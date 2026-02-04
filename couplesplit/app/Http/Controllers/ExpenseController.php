<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /* =========================
     * CREATE
     * ========================= */
    public function create()
    {
        $cards = Auth::user()->cards;

        return view('expenses.create', compact('cards'));
    }

    /* =========================
     * STORE
     * ========================= */
    public function store(Request $request)
    {
        $request->validate([
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'card_id'      => 'nullable|exists:cards,id',
            'is_shared'    => 'required|boolean',
        ]);

        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $expenseDate = Carbon::parse($request->expense_date);
        $billingDate = $this->calculateBillingDate(
            $request->card_id,
            $expenseDate
        );

        $expense = Expense::create([
            'couple_id'    => $couple->id,
            'paid_by'      => $user->id,
            'card_id'      => $request->card_id,
            'description'  => $request->description,
            'amount'       => $request->amount,
            'expense_date' => $expenseDate,
            'billing_date' => $billingDate,
            'is_shared'    => $request->is_shared,
        ]);

        // 🔑 ÚNICA lógica responsável por criar splits
        $this->createSplits($expense);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Despesa registrada com sucesso!');
    }

    /* =========================
     * SPLITS (REGRA DE NEGÓCIO)
     * ========================= */
    private function createSplits(Expense $expense): void
    {
        $users = $expense->couple->users;

        // ➤ Despesa pessoal → tudo para quem pagou
        if (!$expense->is_shared) {
            $expense->splits()->create([
                'user_id' => $expense->paid_by,
                'amount'  => $expense->amount,
                'is_paid' => true,
            ]);

            return;
        }

        // ➤ Despesa compartilhada → divide igualmente
        $perUser = round(
            $expense->amount / $users->count(),
            2
        );

        foreach ($users as $user) {
            $expense->splits()->create([
                'user_id' => $user->id,
                'amount'  => $perUser,
                'is_paid' => $user->id === $expense->paid_by,
            ]);
        }
    }

    /* =========================
     * INDEX (TODAS)
     * ========================= */
    public function index()
    {
        $user   = Auth::user();
        $couple = $user->couples()->first();

        if (!$couple) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Você ainda não faz parte de um casal.');
        }

        $expenses = Expense::where('couple_id', $couple->id)
            ->orderByDesc('expense_date')
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
            'context'  => 'all',
            'total'    => $expenses->sum('amount'),
        ]);
    }

    /* =========================
     * INDEX – CASAL
     * ========================= */
    public function couple()
    {
        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $expenses = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->orderByDesc('expense_date')
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
            'context'  => 'couple',
            'total'    => $expenses->sum('amount'),
        ]);
    }

    /* =========================
     * INDEX – PESSOAL
     * ========================= */
    public function personal()
    {
        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $expenses = Expense::where('couple_id', $couple->id)
            ->where('is_shared', false)
            ->where('paid_by', $user->id)
            ->orderByDesc('expense_date')
            ->get();

        return view('expenses.index', [
            'expenses' => $expenses,
            'context'  => 'personal',
            'total'    => $expenses->sum('amount'),
        ]);
    }

    /* =========================
     * BILLING DATE
     * ========================= */
    private function calculateBillingDate($cardId, Carbon $expenseDate): Carbon
    {
        if (!$cardId) {
            return $expenseDate;
        }

        $card = Card::findOrFail($cardId);

        if ($card->type === 'debit') {
            return $expenseDate;
        }

        if ($expenseDate->day <= $card->closing_day) {
            return $expenseDate->copy()->startOfMonth();
        }

        return $expenseDate->copy()->addMonth()->startOfMonth();
    }
}
