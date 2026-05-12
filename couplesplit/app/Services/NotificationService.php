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
        $couple = $user->currentCouple();
        if (!$couple) {
            return [];
        }

        $dismissed = $user->notification_dismissals ?? [];
        $notifications = [];
        $today = Carbon::today();
        $month = $today->format('Y-m');

        // alertas de fechamento de cartão
        foreach ($couple->users as $member) {
            foreach ($member->cards()->where('type', 'credit')->get() as $card) {
                $closing = $today->copy()->setDay($card->closing_day);

                if ($closing->lt($today)) {
                    $closing = $closing->addMonth();
                }

                $daysUntil = (int) $today->diffInDays($closing, false);

                if ($daysUntil >= 0 && $daysUntil <= self::ALERT_DAYS_BEFORE) {
                    $key = "card_closing:{$card->id}:{$month}";
                    if (!in_array($key, $dismissed)) {
                        $notifications[] = [
                            'type'         => 'card_closing',
                            'key'          => $key,
                            'card'         => $card,
                            'owner'        => $member,
                            'days_until'   => $daysUntil,
                            'closing_date' => $closing,
                        ];
                    }
                }
            }
        }

        // alertas de orçamento
        $budgets = $couple->budgets()->get();

        foreach ($budgets as $budget) {
            $spent = $budget->spentThisMonth();

            if ($spent >= $budget->amount) {
                $key = "budget_exceeded:{$budget->category}:{$month}";
                if (!in_array($key, $dismissed)) {
                    $notifications[] = [
                        'type'     => 'budget_exceeded',
                        'key'      => $key,
                        'category' => $budget->category,
                        'limit'    => $budget->amount,
                        'spent'    => round($spent, 2),
                        'overflow' => round($spent - $budget->amount, 2),
                    ];
                }
            } elseif ($spent >= $budget->amount * 0.9) {
                $key = "budget_warning:{$budget->category}:{$month}";
                if (!in_array($key, $dismissed)) {
                    $notifications[] = [
                        'type'     => 'budget_warning',
                        'key'      => $key,
                        'category' => $budget->category,
                        'limit'    => $budget->amount,
                        'spent'    => round($spent, 2),
                        'pct'      => round($spent / $budget->amount * 100),
                    ];
                }
            }
        }

        // despesas contestadas (sem dismiss — some automaticamente quando resolvida)
        $disputed = Expense::where('couple_id', $couple->id)
            ->where('paid_by', $user->id)
            ->where('status', 'disputed')
            ->get();

        foreach ($disputed as $expense) {
            $notifications[] = [
                'type'    => 'expense_disputed',
                'key'     => null,
                'expense' => $expense,
            ];
        }

        return $notifications;
    }

    public function dismiss(User $user, array $keys): void
    {
        $dismissed = $user->notification_dismissals ?? [];

        // Adiciona as novas chaves
        $dismissed = array_values(array_unique(array_merge($dismissed, $keys)));

        // Limpa chaves de meses anteriores (mantém só mês atual e próximo)
        $validMonths = [
            Carbon::today()->format('Y-m'),
            Carbon::today()->addMonth()->format('Y-m'),
        ];

        $dismissed = array_values(array_filter($dismissed, function (string $key) use ($validMonths) {
            foreach ($validMonths as $month) {
                if (str_contains($key, $month)) {
                    return true;
                }
            }
            return false;
        }));

        $user->notification_dismissals = $dismissed;
        $user->save();
    }

    public function count(User $user): int
    {
        return count($this->getActive($user));
    }
}
