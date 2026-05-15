<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $service) {}

    public function index(): JsonResponse
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

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
        $user = Auth::user();

        $validated = $request->validate([
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'card_id'      => ['nullable', Rule::exists('cards', 'id')->where('user_id', $user->id)],
            'is_shared'    => 'required|boolean',
            'installments' => 'nullable|integer|min:1|max:48',
            'paid_installments' => 'nullable|integer|min:0|max:48',
        ]);

        $couple = $user->currentCoupleOrFail();

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

        $installments = (int) ($validated['installments'] ?? 1);
        $paidInstallments = (int) ($validated['paid_installments'] ?? 0);
        if ($paidInstallments > $installments) {
            return response()->json([
                'message' => 'As parcelas ja quitadas nao podem ser maiores que o total de parcelas.',
                'errors' => [
                    'paid_installments' => ['As parcelas ja quitadas nao podem ser maiores que o total de parcelas.'],
                ],
            ], 422);
        }

        if ($installments > 1) {
            $this->service->createInstallments($expense, $installments, $paidInstallments);
            $expense->load('installments');
        }

        $this->service->createBalances($expense);

        return response()->json($expense->load('installments'), 201);
    }
}
