<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\ExpenseInstallment;
use App\Services\NotificationService;

class DashboardController extends Controller
{
    public function __construct(private PaymentService $service, private NotificationService $notifications) {}

    public function index()
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

        if (!$couple) {
            return view('dashboard-no-couple');
        }

        $partner = $couple->users()
            ->where('users.id', '!=', $user->id)
            ->first();

        if (!$partner) {
            return view('dashboard-no-couple', ['waitingPartner' => true]);
        }

        $openBalances = $this->service->openBalances($user, $partner)->orderBy('created_at')->get();

        $netBalance = $openBalances->sum(function ($b) {
            $remaining = $b->amount - $b->used_amount;
            return $b->type === 'credit' ? $remaining : -$remaining;
        });

        $creditAvailable = $openBalances
            ->where('type', 'credit')
            ->sum(fn($b) => $b->amount - $b->used_amount);

        $debitAvailable = $openBalances
            ->where('type', 'debit')
            ->sum(fn($b) => $b->amount - $b->used_amount);

        // Hidrata os balances com descrição legível (origem da dívida/crédito)
        $openBalances->each(function ($b) {
            $b->label = $this->service->descriptionForBalance($b) ?? 'Saldo';
        });

        $openDebits  = $openBalances->where('type', 'debit');
        $openCredits = $openBalances->where('type', 'credit');

        $recentExpenses = Expense::where('couple_id', $couple->id)
            ->orderBy('expense_date', 'desc')
            ->take(5)
            ->get();

        $thisMonthDebit = $debitAvailable;

        // Todas as despesas compartilhadas do casal (base para os gráficos)
        $allExpenses = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->with('installments')
            ->get();

        // Ciclo financeiro do usuário (baseado em cycle_start_day)
        [$cycleStart, $cycleEnd] = $user->financialCycle();
        [$prevCycleStart, $prevCycleEnd] = $user->financialCycle($cycleStart->copy()->subDay());

        // byCategory: parte do usuário por categoria NO ciclo atual
        $byCategory = $allExpenses
            ->groupBy('category')
            ->map(function ($g) use ($user, $cycleStart, $cycleEnd) {
                return round($g->sum(function ($expense) use ($user, $cycleStart, $cycleEnd) {
                    $amount = $this->expenseAmountInCycle($expense, $cycleStart, $cycleEnd);
                    if ($amount == 0.0) return 0.0;
                    $ratio = $expense->split_ratio ?? 0.5;
                    return $amount * ($expense->paid_by === $user->id ? $ratio : 1 - $ratio);
                }), 2);
            })
            ->filter(fn($total) => $total > 0)
            ->mapWithKeys(fn($total, $key) => [$key ?: 'Sem categoria' => $total]);

        // byMonth: tendência dos últimos 6 meses calendário (mantém meses calendário para o gráfico de tendência)
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        $byMonth = $months->mapWithKeys(function ($ym) use ($allExpenses, $user) {
            $monthDate = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
            $total = $allExpenses->sum(function ($expense) use ($monthDate, $user) {
                $monthAmount = $this->expenseMonthlyAmount($expense, $monthDate);
                if ($monthAmount == 0.0) return 0.0;
                $ratio = $expense->split_ratio ?? 0.5;
                return $monthAmount * ($expense->paid_by === $user->id ? $ratio : 1 - $ratio);
            });
            $label = $monthDate->translatedFormat('M/y');
            return [$label => round($total, 2)];
        });

        $customCats = $couple->categories()->pluck('name');

        // Totais usando ciclo financeiro do usuário
        $coupleMonthTotal = $this->sharedCycleTotal($couple->id, $cycleStart, $cycleEnd);
        $lastMonthTotal   = $this->sharedCycleTotal($couple->id, $prevCycleStart, $prevCycleEnd);
        $monthDelta = round($coupleMonthTotal - $lastMonthTotal, 2);

        $budgets = $couple->budgets()->get()->map(function ($budget) use ($user) {
            $spent = $budget->spentThisMonth($user);
            $budget->spent      = round($spent, 2);
            $budget->percentage = min(100, $budget->amount > 0 ? round($spent / $budget->amount * 100) : 0);
            return $budget;
        });

        // Conta parcelas abertas NA PERSPECTIVA DO USUÁRIO (não do casal inteiro)
        $myDebitInstallmentsCount  = $openBalances->where('type', 'debit')->where('origin_table', 'expense_installments')->count();
        $myCreditInstallmentsCount = $openBalances->where('type', 'credit')->where('origin_table', 'expense_installments')->count();
        $openInstallmentsCount     = $myDebitInstallmentsCount + $myCreditInstallmentsCount;

        $exceededBudgets = collect($this->notifications->getActive($user))
            ->filter(fn($n) => $n['type'] === 'budget_exceeded');

