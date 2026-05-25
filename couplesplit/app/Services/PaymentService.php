<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Couple;
use App\Models\Expense;
use App\Models\ExpenseInstallment;
use App\Models\ExpenseSplit;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /** Evita rodar ensureInstallmentBalances mais de uma vez por request por usuário */
    private static array $ensuredForUser = [];

    public function process(User $payer, Couple $couple, User $partner, float $amount): Payment
    {
        return DB::transaction(function () use ($payer, $couple, $partner, $amount) {
            $payment = Payment::create([
                'couple_id'    => $couple->id,
                'from_user_id' => $payer->id,
                'to_user_id'   => $partner->id,
                'amount'       => $amount,
                'payment_date' => Carbon::now(),
            ]);

            $remaining = $amount;

            $debits = $this->openBalances($payer, $partner)
                ->where('type', 'debit')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($debits as $debit) {
                if ($remaining <= 0) {
                    break;
                }

                $available = $debit->amount - $debit->used_amount;
                $consume = min($available, $remaining);

                if ($consume >= $available && $debit->origin === 'expense') {
                    $expenseId = $this->expenseIdForBalance($debit);
                    if ($expenseId) {
                        ExpenseSplit::where('expense_id', $expenseId)
                            ->where('user_id', $payer->id)
                            ->update(['is_paid' => true]);
                    }
                }

                $debit->increment('used_amount', $consume);

                PaymentItem::create([
                    'payment_id'  => $payment->id,
                    'balance_id'  => $debit->id,
                    'amount'      => $consume,
                    'description' => $this->descriptionForBalance($debit),
                ]);

                $credit = Balance::where('origin', $debit->origin)
                    ->where('origin_table', $debit->origin_table)
                    ->where('origin_id', $debit->origin_id)
                    ->where('user_id', $partner->id)
                    ->where('type', 'credit')
                    ->lockForUpdate()
                    ->first();

                if ($credit) {
                    // Garante que used_amount nunca ultrapasse amount no crédito espelho
                    $creditAvailable = (float) $credit->amount - (float) $credit->used_amount;
                    $creditConsume   = min($consume, $creditAvailable);
                    if ($creditConsume > 0) {
                        $credit->increment('used_amount', $creditConsume);
                    }
                }

                $remaining -= $consume;
            }

            if ($remaining > 0) {
                Balance::create([
                    'couple_id'       => $couple->id,
                    'user_id'         => $payer->id,
                    'related_user_id' => $partner->id,
                    'amount'          => $remaining,
                    'used_amount'     => 0,
                    'type'            => 'credit',
                    'origin'          => 'payment',
                    'origin_table'    => 'payments',
                    'origin_id'       => $payment->id,
                ]);
            }

            return $payment;
        });
    }

    public function openBalances(User $user, User $partner): Builder
    {
        $this->ensureInstallmentBalances($user);

        $installmentExpenseIds = Expense::where('couple_id', $user->currentCouple()?->id)
            ->whereHas('installments')
            ->pluck('id');

        $dueInstallmentIds = ExpenseInstallment::whereNull('paid_at')
            ->whereDate('due_date', '<=', Carbon::now()->endOfMonth())
            ->pluck('id');

        return Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->whereColumn('used_amount', '<', 'amount')
            ->where(function ($query) use ($dueInstallmentIds, $installmentExpenseIds) {
                $query->where(function ($regular) use ($installmentExpenseIds) {
                    $regular->where('origin_table', '!=', 'expense_installments')
                        ->where(function ($nonInstallmentExpense) use ($installmentExpenseIds) {
                            $nonInstallmentExpense->where('origin_table', '!=', 'expenses')
                                ->orWhereNotIn('origin_id', $installmentExpenseIds);
                        });
                })->orWhere(function ($installment) use ($dueInstallmentIds) {
                    $installment->where('origin_table', 'expense_installments')
                        ->whereIn('origin_id', $dueInstallmentIds);
                });
            });
    }

    public function totalOpenDebit(User $user, User $partner): float
    {
        return $this->openBalances($user, $partner)
            ->where('type', 'debit')
            ->get()
            ->sum(fn($b) => $b->amount - $b->used_amount);
    }

    private function ensureInstallmentBalances(User $user): void
    {
        // Roda no máximo uma vez por request por usuário — evita N+1 quando
        // openBalances() é chamado múltiplas vezes (dashboard, settle, etc.)
        if (isset(self::$ensuredForUser[$user->id])) {
            return;
        }
        self::$ensuredForUser[$user->id] = true;

        $couple = $user->currentCouple();
        if (!$couple) {
            return;
        }

        $expenses = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->whereHas('installments')
            ->with(['installments', 'payer', 'couple.users'])
            ->get();

        foreach ($expenses as $expense) {
            $installmentIds = $expense->installments->pluck('id');

            foreach ($expense->couple->users as $member) {
                if ($member->id === $expense->paid_by) {
                    continue;
                }

                foreach ($expense->installments as $installment) {
                    // Parcelas já pagas não geram (nem mantêm) balance de dívida
                    if ($installment->paid_at !== null) {
                        continue;
                    }
                    $amount = round($installment->amount * (1 - ($expense->split_ratio ?? 0.5)), 2);
                    $this->firstOrCreateBalancePair($expense, $expense->paid_by, $member->id, $amount, $installment->id);
                }
            }

            Balance::where('origin_table', 'expenses')
                ->where('origin_id', $expense->id)
                ->where('used_amount', 0)
                ->delete();
        }
    }

    private function firstOrCreateBalancePair(Expense $expense, int $payerId, int $otherUserId, float $amount, int $installmentId): void
    {
        foreach ([['user' => $payerId, 'related' => $otherUserId, 'type' => 'credit'], ['user' => $otherUserId, 'related' => $payerId, 'type' => 'debit']] as $row) {
            Balance::firstOrCreate([
                'user_id'      => $row['user'],
                'related_user_id' => $row['related'],
                'type'         => $row['type'],
                'origin'       => 'expense',
                'origin_table' => 'expense_installments',
                'origin_id'    => $installmentId,
            ], [
                'couple_id'    => $expense->couple_id,
                'amount'       => $amount,
                'used_amount'  => 0,
            ]);
        }
    }
    private function expenseIdForBalance(Balance $balance): ?int
    {
        if ($balance->origin_table === 'expenses') {
            return $balance->origin_id;
        }

        if ($balance->origin_table === 'expense_installments') {
            return ExpenseInstallment::find($balance->origin_id)?->expense_id;
        }

        return null;
    }

    public function descriptionForBalance(Balance $balance): ?string
    {
        if ($balance->origin_table === 'expenses') {
            return Expense::find($balance->origin_id)?->description;
        }

        if ($balance->origin_table === 'expense_installments') {
            $installment = ExpenseInstallment::with('expense')->find($balance->origin_id);
            if ($installment) {
                return $installment->expense->description . ' - parcela ' . $installment->installment_number;
            }
        }

        return null;
    }
}
