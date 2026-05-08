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

        $users     = $expense->couple->users;
        $payer     = $expense->payer;
        // split_ratio = fração que o pagador cobre; o outro deve o restante
        $otherShare = round($expense->amount * (1 - ($expense->split_ratio ?? 0.5)), 2);

        foreach ($users as $user) {
            if ($user->id === $payer->id) {
                continue;
            }

            Balance::create([
                'couple_id'       => $expense->couple_id,
                'user_id'         => $payer->id,
                'related_user_id' => $user->id,
                'amount'          => $otherShare,
                'used_amount'     => 0,
                'type'            => 'credit',
                'origin'          => 'expense',
                'origin_table'    => 'expenses',
                'origin_id'       => $expense->id,
            ]);

            Balance::create([
                'couple_id'       => $expense->couple_id,
                'user_id'         => $user->id,
                'related_user_id' => $payer->id,
                'amount'          => $otherShare,
                'used_amount'     => 0,
                'type'            => 'debit',
                'origin'          => 'expense',
                'origin_table'    => 'expenses',
                'origin_id'       => $expense->id,
            ]);

            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'user_id'    => $user->id,
                'amount'     => $otherShare,
                'is_paid'    => false,
            ]);

            // net out: payer's new credit against payer's existing debits
            $this->netBalances($payer->id, $user->id);
            // net out: other's new debit against other's existing credits
            $this->netBalances($user->id, $payer->id);
        }
    }

    private function netBalances(int $userId, int $relatedUserId): void
    {
        DB::transaction(function () use ($userId, $relatedUserId) {
            $credits = Balance::where('user_id', $userId)
                ->where('related_user_id', $relatedUserId)
                ->where('type', 'credit')
                ->whereColumn('used_amount', '<', 'amount')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $debits = Balance::where('user_id', $userId)
                ->where('related_user_id', $relatedUserId)
                ->where('type', 'debit')
                ->whereColumn('used_amount', '<', 'amount')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            // track remaining locally — model objects go stale after increment()
            $cAvail = $credits->map(fn($c) => (float) $c->amount - (float) $c->used_amount)->toArray();
            $dAvail = $debits->map(fn($d)  => (float) $d->amount - (float) $d->used_amount)->toArray();

            $di = 0;
            foreach ($credits as $ci => $credit) {
                while ($cAvail[$ci] > 0.001 && $di < count($dAvail)) {
                    if ($dAvail[$di] <= 0.001) { $di++; continue; }

                    $consume = min($cAvail[$ci], $dAvail[$di]);

                    Balance::where('id', $credit->id)->increment('used_amount', $consume);
                    Balance::where('id', $debits[$di]->id)->increment('used_amount', $consume);

                    $cAvail[$ci]  -= $consume;
                    $dAvail[$di]  -= $consume;

                    if ($dAvail[$di] <= 0.001) $di++;
                }
            }
        });
    }

    public function createInstallments(Expense $expense, int $installments): void
    {
        $perInstallment = round($expense->amount / $installments, 2);
        $baseDate = $expense->billing_date;

        for ($i = 1; $i <= $installments; $i++) {
            ExpenseInstallment::create([
                'expense_id'         => $expense->id,
                'installment_number' => $i,
                'amount'             => $perInstallment,
                'due_date'           => $baseDate->copy()->addMonths($i - 1),
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
