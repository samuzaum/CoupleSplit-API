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

        $openDebits = $openBalances->where('type', 'debit');
        $openCredits = $openBalances->where('type', 'credit');

        $recentExpenses = Expense::where('couple_id', $couple->id)
            ->latest()
            ->take(5)
            ->get();

        $thisMonthDebit = $debitAvailable;

        // dados para gráficos
        $allExpenses = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->with('installments')
            ->get();

        $byCategory = $allExpenses
            ->groupBy('category')
            ->map(fn($g) => round($g->sum(fn($expense) => $this->expenseMonthlyAmount($expense, Carbon::now())), 2))
            ->filter(fn($total) => $total > 0)
            ->mapWithKeys(fn($total, $key) => [$key ?: 'Sem categoria' => $total]);

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        $byMonth = $months->mapWithKeys(function ($ym) use ($allExpenses) {
            $monthDate = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
            $total = $allExpenses->sum(fn($expense) => $this->expenseMonthlyAmount($expense, $monthDate));
            $label = $monthDate->translatedFormat('M/y');
            return [$label => round($total, 2)];
        });

        $customCats = $couple->categories()->pluck('name');

        $coupleMonthTotal = $this->sharedMonthlyTotal($couple->id, Carbon::now());

        $lastMonth = Carbon::now()->subMonth();
        $lastMonthTotal = $this->sharedMonthlyTotal($couple->id, $lastMonth);
        $monthDelta = round($coupleMonthTotal - $lastMonthTotal, 2);

        $budgets = $couple->budgets()->get()->map(function ($budget) {
            $spent = $budget->spentThisMonth();
            $budget->spent      = round($spent, 2);
            $budget->percentage = min(100, $budget->amount > 0 ? round($spent / $budget->amount * 100) : 0);
            return $budget;
        });

        $openInstallmentsCount = ExpenseInstallment::whereHas('expense', fn($q) => $q->where('couple_id', $couple->id))
            ->whereNull('paid_at')
            ->count();

        $exceededBudgets = collect($this->notifications->getActive($user))
            ->filter(fn($n) => $n['type'] === 'budget_exceeded');

        $totalIncome  = ($user->monthly_income ?? 0) + ($partner->monthly_income ?? 0);
        $myRatio      = $totalIncome > 0 ? round($user->monthly_income / $totalIncome * 100) : null;
        $partnerRatio = $myRatio !== null ? 100 - $myRatio : null;

        $myCardCosts = $this->myCardCostsThisCycle($user, $couple);

        // share mensal e breakdown do saldo
        [$myMonthShare, $partnerMonthShare] = $this->monthShareSplit($allExpenses, $user, $coupleMonthTotal);
        $balanceBreakdown = $this->buildBalanceBreakdown($openBalances, $user, $partner);

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

    private function monthShareSplit($allExpenses, $user, float $coupleMonthTotal): array
    {
        $now = Carbon::now();

        $myShare = round($allExpenses->sum(function (Expense $expense) use ($user, $now) {
            $amount = $this->expenseMonthlyAmount($expense, $now);
            if ($amount <= 0) return 0;
            return $expense->paid_by === $user->id
                ? $amount * ($expense->split_ratio ?? 0.5)
                : $amount * (1 - ($expense->split_ratio ?? 0.5));
        }), 2);

        return [$myShare, round($coupleMonthTotal - $myShare, 2)];
    }

    private function buildBalanceBreakdown($openBalances, $user, $partner): \Illuminate\Support\Collection
    {
        // agrupa créditos e débitos por expense para montar o breakdown
        return $openBalances->map(function ($balance) use ($user, $partner) {
            $remaining = round($balance->amount - $balance->used_amount, 2);
            $isCredit  = $balance->type === 'credit';

            // tenta resolver descrição via origin
            $description = null;
            if ($balance->origin_table === 'expenses') {
                $description = Expense::find($balance->origin_id)?->description;
            } elseif ($balance->origin_table === 'expense_installments') {
                $inst = \App\Models\ExpenseInstallment::with('expense')->find($balance->origin_id);
                if ($inst) {
                    $description = $inst->expense->description . ' (parcela ' . $inst->installment_number . ')';
                }
            }

            return (object) [
                'description' => $description ?? 'Despesa',
                'amount'      => $remaining,
                'is_credit'   => $isCredit,
                // crédito = parceiro te deve; débito = você deve ao parceiro
                'label'       => $isCredit
                    ? $partner->name . ' te deve'
                    : 'Você deve ' . $partner->name,
            ];
        })->filter(fn($b) => $b->amount > 0.001);
    }

    private function myCardCostsThisCycle($user, $couple): \Illuminate\Support\Collection
    {
        $today = Carbon::now();

        return $user->cards()
            ->where('type', 'credit')
            ->get()
            ->map(function ($card) use ($user, $couple, $today) {
                $closingDay = $card->closing_day ?? 1;

                $nextClosing = $today->day < $closingDay
                    ? $today->copy()->setDay($closingDay)
                    : $today->copy()->addMonthNoOverflow()->setDay($closingDay);

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
                    $full = round((float) $exp->amount, 2);
                    $mine = $exp->is_shared
                        ? round($full * ($exp->split_ratio ?? 0.5), 2)
                        : $full;
                    $lines->push((object) [
                        'description'  => $exp->description,
                        'full_amount'  => $full,
                        'my_amount'    => $mine,
                        'is_shared'    => (bool) $exp->is_shared,
                        'split_pct'    => round(($exp->split_ratio ?? 0.5) * 100),
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

        $date = $expense->billing_date ?: $expense->expense_date;

        if (!$date || !$date->isSameMonth($month)) {
            return 0.0;
        }

        return (float) $expense->amount;
    }

}
