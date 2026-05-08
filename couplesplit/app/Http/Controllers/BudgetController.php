<?php

namespace App\Http\Controllers;

use App\Models\CoupleBudget;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'amount'   => 'required|numeric|min:1',
        ]);

        $couple = Auth::user()->couples()->firstOrFail();

        $couple->budgets()->updateOrCreate(
            ['category' => $request->category],
            ['amount'   => $request->amount]
        );

        return back()->with('budget_success', 'Orçamento salvo!');
    }

    public function update(Request $request, CoupleBudget $budget)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        $couple = Auth::user()->couples()->firstOrFail();
        abort_if($budget->couple_id !== $couple->id, 403);

        $budget->update(['amount' => $request->amount]);

        return back()->with('budget_success', 'Orçamento atualizado!');
    }

    public function destroy(CoupleBudget $budget)
    {
        $couple = Auth::user()->couples()->firstOrFail();
        abort_if($budget->couple_id !== $couple->id, 403);

        $budget->delete();

        return back()->with('budget_success', 'Orçamento removido.');
    }
}
