<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function create()
    {
        $cards = Auth::user()->cards;

        return view('expenses.create', compact('cards'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description'   => 'required|string|max:255',
            'amount'        => 'required|numeric|min:0.01',
            'expense_date'  => 'required|date',
            'card_id'       => 'nullable|exists:cards,id',
            'is_shared'     => 'required|boolean',
        ]);

        $expenseDate = Carbon::parse($request->expense_date);
        $billingDate = $this->calculateBillingDate(
            $request->card_id,
            $expenseDate
        );

        Expense::create([
            'couple_id'    => Auth::user()->couples()->first()->id,
            'paid_by'      => Auth::id(),
            'card_id'      => $request->card_id,
            'description'  => $request->description,
            'amount'       => $request->amount,
            'expense_date' => $expenseDate,
            'billing_date' => $billingDate,
            'is_shared'    => $request->is_shared ?? true,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Despesa registrada com sucesso!');
    }

    private function calculateBillingDate($cardId, Carbon $expenseDate): Carbon
    {
        // sem cartão → débito / dinheiro / pix
        if (!$cardId) {
            return $expenseDate;
        }

        $card = Card::findOrFail($cardId);

        // débito → impacto imediato
        if ($card->type === 'debit') {
            return $expenseDate;
        }

        // crédito → depende do fechamento
        if ($expenseDate->day <= $card->closing_day) {
            return $expenseDate->copy()->startOfMonth();
        }

        return $expenseDate->copy()->addMonth()->startOfMonth();
    }
}
