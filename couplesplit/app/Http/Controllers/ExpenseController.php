<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\CoupleBudget;
use App\Models\Expense;
use App\Models\ExpenseInstallment;
use App\Models\ExpenseSplit;
use App\Services\ActivityService;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $service, private ActivityService $activity) {}

    public function create()
    {
        $user       = Auth::user();
        $cards      = $user->cards;
        $couple     = $user->currentCouple();
        $customCats = $couple ? $couple->categories()->pluck('name') : collect();

        $partner        = $couple?->users()->where('users.id', '!=', $user->id)->first();
        $myIncome       = $user->monthly_income;
        $partnerIncome  = $partner?->monthly_income;
        $incomeRatio    = null;

        if ($myIncome > 0 && $partnerIncome > 0) {
            $incomeRatio = round($myIncome / ($myIncome + $partnerIncome) * 100);
        }

        $myCards      = $user->cards;
        $partnerCards = $partner?->cards ?? collect();

        return view('expenses.create', compact('myCards', 'partnerCards', 'customCats', 'myIncome', 'partnerIncome', 'incomeRatio', 'partner'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description'  => 'required|string|max:255',
            'notes'        => 'nullable|string|max:1000',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'card_id'           => 'nullable|exists:cards,id',
            'is_shared'         => 'required|boolean',
            'split_ratio'       => 'nullable|integer|min:1|max:99',
            'is_recurring'      => 'nullable|boolean',
            'installments'      => 'nullable|integer|min:1|max:48',
            'paid_installments' => 'nullable|integer|min:0|max:48',
            'category'          => 'nullable|string',
            'paid_by_partner'   => 'nullable|boolean',
        ]);

        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        $partner     = $couple->users()->where('users.id', '!=', $user->id)->first();
        $paidBy      = $request->boolean('paid_by_partner') && $partner ? $partner->id : $user->id;
        $expenseDate = Carbon::parse($request->expense_date);
        $billingDate = $this->service->calculateBillingDate($request->card_id, $expenseDate);
        $splitRatio  = $request->is_shared ? round(($request->split_ratio ?? 50) / 100, 4) : 1.0;

        $expense = Expense::create([
            'couple_id'    => $couple->id,
            'paid_by'      => $paidBy,
            'card_id'      => $request->card_id,
            'description'  => $request->description,
            'notes'        => $request->notes,
            'category'     => $request->category,
            'amount'       => $request->amount,
            'expense_date' => $expenseDate,
            'billing_date' => $billingDate,
            'is_shared'    => $request->is_shared,
            'split_ratio'  => $splitRatio,
            'is_recurring' => $request->boolean('is_recurring'),
        ]);

        $installments = (int) ($request->installments ?? 1);
        $paidInstallments = (int) ($request->paid_installments ?? 0);
        if ($paidInstallments > $installments) {
            return back()
                ->withErrors(['paid_installments' => 'As parcelas ja quitadas nao podem ser maiores que o total de parcelas.'])
                ->withInput();
        }

        if ($installments > 1) {
            $this->service->createInstallments($expense, $installments, $paidInstallments);
            $expense->load('installments');
        }

        $this->service->createBalances($expense);

        $this->activity->log($user, $couple->id, 'created', 'expense', $expense->id,
            "Criou a despesa \"{$expense->description}\" (R$ " . number_format($expense->amount, 2, ',', '.') . ")");

        $redirect = redirect()->route('expenses.index')->with('success', 'Despesa registrada com sucesso!');

        if ($expense->category && $expense->is_shared) {
            $budget = CoupleBudget::where('couple_id', $couple->id)
                ->where('category', $expense->category)
                ->first();
            if ($budget) {
                $spent = $budget->spentThisMonth();
                if ($spent > $budget->amount) {
                    $over = number_format($spent - $budget->amount, 2, ',', '.');
                    $redirect = $redirect->with('budget_warning',
                        "Atenção: o orçamento de \"{$expense->category}\" foi estourado em R$ {$over} este mês.");
                }
            }
        }

        return $redirect;
    }

    public function edit(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);

        $cards      = $user->cards;
        $customCats = $couple->categories()->pluck('name');
        $hasPayments  = $this->hasExpensePayments($expense);

        return view('expenses.edit', compact('expense', 'cards', 'hasPayments', 'customCats'));
    }

    public function update(Request $request, Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);

        $hasPayments = $this->hasExpensePayments($expense);

        if ($hasPayments) {
            $request->validate([
                'description' => 'required|string|max:255',
                'notes'       => 'nullable|string|max:1000',
            ]);
            $expense->update(['description' => $request->description, 'notes' => $request->notes]);
        } else {
            $request->validate([
                'description'  => 'required|string|max:255',
                'notes'        => 'nullable|string|max:1000',
                'amount'       => 'required|numeric|min:0.01',
                'expense_date' => 'required|date',
                'card_id'      => ['nullable', Rule::exists('cards', 'id')->where('user_id', Auth::id())],
                'is_shared'    => 'required|boolean',
                'split_ratio'  => 'nullable|integer|min:1|max:99',
                'is_recurring' => 'nullable|boolean',
                'category'     => 'nullable|string',
            ]);

            $expenseDate = Carbon::parse($request->expense_date);
            $billingDate = $this->service->calculateBillingDate($request->card_id, $expenseDate);
            $splitRatio  = $request->is_shared ? round(($request->split_ratio ?? 50) / 100, 4) : 1.0;

            DB::transaction(function () use ($request, $expense, $expenseDate, $billingDate, $splitRatio) {
                $installmentCount = $expense->installments()->count();
                // Salvar quais números de parcela estavam pagos ANTES de deletar,
                // para preservar o estado real (não apenas a contagem) ao recriar
                $paidInstallmentNumbers = $expense->installments()
                    ->whereNotNull('paid_at')
                    ->pluck('installment_number')
                    ->toArray();

                $this->deleteExpenseBalances($expense);
                ExpenseSplit::where('expense_id', $expense->id)->delete();

                $expense->update([
                    'card_id'      => $request->card_id,
                    'description'  => $request->description,
                    'notes'        => $request->notes,
                    'category'     => $request->category,
                    'amount'       => $request->amount,
                    'expense_date' => $expenseDate,
                    'billing_date' => $billingDate,
                    'is_shared'    => $request->is_shared,
                    'split_ratio'  => $splitRatio,
                    'is_recurring' => $request->boolean('is_recurring'),
                ]);

                if ($installmentCount > 1) {
                    $expense->installments()->delete();
                    // Passa os números reais das parcelas pagas em vez de só a contagem
                    $this->service->createInstallments($expense, $installmentCount, 0, $paidInstallmentNumbers);
                }

                $expense->refresh()->load('installments');
                $this->service->createBalances($expense);
            });
        }

        $this->activity->log($user, $couple->id, 'updated', 'expense', $expense->id,
            "Editou a despesa \"{$expense->description}\"");

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Despesa atualizada com sucesso!');
    }

    public function destroy(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);

        $hasPayments = $this->hasExpensePayments($expense);

        if ($hasPayments) {
            return redirect()
                ->route('expenses.index')
                ->with('error', 'Esta despesa já possui pagamentos e não pode ser excluída.');
        }

        $desc = $expense->description;

        DB::transaction(function () use ($expense) {
            $this->deleteExpenseBalances($expense);
            ExpenseSplit::where('expense_id', $expense->id)->delete();
            $expense->installments()->delete();
            $expense->delete();
        });

        $this->activity->log($user, $couple->id, 'deleted', 'expense', null,
            "Excluiu a despesa \"{$desc}\"");

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Despesa excluída com sucesso!');
    }

    public function markPaid(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);
        abort_if($expense->is_shared, 403);
        abort_if($expense->paid_by !== $user->id, 403);

        $expense->update(['paid_at' => now()]);

        $this->activity->log($user, $couple->id, 'updated', 'expense', $expense->id,
            "Marcou \"{$expense->description}\" como paga");

        return back()->with('success', 'Despesa marcada como paga.');
    }

    public function markUnpaid(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);
        abort_if($expense->is_shared, 403);
        abort_if($expense->paid_by !== $user->id, 403);

        $expense->update(['paid_at' => null]);

        return back()->with('success', 'Despesa marcada como pendente.');
    }

    public function recurring()
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        $templates = Expense::where('couple_id', $couple->id)
            ->where('is_recurring', true)
            ->whereNull('parent_id')
            ->withCount('children')
            ->orderByDesc('expense_date')
            ->get();

        return view('expenses.recurring', compact('templates'));
    }

    public function stopRecurring(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);

        $expense->update(['is_recurring' => false]);

        $this->activity->log($user, $couple->id, 'updated', 'expense', $expense->id,
            "Cancelou a recorrência de \"{$expense->description}\"");

        return back()->with('success', 'Recorrência cancelada.');
    }

    public function dispute(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);
        abort_if($expense->paid_by === $user->id, 403);
        abort_if(!$expense->is_shared, 403);

        $expense->update(['status' => 'disputed']);

        $this->activity->log($user, $couple->id, 'disputed', 'expense', $expense->id,
            "Contestou a despesa \"{$expense->description}\"");

        return back()->with('success', 'Despesa contestada.');
    }

    public function undispute(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);
        abort_if($expense->paid_by === $user->id, 403);

        $expense->update(['status' => 'approved']);

        $this->activity->log($user, $couple->id, 'approved', 'expense', $expense->id,
            "Desfez a contestação da despesa \"{$expense->description}\"");

        return back()->with('success', 'Contestação removida.');
    }

    public function index(Request $request)
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

        if (!$couple) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Você ainda não faz parte de um casal.');
        }

        $baseQuery  = $this->applyFilters(Expense::where('couple_id', $couple->id), $request);
        $total      = $baseQuery->sum('amount');
        $expenses   = $baseQuery->paginate(20)->withQueryString();
        $customCats = $couple->categories;
        $allCats    = $this->allCategories($couple);

        return view('expenses.index', [
            'expenses'      => $expenses,
            'context'       => 'all',
            'total'         => $total,
            'couple'        => $couple,
            'customCats'    => $customCats,
            'allCats'       => $allCats,
            'filters'       => $request->only('q', 'category', 'month'),
        ] + $this->balanceMaps($user, $couple));
    }

    public function couple(Request $request)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        $baseQuery  = $this->applyFilters(Expense::where('couple_id', $couple->id)->where('is_shared', true), $request);
        $total      = $baseQuery->sum('amount');
        $expenses   = $baseQuery->paginate(20)->withQueryString();
        $customCats = $couple->categories;
        $allCats    = $this->allCategories($couple);

        return view('expenses.index', [
            'expenses'   => $expenses,
            'context'    => 'couple',
            'total'      => $total,
            'couple'     => $couple,
            'customCats' => $customCats,
            'allCats'    => $allCats,
            'filters'    => $request->only('q', 'category', 'month'),
        ] + $this->balanceMaps($user, $couple));
    }

    public function personal(Request $request)
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        $baseQuery  = $this->applyFilters(
            Expense::where('couple_id', $couple->id)->where('is_shared', false)->where('paid_by', $user->id),
            $request
        );
        $total      = $baseQuery->sum('amount');
        $expenses   = $baseQuery->paginate(20)->withQueryString();
        $customCats = $couple->categories;
        $allCats    = $this->allCategories($couple);

        return view('expenses.index', [
            'expenses'   => $expenses,
            'context'    => 'personal',
            'total'      => $total,
            'couple'     => $couple,
            'customCats' => $customCats,
            'allCats'    => $allCats,
            'filters'    => $request->only('q', 'category', 'month'),
        ] + $this->balanceMaps($user, $couple));
    }


    private function hasExpensePayments(Expense $expense): bool
    {
        $installmentIds = $expense->installments()->pluck('id');

        return Balance::where('origin', 'expense')
            ->where('used_amount', '>', 0)
            ->where(function ($query) use ($expense, $installmentIds) {
                $query->where(function ($expenseBalance) use ($expense) {
                    $expenseBalance->where('origin_table', 'expenses')
                        ->where('origin_id', $expense->id);
                })->orWhere(function ($installmentBalance) use ($installmentIds) {
                    $installmentBalance->where('origin_table', 'expense_installments')
                        ->whereIn('origin_id', $installmentIds);
                });
            })
            ->exists();
    }

    private function deleteExpenseBalances(Expense $expense): void
    {
        $installmentIds = $expense->installments()->pluck('id');

        Balance::where('origin', 'expense')
            ->where(function ($query) use ($expense, $installmentIds) {
                $query->where(function ($expenseBalance) use ($expense) {
                    $expenseBalance->where('origin_table', 'expenses')
                        ->where('origin_id', $expense->id);
                })->orWhere(function ($installmentBalance) use ($installmentIds) {
                    $installmentBalance->where('origin_table', 'expense_installments')
                        ->whereIn('origin_id', $installmentIds);
                });
            })
            ->delete();
    }

    private function balanceMaps($user, $couple): array
    {
        $partner = $couple->users()->where('users.id', '!=', $user->id)->first();
        if (!$partner) {
            return ['myCredits' => collect(), 'myDebits' => collect()];
        }

        $myCredits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'credit')
            ->where('origin', 'expense')
            ->get();

        $myDebits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->where('origin', 'expense')
            ->get();

        $myCredits = $this->balancesByExpense($myCredits);
        $myDebits = $this->balancesByExpense($myDebits);

        return compact('myCredits', 'myDebits');
    }

    private function balancesByExpense($balances)
    {
        $installmentIds = $balances
            ->where('origin_table', 'expense_installments')
            ->pluck('origin_id')
            ->unique();

        $installmentExpenseIds = $installmentIds->isEmpty()
            ? collect()
            : ExpenseInstallment::whereIn('id', $installmentIds)->pluck('expense_id', 'id');

        return $balances
            ->groupBy(function ($balance) use ($installmentExpenseIds) {
                return $balance->origin_table === 'expense_installments'
                    ? $installmentExpenseIds->get($balance->origin_id)
                    : $balance->origin_id;
            })
            ->filter(fn($group, $expenseId) => $expenseId !== null)
            ->map(function ($group) {
                return (object) [
                    'amount'      => $group->sum(fn($balance) => (float) $balance->amount),
                    'used_amount' => $group->sum(fn($balance) => (float) $balance->used_amount),
                ];
            });
    }

    private function applyFilters($query, Request $request)
    {
        if ($q = $request->q) {
            $query->where('description', 'like', "%{$q}%");
        }

        if ($cat = $request->category) {
            $query->where('category', $cat);
        }

        if ($month = $request->month) {
            $parts = explode('-', $month);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $query->whereYear('expense_date', $parts[0])->whereMonth('expense_date', $parts[1]);
            }
        }

        return $query->orderByDesc('expense_date');
    }

    private function allCategories($couple): array
    {
        return array_merge(
            Expense::CATEGORIES,
            $couple->categories()->pluck('name')->toArray()
        );
    }
}