        $totalIncome  = ($user->monthly_income ?? 0) + ($partner->monthly_income ?? 0);
        $myRatio      = $totalIncome > 0 ? round(($user->monthly_income ?? 0) / $totalIncome * 100) : null;
        $partnerRatio = $myRatio !== null ? 100 - $myRatio : null;

        $myCardCosts = $this->myCardCostsThisCycle($user, $couple);

        // breakdown por ciclo financeiro do usuário
        $balanceBreakdown  = $this->buildBalanceBreakdown($allExpenses, $user, $partner, $cycleStart, $cycleEnd);
        $myMonthShare      = round($balanceBreakdown->sum('my_share'), 2);
        $partnerMonthShare = round($balanceBreakdown->sum('partner_share'), 2);

        return view('dashboard', compact(
            'partner',
            'netBalance',
            'creditAvailable',
            'debitAvailable',
            'thisMonthDebit',
            'openDebits',
            'openCredits',
            'recentExpenses',
            'byCategory',
            'byMonth',
            'customCats',
            'coupleMonthTotal',
            'lastMonthTotal',
            'monthDelta',
            'myRatio',
            'partnerRatio',
            'budgets',
            'openInstallmentsCount',
            'myDebitInstallmentsCount',
            'myCreditInstallmentsCount',
            'exceededBudgets',
            'myCardCosts',
            'myMonthShare',
            'partnerMonthShare',
            'balanceBreakdown'
        ));
    }

    public function settle()
    {
        $user    = Auth::user();
        $couple  = $user->currentCoupleOrFail();
        $partner = $couple->users()->where('users.id', '!=', $user->id)->firstOrFail();
        $netBalance = $this->service->openBalances($user, $partner)
            ->get()
            ->sum(fn($b) => $b->type === 'credit'
                ? $b->amount - $b->used_amount
                : -($b->amount - $b->used_amount));

        if ($netBalance >= 0) {
            return redirect()->route('dashboard')->with('info', 'Você não tem dívida líquida em aberto.');
        }

        $this->service->process($user, $couple, $partner, abs($netBalance));

        return redirect()->route('dashboard')->with('success', 'Dívida liquidada!');
    }

    private function buildBalanceBreakdown($allExpenses, $user, $partner, Carbon $cycleStart, Carbon $cycleEnd): \Illuminate\Support\Collection
    {
        return $allExpenses
            ->filter(function (Expense $e) use ($cycleStart, $cycleEnd) {
                if ($e->installments->isNotEmpty()) {
                    return $this->expenseAmountInCycle($e, $cycleStart, $cycleEnd) > 0;
                }
                return $e->expense_date && $e->expense_date->between($cycleStart, $cycleEnd);
            })
            ->map(function (Expense $expense) use ($user, $partner, $cycleStart, $cycleEnd) {
                $monthAmount = $expense->installments->isNotEmpty()
                    ? round($this->expenseAmountInCycle($expense, $cycleStart, $cycleEnd), 2)
                    : round((float) $expense->amount, 2);

                $splitRatio   = $expense->split_ratio ?? 0.5;
                $iPaid        = $expense->paid_by === $user->id;
                $myShare      = round($monthAmount * ($iPaid ? $splitRatio : 1 - $splitRatio), 2);
                $partnerShare = round($monthAmount - $myShare, 2);
                $iOwe         = !$iPaid ? $myShare   : 0;
                $partnerOwes  = $iPaid  ? $partnerShare : 0;

                $label = $expense->description;
                if ($expense->installments->isNotEmpty()) {
                    $inst = $expense->installments->first(
                        fn($i) => $i->due_date && $i->due_date->between($cycleStart, $cycleEnd)
                    );
                    if ($inst) {
                        $label .= ' (parcela ' . $inst->installment_number . '/' . $expense->installments->count() . ')';
                    }
                }

                return (object) [
                    'description'   => $label,
                    'full_amount'   => $monthAmount,
                    'my_share'      => $myShare,
                    'partner_share' => $partnerShare,
                    'split_pct'     => round($splitRatio * 100),
                    'i_paid'        => $iPaid,
                    'i_owe'         => $iOwe,
                    'partner_owes'  => $partnerOwes,
                    'payer_name'    => $iPaid ? 'Você' : $partner->name,
                ];
            })
            ->sortByDesc('full_amount')
            ->values();
    }

    private function myCardCostsThisCycle($user, $couple): \Illuminate\Support\Collection
    {
        $today = Carbon::now();

        return $user->cards()
            ->where('type', 'credit')
            ->get()
            ->map(function ($card) use ($user, $couple, $today) {
                $closingDay = $card->closing_day ?? 1;

                // Safe setDay: clamp ao último dia do mês para evitar overflow em meses curtos
                $candidateThisMonth = $today->copy()->setDay(min($closingDay, $today->daysInMonth));
                $nextClosing = $today->day < $closingDay
                    ? $candidateThisMonth
                    : $today->copy()->addMonthNoOverflow()->setDay(
                        min($closingDay, $today->copy()->addMonthNoOverflow()->daysInMonth)
                      );

                $installments = ExpenseInstallment::whereHas('expense', fn($q) =>
                    $q->where('couple_id', $couple->id)
                      ->where('card_id', $card->id)
                      ->where('paid_by', $user->id)
                )->whereNull('paid_at')
                 ->whereYear('due_date', $nextClosing->year)
                 ->whereMonth('due_date', $nextClosing->month)
                 ->with('expense')
                 ->get();

                $singleExpenses = Expense::where('couple_id', $couple->id)
                    ->where('card_id', $card->id)
                    ->whereDoesntHave('installments')
                    ->whereYear('billing_date', $nextClosing->year)
                    ->whereMonth('billing_date', $nextClosing->month)
                    ->where(function ($q) {
                        $q->where('is_shared', true)->orWhereNull('paid_at');
                    })
                    ->get();

                $totalBill = round(
                    $installments->sum('amount') + $singleExpenses->sum('amount'),
                    2
                );

                // monta linhas detalhadas para o breakdown
                $lines = collect();

                foreach ($installments as $inst) {
                    $full   = round((float) $inst->amount, 2);
                    $mine   = $inst->expense->is_shared
                        ? round($full * ($inst->expense->split_ratio ?? 0.5), 2)
                        : $full;
                    $lines->push((object) [
                        'description'  => $inst->expense->description . ' (parcela ' . $inst->installment_number . ')',
                        'full_amount'  => $full,
                        'my_amount'    => $mine,
                        'is_shared'    => (bool) $inst->expense->is_shared,
                        'split_pct'    => round(($inst->expense->split_ratio ?? 0.5) * 100),
                    ]);
                }

                foreach ($singleExpenses as $exp) {
                    $full   = round((float) $exp->amount, 2);
                    $iPaid  = $exp->paid_by === $user->id;
                    $ratio  = $exp->split_ratio ?? 0.5;
                    $mine   = $exp->is_shared
                        ? round($full * ($iPaid ? $ratio : 1 - $ratio), 2)
                        : $full;
                    $lines->push((object) [
                        'description'  => $exp->description,
                        'full_amount'  => $full,
                        'my_amount'    => $mine,
                        'is_shared'    => (bool) $exp->is_shared,
                        'split_pct'    => round($ratio * 100),
                    ]);
                }

                $myCost = round($lines->sum('my_amount'), 2);

                return (object) [
                    'card'          => $card,
                    'next_closing'  => $nextClosing,
                    'total_bill'    => $totalBill,
                    'my_cost'       => $myCost,
                    'partner_share' => round($totalBill - $myCost, 2),
                    'lines'         => $lines,
                ];
            })
            ->filter(fn($c) => $c->total_bill > 0);
    }

    /**
     * Total do casal em despesas compartilhadas dentro de um período (ciclo financeiro).
     */
    private function sharedCycleTotal(int $coupleId, Carbon $from, Carbon $to): float
    {
        return round(Expense::where('couple_id', $coupleId)
            ->where('is_shared', true)
            ->with('installments')
            ->get()
            ->sum(fn($expense) => $this->expenseAmountInCycle($expense, $from, $to)), 2);
    }

    /**
     * Valor da despesa que cai dentro de um período [from, to] (inclusivo).
     * Para parceladas: soma as parcelas com due_date no período.
     * Para avulsas: retorna o valor total se expense_date está no período.
     */
    private function expenseAmountInCycle(Expense $expense, Carbon $from, Carbon $to): float
    {
        if ($expense->installments->isNotEmpty()) {
            return (float) $expense->installments
                ->filter(fn($i) => $i->due_date && $i->due_date->between($from, $to))
                ->sum('amount');
        }

        $date = $expense->expense_date;
        return $date && $date->between($from, $to) ? (float) $expense->amount : 0.0;
    }

    /**
     * Mantido para o gráfico de tendência (6 meses calendário).
     */
    private function sharedMonthlyTotal(int $coupleId, Carbon $month): float
    {
        return round(Expense::where('couple_id', $coupleId)
            ->where('is_shared', true)
            ->with('installments')
            ->get()
            ->sum(fn($expense) => $this->expenseMonthlyAmount($expense, $month)), 2);
    }

    private function expenseMonthlyAmount(Expense $expense, Carbon $month): float
    {
        if ($expense->installments->isNotEmpty()) {
            return (float) $expense->installments
                ->filter(fn($installment) =>
                    $installment->due_date &&
                    $installment->due_date->isSameMonth($month)
                )
                ->sum('amount');
        }

        $date = $expense->expense_date;

        if (!$date || !$date->isSameMonth($month)) {
            return 0.0;
        }

        return (float) $expense->amount;
    }

}
