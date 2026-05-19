<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseInstallment;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalculatorController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request)
    {
        $user    = Auth::user();
        $couple  = $user->currentCoupleOrFail();
        $partner = $couple->users()->where('users.id', '!=', $user->id)->first();

        $myIncome     = (float) ($user->monthly_income ?? 0);
        $totalIncome  = $myIncome + (float) ($partner?->monthly_income ?? 0);
        $currentMonth = Carbon::now()->startOfMonth();

        // dívida acumulada com o parceiro — já está comprometida
        $netDebt = $partner ? max(0, $this->paymentService->totalOpenDebit($user, $partner)) : 0;

        $committed = $this->myCommittedForMonth($user, $couple, $currentMonth) + $netDebt;
        $available = $myIncome > 0 ? round($myIncome - $committed, 2) : null;

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

            $simulation = $months->map(function ($month) use ($user, $couple, $monthly, $installments, $myIncome, $netDebt, $months) {
                $index      = $months->search(fn($m) => $m->isSameMonth($month));
                // dívida só pesa no mês atual, nos futuros já está quitada
                $debt       = $index === 0 ? $netDebt : 0;
                $committed  = $this->myCommittedForMonth($user, $couple, $month) + $debt;
                $newCharge  = $index < $installments ? $monthly : 0;
                $total      = round($committed + $newCharge, 2);
                $riskPct    = $myIncome > 0 ? round($total / $myIncome * 100) : null;

                return [
                    'month'      => $month,
                    'committed'  => $committed,
                    'new_charge' => $newCharge,
                    'total'      => $total,
                    'available'  => $myIncome > 0 ? round($myIncome - $total, 2) : null,
                    'risk_pct'   => $riskPct,
                    'color'      => $this->riskColor($riskPct),
                ];
            });

            $thisMonthCommitted = $simulation->first()['committed'] ?? $committed;
            $cashRiskPct    = $myIncome > 0 ? round(($thisMonthCommitted + $amount)   / $myIncome * 100) : null;
            $installRiskPct = $myIncome > 0 ? round(($thisMonthCommitted + $monthly)  / $myIncome * 100) : null;

            $cashComparison = [
                'amount'            => $amount,
                'installments'      => $installments,
                'monthly'           => $monthly,
                'cash_impact'       => round($thisMonthCommitted + $amount, 2),
                'cash_available'    => $myIncome > 0 ? round($myIncome - ($thisMonthCommitted + $amount), 2) : null,
                'cash_risk'         => $cashRiskPct,
                'cash_color'        => $this->riskColor($cashRiskPct),
                'install_impact'    => round($thisMonthCommitted + $monthly, 2),
                'install_available' => $myIncome > 0 ? round($myIncome - ($thisMonthCommitted + $monthly), 2) : null,
                'install_risk'      => $installRiskPct,
                'install_color'     => $this->riskColor($installRiskPct),
            ];
        }

        return view('calculator.index', compact(
            'myIncome', 'totalIncome', 'simulation', 'cashComparison',
            'partner', 'committed', 'available', 'netDebt'
        ));
    }

    // Comprometido do MÊS na perspectiva do usuário:
    // - sua parte das despesas compartilhadas (split_ratio)
    // - suas despesas pessoais não pagas
    private function myCommittedForMonth($user, $couple, Carbon $month): float
    {
        // parcelas vencendo no mês — calcula a parte do usuário
        $installmentExpenses = Expense::where('couple_id', $couple->id)
            ->whereHas('installments', fn($q) =>
                $q->whereNull('paid_at')
                  ->whereYear('due_date', $month->year)
                  ->whereMonth('due_date', $month->month)
            )
            ->with(['installments' => fn($q) =>
                $q->whereNull('paid_at')
                  ->whereYear('due_date', $month->year)
                  ->whereMonth('due_date', $month->month)
            ])
            ->get();

        $installmentCost = $installmentExpenses->sum(function ($expense) use ($user) {
            $instAmount = $expense->installments->sum('amount');
            if (!$expense->is_shared) return $instAmount; // pessoal = 100%
            $ratio = $expense->split_ratio ?? 0.5;
            return $expense->paid_by === $user->id
                ? $instAmount * $ratio               // eu paguei → minha parte
                : $instAmount * (1 - $ratio);        // parceiro pagou → minha parte
        });

        // despesas avulsas (sem parcelamento) — recorrentes ou do mês
        $singleExpenses = Expense::where('couple_id', $couple->id)
            ->whereDoesntHave('installments')
            ->whereNull('parent_id')
            ->where(function ($q) use ($month, $user) {
                $q->where(function ($recurring) {
                    $recurring->where('is_recurring', true);
                })->orWhere(function ($monthly) use ($month) {
                    $monthly->where('is_recurring', false)
                            ->whereYear('billing_date', $month->year)
                            ->whereMonth('billing_date', $month->month);
                });
            })
            ->where(function ($q) use ($user) {
                // shared: sempre inclui; pessoal: só as não pagas
                $q->where('is_shared', true)
                  ->orWhere(fn($p) => $p->where('is_shared', false)
                                        ->where('paid_by', $user->id)
                                        ->whereNull('paid_at'));
            })
            ->get();

        $singleCost = $singleExpenses->sum(function ($expense) use ($user) {
            if (!$expense->is_shared) return (float) $expense->amount;
            $ratio = $expense->split_ratio ?? 0.5;
            return $expense->paid_by === $user->id
                ? $expense->amount * $ratio
                : $expense->amount * (1 - $ratio);
        });

        return round($installmentCost + $singleCost, 2);
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