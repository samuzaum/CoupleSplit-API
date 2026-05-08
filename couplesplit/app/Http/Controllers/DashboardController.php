<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expense;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\CoupleCategory;
use App\Models\CoupleBudget;
use App\Models\ExpenseInstallment;
use App\Services\NotificationService;

class DashboardController extends Controller
{
    public function __construct(private PaymentService $service, private NotificationService $notifications) {}

    public function index()
    {
        $user   = Auth::user();
        $couple = $user->couples()->first();

        if (!$couple) {
            return view('dashboard-no-couple');
        }

        $partner = $couple->users()
            ->where('users.id', '!=', $user->id)
            ->first();

        if (!$partner) {
            return view('dashboard-no-couple', ['waitingPartner' => true]);
        }

        $netBalance = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->get()
            ->sum(function ($b) {
                $remaining = $b->amount - $b->used_amount;
                return $b->type === 'credit' ? $remaining : -$remaining;
            });

        $creditAvailable = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'credit')
            ->get()
            ->sum(fn($b) => $b->amount - $b->used_amount);

        $debitAvailable = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->get()
            ->sum(fn($b) => $b->amount - $b->used_amount);

        $openDebits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->whereColumn('used_amount', '<', 'amount')
            ->orderBy('created_at')
            ->get();

        $openCredits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'credit')
            ->whereColumn('used_amount', '<', 'amount')
            ->orderBy('created_at')
            ->get();

        $recentExpenses = Expense::where('couple_id', $couple->id)
            ->latest()
            ->take(5)
            ->get();

        // despesas do mês atual que o usuário deve (não pagou)
        $thisMonthExpenseIds = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->where('paid_by', '!=', $user->id)
            ->whereYear('billing_date', Carbon::now()->year)
            ->whereMonth('billing_date', Carbon::now()->month)
            ->pluck('id');

        $thisMonthDebit = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->where('origin', 'expense')
            ->whereIn('origin_id', $thisMonthExpenseIds)
            ->sum('amount');

        // dados para gráficos
        $allExpenses = Expense::where('couple_id', $couple->id)->where('is_shared', true)->get();

        $byCategory = $allExpenses
            ->groupBy('category')
            ->map(fn($g) => round($g->sum('amount'), 2))
            ->mapWithKeys(fn($total, $key) => [$key ?: 'Sem categoria' => $total]);

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        $byMonth = $months->mapWithKeys(function ($ym) use ($allExpenses) {
            [$year, $month] = explode('-', $ym);
            $total = $allExpenses->filter(fn($e) =>
                $e->billing_date &&
                $e->billing_date->year == $year &&
                $e->billing_date->month == $month
            )->sum('amount');
            $label = Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M/y');
            return [$label => round($total, 2)];
        });

        $customCats = $couple->categories()->pluck('name');

        $coupleMonthTotal = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->whereYear('expense_date', Carbon::now()->year)
            ->whereMonth('expense_date', Carbon::now()->month)
            ->sum('amount');

        $lastMonth = Carbon::now()->subMonth();
        $lastMonthTotal = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->whereYear('expense_date', $lastMonth->year)
            ->whereMonth('expense_date', $lastMonth->month)
            ->sum('amount');
        $monthDelta = round($coupleMonthTotal - $lastMonthTotal, 2);

        $budgets = $couple->budgets()->get()->map(function ($budget) use ($couple) {
            $spent = Expense::where('couple_id', $couple->id)
                ->where('is_shared', true)
                ->where('category', $budget->category)
                ->whereYear('expense_date', Carbon::now()->year)
                ->whereMonth('expense_date', Carbon::now()->month)
                ->sum('amount');

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
            'exceededBudgets'
        ));
    }

    public function settle()
    {
        $user    = Auth::user();
        $couple  = $user->couples()->firstOrFail();
        $partner = $couple->users()->where('users.id', '!=', $user->id)->firstOrFail();

        $total = $this->service->totalOpenDebit($user, $partner);

        if ($total <= 0) {
            return redirect()->route('dashboard')->with('info', 'Nenhuma dívida em aberto.');
        }

        $this->service->process($user, $couple, $partner, $total);

        return redirect()->route('dashboard')->with('success', 'Todas as dívidas foram liquidadas!');
    }
}
