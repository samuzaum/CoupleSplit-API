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

        $month   = $request->month ? Carbon::createFromFormat('Y-m', $request->month) : Carbon::now();
        $month->startOfMonth();

        $expenses = Expense::where('couple_id', $couple->id)
            ->whereYear('expense_date', $month->year)
            ->whereMonth('expense_date', $month->month)
            ->get();

        $shared   = $expenses->where('is_shared', true);
        $personal = $expenses->where('is_shared', false);

        $byCategory = $shared->groupBy('category')
            ->map(fn($g) => round($g->sum('amount'), 2))
            ->mapWithKeys(fn($total, $key) => [$key ?: 'Sem categoria' => $total])
            ->sortByDesc(fn($v) => $v);

        $byPayer = $shared->groupBy('paid_by')
            ->map(fn($g) => [
                'name'  => $g->first()->payer->name,
                'total' => round($g->sum('amount'), 2),
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
            'byCategory',
            'byPayer',
            'payments',
            'partner',
            'prevMonth',
            'nextMonth',
            'isCurrentMonth'
        ));
    }
}
