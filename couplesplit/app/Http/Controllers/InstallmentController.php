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
            ->whereNull('paid_at')
            ->with('expense.payer')
            ->orderBy('due_date')
            ->get();

        $totalOpen = $installments->sum('amount');

        return view('installments.index', compact('installments', 'totalOpen'));
    }
}
