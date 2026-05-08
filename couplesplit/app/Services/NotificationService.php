<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;

class NotificationService
{
    const ALERT_DAYS_BEFORE = 5;

    public function getActive(User $user): array
    {
        $couple = $user->couples()->first();
        if (!$couple) {
            return [];
        }

        $notifications = [];
        $today = Carbon::today();

        // alertas de fechamento de cartão
        foreach ($couple->users as $member) {
            foreach ($member->cards()->where('type', 'credit')->get() as $card) {
                $closing = $today->copy()->setDay($card->closing_day);

                if ($closing->lt($today)) {
                    $closing = $closing->addMonth();
                }

                $daysUntil = (int) $today->diffInDays($closing, false);

                if ($daysUntil >= 0 && $daysUntil <= self::ALERT_DAYS_BEFORE) {
                    $notifications[] = [
                        'type'         => 'card_closing',
                        'card'         => $card,
                        'owner'        => $member,
                        'days_until'   => $daysUntil,
                        'closing_date' => $closing,
                    ];
                }
            }
        }

        // alertas de orçamento estourado
        $budgets = $couple->budgets()->get();

        foreach ($budgets as $budget) {
            $spent = Expense::where('couple_id', $couple->id)
                ->where('is_shared', true)
                ->where('category', $budget->category)
                ->whereYear('expense_date', $today->year)
                ->whereMonth('expense_date', $today->month)
                ->sum('amount');

            if ($spent >= $budget->amount) {
                $notifications[] = [
                    'type'     => 'budget_exceeded',
                    'category' => $budget->category,
                    'limit'    => $budget->amount,
                    'spent'    => round($spent, 2),
                    'overflow' => round($spent - $budget->amount, 2),
                ];
            } elseif ($spent >= $budget->amount * 0.9) {
                $notifications[] = [
                    'type'     => 'budget_warning',
                    'category' => $budget->category,
                    'limit'    => $budget->amount,
                    'spent'    => round($spent, 2),
                    'pct'      => round($spent / $budget->amount * 100),
                ];
            }
        }

        // despesas contestadas pelo parceiro onde o usuário é o pagador
        $disputed = Expense::where('couple_id', $couple->id)
            ->where('paid_by', $user->id)
            ->where('status', 'disputed')
            ->get();

        foreach ($disputed as $expense) {
            $notifications[] = [
                'type'    => 'expense_disputed',
                'expense' => $expense,
            ];
        }

        return $notifications;
    }

    public function count(User $user): int
    {
        return count($this->getActive($user));
    }
}
