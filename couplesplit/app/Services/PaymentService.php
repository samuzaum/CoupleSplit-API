<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Couple;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
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

            // busca os débitos de quem está pagando (payer deve ao partner)
            $debits = Balance::where('user_id', $payer->id)
                ->where('related_user_id', $partner->id)
                ->where('type', 'debit')
                ->whereColumn('used_amount', '<', 'amount')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($debits as $debit) {
                if ($remaining <= 0) {
                    break;
                }

                $available = $debit->amount - $debit->used_amount;
                $consume   = min($available, $remaining);

                if ($consume >= $available && $debit->origin === 'expense') {
                    ExpenseSplit::where('expense_id', $debit->origin_id)
                        ->where('user_id', $payer->id)
                        ->update(['is_paid' => true]);

                    // Se todos os splits da despesa estão pagos, marca a despesa como paga
                    $expense = Expense::find($debit->origin_id);
                    if ($expense && $expense->paid_at === null) {
                        $allPaid = ExpenseSplit::where('expense_id', $expense->id)
                            ->where('is_paid', false)
                            ->doesntExist();

                        if ($allPaid) {
                            $expense->update(['paid_at' => now()]);
                        }
                    }
                }

                $debit->increment('used_amount', $consume);

                // descrição para o item do histórico
                $description = $debit->origin === 'expense'
                    ? Expense::find($debit->origin_id)?->description
                    : null;

                PaymentItem::create([
                    'payment_id'  => $payment->id,
                    'balance_id'  => $debit->id,
                    'amount'      => $consume,
                    'description' => $description,
                ]);

                // consome o crédito correspondente do partner (quem pagou a despesa)
                $credit = Balance::where('origin', 'expense')
                    ->where('origin_id', $debit->origin_id)
                    ->where('user_id', $partner->id)
                    ->where('type', 'credit')
                    ->lockForUpdate()
                    ->first();

                if ($credit) {
                    $credit->increment('used_amount', $consume);
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

    public function totalOpenDebit(User $user, User $partner): float
    {
        return Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->whereColumn('used_amount', '<', 'amount')
            ->get()
            ->sum(fn($b) => $b->amount - $b->used_amount);
    }
}
