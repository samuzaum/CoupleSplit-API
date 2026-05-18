<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        $partner = $couple->users()->where('users.id', '!=', $user->id)->first();

        if (!$partner) {
            return redirect()->route('dashboard')
                ->with('error', 'Seu parceiro(a) ainda não aceitou o convite.');
        }

        try {
            $month = $request->month ? Carbon::createFromFormat('Y-m', $request->month) : Carbon::now();
        } catch (\Exception $e) {
            $month = Carbon::now();
        }
        $month->startOfMonth();

        $expenses = Expense::where('couple_id', $couple->id)
            ->with(['payer', 'installments'])
            ->get();

        $expenses = $expenses->filter(fn(Expense $expense) => $this->monthlyAmount($expense, $month) > 0);

        $shared   = $expenses->where('is_shared', true);
        $personal = $expenses->where('is_shared', false);
        $sharedTotal = round($shared->sum(fn(Expense $expense) => $this->monthlyAmount($expense, $month)), 2);
        $personalTotal = round($personal
            ->where('paid_by', $user->id)
            ->sum(fn(Expense $expense) => $this->monthlyAmount($expense, $month)), 2);

        $byCategory = $shared->groupBy('category')
            ->map(fn($g) => round($g->sum(fn(Expense $expense) => $this->monthlyAmount($expense, $month)), 2))
            ->mapWithKeys(fn($total, $key) => [$key ?: 'Sem categoria' => $total])
            ->sortByDesc(fn($v) => $v);

        $byPayer = $shared->groupBy('paid_by')
            ->map(fn($g) => [
                'name'  => $g->first()->payer->name,
                'total' => round($g->sum(fn(Expense $expense) => $this->monthlyAmount($expense, $month)), 2),
                'count' => $g->count(),
            ]);

        $payments = Payment::where('couple_id', $couple->id)
            ->whereYear('payment_date', $month->year)
            ->whereMonth('payment_date', $month->month)
            ->with(['fromUser', 'toUser'])
            ->get();

        $prevMonth = $month->copy()->subMonth();
        $nextMonth = $month->copy()->addMonth();
        $isCurrentMonth = $month->isSameMonth(Carbon::now());

        return view('summary.index', compact(
            'month',
            'shared',
            'personal',
            'sharedTotal',
            'personalTotal',
            'byCategory',
            'byPayer',
            'payments',
            'partner',
            'prevMonth',
            'nextMonth',
            'isCurrentMonth'
        ));
    }

    private function monthlyAmount(Expense $expense, Carbon $month): float
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

        return $date && $date->isSameMonth($month)
            ? (float) $expense->amount
            : 0.0;
    }
}
