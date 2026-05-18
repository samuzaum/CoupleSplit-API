<?php

namespace App\Http\Controllers;

use App\Models\ExpenseInstallment;
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

        return view('installments.index', compact('installments', 'openInstallments', 'totalOpen'));
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