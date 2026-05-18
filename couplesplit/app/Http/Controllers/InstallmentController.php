<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseInstallment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class InstallmentController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        $installments = ExpenseInstallment::whereHas('expense', fn($q) => $q->where('couple_id', $couple->id))
            ->with('expense.payer')
            ->orderBy('due_date')
            ->get();

        $openInstallments = $installments->whereNull('paid_at');
        $totalOpen = $openInstallments->sum('amount');

        $faturas = $this->nextFaturasByCard($user, $couple);

        return view('installments.index', compact('installments', 'openInstallments', 'totalOpen', 'faturas'));
    }

    private function nextFaturasByCard($user, $couple): Collection
    {
        $today = Carbon::now();

        return $user->cards()
            ->where('type', 'credit')
            ->get()
            ->map(function ($card) use ($couple, $today) {
                $closingDay = $card->closing_day ?? 1;

                $nextClosing = $today->day < $closingDay
                    ? $today->copy()->setDay($closingDay)
                    : $today->copy()->addMonthNoOverflow()->setDay($closingDay);

                $installments = ExpenseInstallment::whereHas('expense', fn($q) =>
                    $q->where('couple_id', $couple->id)->where('card_id', $card->id)
                )
                ->whereNull('paid_at')
                ->whereYear('due_date', $nextClosing->year)
                ->whereMonth('due_date', $nextClosing->month)
                ->with('expense')
                ->orderBy('due_date')
                ->orderBy('id')
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

                $total = round($installments->sum('amount') + $singleExpenses->sum('amount'), 2);

                return (object) [
                    'card'            => $card,
                    'next_closing'    => $nextClosing,
                    'total'           => $total,
                    'installments'    => $installments,
                    'single_expenses' => $singleExpenses,
                ];
            })
            ->filter(fn($f) => $f->total > 0);
    }

    public function markPaid(ExpenseInstallment $installment)
    {
        $this->authorizeInstallment($installment);
        abort_if($installment->due_date->isFuture(), 403);

        $installment->update(['paid_at' => now()]);

        return back()->with('success', 'Parcela marcada como paga.');
    }

    public function markUnpaid(ExpenseInstallment $installment)
    {
        $this->authorizeInstallment($installment);

        $installment->update(['paid_at' => null]);

        return back()->with('success', 'Parcela reaberta.');
    }

    private function authorizeInstallment(ExpenseInstallment $installment): void
    {
        $couple = Auth::user()->currentCoupleOrFail();
        abort_if($installment->expense->couple_id !== $couple->id, 403);
    }
}