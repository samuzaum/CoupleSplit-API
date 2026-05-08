<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $service) {}

    public function index(): JsonResponse
    {
        $user   = Auth::user();
        $couple = $user->couples()->first();

        if (!$couple) {
            return response()->json(['message' => 'Usuário não faz parte de um casal.'], 422);
        }

        $expenses = Expense::where('couple_id', $couple->id)
            ->orderByDesc('expense_date')
            ->get();

        return response()->json($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'card_id'      => 'nullable|exists:cards,id',
            'is_shared'    => 'required|boolean',
            'installments' => 'nullable|integer|min:1|max:48',
        ]);

        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $expenseDate = Carbon::parse($validated['expense_date']);
        $billingDate = $this->service->calculateBillingDate($validated['card_id'] ?? null, $expenseDate);

        $expense = Expense::create([
            'couple_id'    => $couple->id,
            'paid_by'      => $user->id,
            'card_id'      => $validated['card_id'] ?? null,
            'description'  => $validated['description'],
            'amount'       => $validated['amount'],
            'expense_date' => $expenseDate,
            'billing_date' => $billingDate,
            'is_shared'    => $validated['is_shared'],
        ]);

        $this->service->createBalances($expense);

        $installments = (int) ($validated['installments'] ?? 1);
        if ($installments > 1) {
            $this->service->createInstallments($expense, $installments);
        }

        return response()->json($expense->load('installments'), 201);
    }
}
