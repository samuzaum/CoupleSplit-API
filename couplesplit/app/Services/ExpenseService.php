<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Card;
use App\Models\Expense;
use App\Models\ExpenseInstallment;
use App\Models\ExpenseSplit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function createBalances(Expense $expense): void
    {
        if (!$expense->is_shared) {
            return;
        }

        $users = $expense->couple->users;
        $payer = $expense->payer;
        $installments = $expense->installments()->orderBy('installment_number')->get();

        foreach ($users as $user) {
            if ($user->id === $payer->id) {
                continue;
            }

            if ($installments->isNotEmpty()) {
                foreach ($installments as $installment) {
                    $this->createBalancePair(
                        $expense,
                        $payer->id,
                        $user->id,
                        round($installment->amount * (1 - ($expense->split_ratio ?? 0.5)), 2),
                        'expense_installments',
                        $installment->id
                    );
                }
            } else {
                $this->createBalancePair(
                    $expense,
                    $payer->id,
                    $user->id,
                    round($expense->amount * (1 - ($expense->split_ratio ?? 0.5)), 2),
                    'expenses',
                    $expense->id
                );
            }

            ExpenseSplit::updateOrCreate(
                ['expense_id' => $expense->id, 'user_id' => $user->id],
                [
                    'amount'  => round($expense->amount * (1 - ($expense->split_ratio ?? 0.5)), 2),
                    'is_paid' => false,
                ]
            );

            $this->netBalances($payer->id, $user->id);
            $this->netBalances($user->id, $payer->id);
        }
    }

    private function createBalancePair(Expense $expense, int $payerId, int $otherUserId, float $amount, string $originTable, int $originId): void
    {
        Balance::create([
            'couple_id'       => $expense->couple_id,
            'user_id'         => $payerId,
            'related_user_id' => $otherUserId,
            'amount'          => $amount,
            'used_amount'     => 0,
            'type'            => 'credit',
            'origin'          => 'expense',
            'origin_table'    => $originTable,
            'origin_id'       => $originId,
        ]);

        Balance::create([
            'couple_id'       => $expense->couple_id,
            'user_id'         => $otherUserId,
            'related_user_id' => $payerId,
            'amount'          => $amount,
            'used_amount'     => 0,
            'type'            => 'debit',
            'origin'          => 'expense',
            'origin_table'    => $originTable,
            'origin_id'       => $originId,
        ]);
    }

    private function netBalances(int $userId, int $relatedUserId): void
    {
        DB::transaction(function () use ($userId, $relatedUserId) {
            $dueInstallmentIds = ExpenseInstallment::whereNull('paid_at')
                ->whereDate('due_date', '<=', Carbon::now()->endOfMonth())
                ->pluck('id');

            $credits = Balance::where('user_id', $userId)
                ->where('related_user_id', $relatedUserId)
                ->where('type', 'credit')
                ->whereColumn('used_amount', '<', 'amount')
                ->where(function ($query) use ($dueInstallmentIds) {
                    $query->where('origin_table', '!=', 'expense_installments')
                        ->orWhereIn('origin_id', $dueInstallmentIds);
                })
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $debits = Balance::where('user_id', $userId)
                ->where('related_user_id', $relatedUserId)
                ->where('type', 'debit')
                ->whereColumn('used_amount', '<', 'amount')
                ->where(function ($query) use ($dueInstallmentIds) {
                    $query->where('origin_table', '!=', 'expense_installments')
                        ->orWhereIn('origin_id', $dueInstallmentIds);
                })
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $cAvail = $credits->map(fn($c) => (float) $c->amount - (float) $c->used_amount)->toArray();
            $dAvail = $debits->map(fn($d) => (float) $d->amount - (float) $d->used_amount)->toArray();

            $di = 0;
            foreach ($credits as $ci => $credit) {
                while ($cAvail[$ci] > 0.001 && $di < count($dAvail)) {
                    if ($dAvail[$di] <= 0.001) {
                        $di++;
                        continue;
                    }

                    $consume = min($cAvail[$ci], $dAvail[$di]);

                    Balance::where('id', $credit->id)->increment('used_amount', $consume);
                    Balance::where('id', $debits[$di]->id)->increment('used_amount', $consume);

                    $cAvail[$ci] -= $consume;
                    $dAvail[$di] -= $consume;

                    if ($dAvail[$di] <= 0.001) {
                        $di++;
                    }
                }
            }
        });
    }

    public function createInstallments(Expense $expense, int $installments, int $paidInstallments = 0): void
    {
        $totalCents = (int) round($expense->amount * 100);
        $baseCents = intdiv($totalCents, $installments);
        $remainder = $totalCents % $installments;
        $baseDate = $expense->billing_date;
        $paidInstallments = max(0, min($paidInstallments, $installments));

        for ($i = 1; $i <= $installments; $i++) {
            $amount = ($baseCents + ($i <= $remainder ? 1 : 0)) / 100;

            ExpenseInstallment::create([
                'expense_id'         => $expense->id,
                'installment_number' => $i,
                'amount'             => $amount,
                'due_date'           => $baseDate->copy()->addMonths($i - 1),
                'paid_at'            => $i <= $paidInstallments ? Carbon::now() : null,
            ]);
        }
    }

    public function calculateBillingDate($cardId, Carbon $expenseDate): Carbon
    {
        if (!$cardId) {
            return $expenseDate;
        }

        $card = Card::findOrFail($cardId);

        if ($card->type === 'debit') {
            return $expenseDate;
        }

        if ($expenseDate->day <= $card->closing_day) {
            return $expenseDate->copy()->startOfMonth();
        }

        return $expenseDate->copy()->addMonth()->startOfMonth();
    }
}
