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
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();
        $partner = $couple->users()->where('users.id', '!=', $user->id)->first();

        $totalIncome = ($user->monthly_income ?? 0) + ($partner?->monthly_income ?? 0);

        // capacidade disponível atual
        $recurringTotal = Expense::where('couple_id', $couple->id)
            ->where('is_recurring', true)
            ->whereNull('parent_id')
            ->sum('amount');

        $installmentsThisMonth = ExpenseInstallment::whereHas('expense', fn($q) => $q->where('couple_id', $couple->id))
            ->whereNull('paid_at')
            ->whereYear('due_date', Carbon::now()->year)
            ->whereMonth('due_date', Carbon::now()->month)
            ->sum('amount');

        $committed = round($recurringTotal + $installmentsThisMonth, 2);
        $available = $totalIncome > 0 ? round($totalIncome - $committed, 2) : null;

        $simulation   = null;
        $cashComparison = null;

        if ($request->filled('amount') && $request->filled('installments')) {
            $request->validate([
                'amount'       => 'required|numeric|min:0.01',
                'installments' => 'required|integer|min:1|max:48',
            ]);

            $amount       = (float) $request->amount;
            $installments = (int) $request->installments;
            $monthly      = round($amount / $installments, 2);

            // compromissos fixos já existentes por mês (próximos 12 meses)
            $months = collect();
            for ($i = 0; $i < 12; $i++) {
                $months->push(Carbon::now()->startOfMonth()->addMonths($i));
            }

            // parcelas em andamento não pagas
            $activeInstallments = ExpenseInstallment::whereHas('expense', fn($q) => $q->where('couple_id', $couple->id))
                ->whereNull('paid_at')
                ->get();

            $simulation = $months->map(function ($month) use ($recurringTotal, $activeInstallments, $monthly, $installments, $totalIncome, $months) {
                $index = $months->search(fn($m) => $m->isSameMonth($month));

                $installmentsDue = $activeInstallments->filter(
                    fn($i) => $i->due_date->isSameMonth($month)
                )->sum('amount');

                $committed  = round($recurringTotal + $installmentsDue, 2);
                $newCharge  = $index < $installments ? $monthly : 0;
                $total      = round($committed + $newCharge, 2);

                $riskPct = $totalIncome > 0 ? round($total / $totalIncome * 100) : null;

                $color = match(true) {
                    $riskPct === null         => 'gray',
                    $riskPct < 50             => 'green',
                    $riskPct < 75             => 'yellow',
                    default                   => 'red',
                };

                return [
                    'month'       => $month,
                    'committed'   => $committed,
                    'new_charge'  => $newCharge,
                    'total'       => $total,
                    'risk_pct'    => $riskPct,
                    'color'       => $color,
                ];
            });

            // comparador à vista vs parcelado
            $thisMonthCommitted = $simulation->first()['committed'] ?? $committed;
            $cashRiskPct  = $totalIncome > 0 ? round(($thisMonthCommitted + $amount) / $totalIncome * 100) : null;
            $installRiskPct = $totalIncome > 0 ? round(($thisMonthCommitted + $monthly) / $totalIncome * 100) : null;

            $cashComparison = [
                'amount'        => $amount,
                'installments'  => $installments,
                'monthly'       => $monthly,
                'cash_impact'   => round($thisMonthCommitted + $amount, 2),
                'cash_risk'     => $cashRiskPct,
                'cash_color'    => $cashRiskPct === null ? 'gray' : ($cashRiskPct < 50 ? 'green' : ($cashRiskPct < 75 ? 'yellow' : 'red')),
                'install_impact' => round($thisMonthCommitted + $monthly, 2),
                'install_risk'  => $installRiskPct,
                'install_color' => $installRiskPct === null ? 'gray' : ($installRiskPct < 50 ? 'green' : ($installRiskPct < 75 ? 'yellow' : 'red')),
            ];
        }

        return view('calculator.index', compact('totalIncome', 'simulation', 'cashComparison', 'partner', 'committed', 'available'));
    }
}
