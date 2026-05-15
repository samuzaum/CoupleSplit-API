<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseInstallment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalculatorController extends Controller
{
    public function index(Request $request)
    {
        $user    = Auth::user();
        $couple  = $user->currentCoupleOrFail();
        $partner = $couple->users()->where('users.id', '!=', $user->id)->first();

        $totalIncome = ($user->monthly_income ?? 0) + ($partner?->monthly_income ?? 0);
        $currentMonth = Carbon::now()->startOfMonth();
        $committed = $this->committedForMonth($couple->id, $currentMonth);
        $available = $totalIncome > 0 ? round($totalIncome - $committed, 2) : null;

        $simulation = null;
        $cashComparison = null;

        if ($request->filled('amount') && $request->filled('installments')) {
            $request->validate([
                'amount'       => 'required|numeric|min:0.01',
                'installments' => 'required|integer|min:1|max:48',
            ]);

            $amount = (float) $request->amount;
            $installments = (int) $request->installments;
            $monthly = round($amount / $installments, 2);

            $months = collect();
            for ($i = 0; $i < 12; $i++) {
                $months->push(Carbon::now()->startOfMonth()->addMonths($i));
            }

            $simulation = $months->map(function ($month) use ($couple, $monthly, $installments, $totalIncome, $months) {
                $index = $months->search(fn($m) => $m->isSameMonth($month));
                $committed = $this->committedForMonth($couple->id, $month);
                $newCharge = $index < $installments ? $monthly : 0;
                $total = round($committed + $newCharge, 2);
                $riskPct = $totalIncome > 0 ? round($total / $totalIncome * 100) : null;

                return [
                    'month'      => $month,
                    'committed'  => $committed,
                    'new_charge' => $newCharge,
                    'total'      => $total,
                    'available'  => $totalIncome > 0 ? round($totalIncome - $total, 2) : null,
                    'risk_pct'   => $riskPct,
                    'color'      => $this->riskColor($riskPct),
                ];
            });

            $thisMonthCommitted = $simulation->first()['committed'] ?? $committed;
            $cashRiskPct = $totalIncome > 0 ? round(($thisMonthCommitted + $amount) / $totalIncome * 100) : null;
            $installRiskPct = $totalIncome > 0 ? round(($thisMonthCommitted + $monthly) / $totalIncome * 100) : null;

            $cashComparison = [
                'amount'         => $amount,
                'installments'   => $installments,
                'monthly'        => $monthly,
                'cash_impact'    => round($thisMonthCommitted + $amount, 2),
                'cash_available' => $totalIncome > 0 ? round($totalIncome - ($thisMonthCommitted + $amount), 2) : null,
                'cash_risk'      => $cashRiskPct,
                'cash_color'     => $this->riskColor($cashRiskPct),
                'install_impact' => round($thisMonthCommitted + $monthly, 2),
                'install_available' => $totalIncome > 0 ? round($totalIncome - ($thisMonthCommitted + $monthly), 2) : null,
                'install_risk'   => $installRiskPct,
                'install_color'  => $this->riskColor($installRiskPct),
            ];
        }

        return view('calculator.index', compact('totalIncome', 'simulation', 'cashComparison', 'partner', 'committed', 'available'));
    }

    private function committedForMonth(int $coupleId, Carbon $month): float
    {
        $recurringTotal = Expense::where('couple_id', $coupleId)
            ->where('is_recurring', true)
            ->whereNull('parent_id')
            ->sum('amount');

        $installments = ExpenseInstallment::whereHas('expense', fn($q) => $q->where('couple_id', $coupleId))
            ->whereNull('paid_at')
            ->whereYear('due_date', $month->year)
            ->whereMonth('due_date', $month->month)
            ->sum('amount');

        $nonInstallmentExpenses = Expense::where('couple_id', $coupleId)
            ->whereDoesntHave('installments')
            ->where('is_recurring', false)
            ->whereNull('parent_id')
            ->whereYear('billing_date', $month->year)
            ->whereMonth('billing_date', $month->month)
            ->where(function ($query) {
                $query->where('is_shared', true)
                    ->orWhereNull('paid_at');
            })
            ->sum('amount');

        return round($recurringTotal + $installments + $nonInstallmentExpenses, 2);
    }

    private function riskColor(?int $riskPct): string
    {
        return match (true) {
            $riskPct === null => 'gray',
            $riskPct < 50 => 'green',
            $riskPct < 75 => 'yellow',
            default => 'red',
        };
    }
}