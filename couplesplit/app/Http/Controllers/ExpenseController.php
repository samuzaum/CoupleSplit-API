<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\CoupleBudget;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Services\ActivityService;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $service, private ActivityService $activity) {}

    public function create()
    {
        $user       = Auth::user();
        $cards      = $user->cards;
        $couple     = $user->couples()->first();
        $customCats = $couple ? $couple->categories()->pluck('name') : collect();

        $partner        = $couple?->users()->where('users.id', '!=', $user->id)->first();
        $myIncome       = $user->monthly_income;
        $partnerIncome  = $partner?->monthly_income;
        $incomeRatio    = null;

        if ($myIncome > 0 && $partnerIncome > 0) {
            $incomeRatio = round($myIncome / ($myIncome + $partnerIncome) * 100);
        }

        return view('expenses.create', compact('cards', 'customCats', 'myIncome', 'partnerIncome', 'incomeRatio', 'partner'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'card_id'      => 'nullable|exists:cards,id',
            'is_shared'    => 'required|boolean',
            'split_ratio'  => 'nullable|integer|min:1|max:99',
            'is_recurring' => 'nullable|boolean',
            'installments' => 'nullable|integer|min:1|max:48',
            'category'     => 'nullable|string',
        ]);

        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $expenseDate = Carbon::parse($request->expense_date);
        $billingDate = $this->service->calculateBillingDate($request->card_id, $expenseDate);
        $splitRatio  = $request->is_shared ? round(($request->split_ratio ?? 50) / 100, 4) : 1.0;

        $expense = Expense::create([
            'couple_id'    => $couple->id,
            'paid_by'      => $user->id,
            'card_id'      => $request->card_id,
            'description'  => $request->description,
            'category'     => $request->category,
            'amount'       => $request->amount,
            'expense_date' => $expenseDate,
            'billing_date' => $billingDate,
            'is_shared'    => $request->is_shared,
            'split_ratio'  => $splitRatio,
            'is_recurring' => $request->boolean('is_recurring'),
        ]);

        $this->service->createBalances($expense);

        $installments = (int) ($request->installments ?? 1);
        if ($installments > 1) {
            $this->service->createInstallments($expense, $installments);
        }

        $this->activity->log($user, $couple->id, 'created', 'expense', $expense->id,
            "Criou a despesa \"{$expense->description}\" (R$ " . number_format($expense->amount, 2, ',', '.') . ")");

        $redirect = redirect()->route('expenses.index')->with('success', 'Despesa registrada com sucesso!');

        if ($expense->category && $expense->is_shared) {
            $budget = CoupleBudget::where('couple_id', $couple->id)
                ->where('category', $expense->category)
                ->first();
            if ($budget) {
                $spent = Expense::where('couple_id', $couple->id)
                    ->where('is_shared', true)
                    ->where('category', $expense->category)
                    ->whereYear('expense_date', Carbon::now()->year)
                    ->whereMonth('expense_date', Carbon::now()->month)
                    ->sum('amount');
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
        $couple = $user->couples()->firstOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);

        $cards      = $user->cards;
        $customCats = $couple->categories()->pluck('name');
        $hasPayments  = Balance::where('origin', 'expense')
            ->where('origin_id', $expense->id)
            ->where('used_amount', '>', 0)
            ->exists();

        return view('expenses.edit', compact('expense', 'cards', 'hasPayments', 'customCats'));
    }

    public function update(Request $request, Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);

        $hasPayments = Balance::where('origin', 'expense')
            ->where('origin_id', $expense->id)
            ->where('used_amount', '>', 0)
            ->exists();

        if ($hasPayments) {
            $request->validate(['description' => 'required|string|max:255']);
            $expense->update(['description' => $request->description]);
        } else {
            $request->validate([
                'description'  => 'required|string|max:255',
                'amount'       => 'required|numeric|min:0.01',
                'expense_date' => 'required|date',
                'card_id'      => 'nullable|exists:cards,id',
                'is_shared'    => 'required|boolean',
                'split_ratio'  => 'nullable|integer|min:1|max:99',
                'is_recurring' => 'nullable|boolean',
                'category'     => 'nullable|string',
            ]);

            $expenseDate = Carbon::parse($request->expense_date);
            $billingDate = $this->service->calculateBillingDate($request->card_id, $expenseDate);
            $splitRatio  = $request->is_shared ? round(($request->split_ratio ?? 50) / 100, 4) : 1.0;

            Balance::where('origin', 'expense')->where('origin_id', $expense->id)->delete();
            ExpenseSplit::where('expense_id', $expense->id)->delete();

            $expense->update([
                'card_id'      => $request->card_id,
                'description'  => $request->description,
                'category'     => $request->category,
                'amount'       => $request->amount,
                'expense_date' => $expenseDate,
                'billing_date' => $billingDate,
                'is_shared'    => $request->is_shared,
                'split_ratio'  => $splitRatio,
                'is_recurring' => $request->boolean('is_recurring'),
            ]);

            $expense->refresh();
            $this->service->createBalances($expense);
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
        $couple = $user->couples()->firstOrFail();

        abort_if($expense->couple_id !== $couple->id, 403);

        $hasPayments = Balance::where('origin', 'expense')
            ->where('origin_id', $expense->id)
            ->where('used_amount', '>', 0)
            ->exists();

        if ($hasPayments) {
            return redirect()
                ->route('expenses.index')
                ->with('error', 'Esta despesa já possui pagamentos e não pode ser excluída.');
        }

        $desc = $expense->description;
        Balance::where('origin', 'expense')->where('origin_id', $expense->id)->delete();
        ExpenseSplit::where('expense_id', $expense->id)->delete();
        $expense->installments()->delete();
        $expense->delete();

        $this->activity->log($user, $couple->id, 'deleted', 'expense', null,
            "Excluiu a despesa \"{$desc}\"");

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Despesa excluída com sucesso!');
    }

    public function dispute(Expense $expense)
    {
        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

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
        $couple = $user->couples()->firstOrFail();

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
        $couple = $user->couples()->first();

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
        $couple = $user->couples()->firstOrFail();

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
        $couple = $user->couples()->firstOrFail();

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
            ->get()->keyBy('origin_id');

        $myDebits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->where('origin', 'expense')
            ->get()->keyBy('origin_id');

        return compact('myCredits', 'myDebits');
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
            [$y, $m] = explode('-', $month);
            $query->whereYear('expense_date', $y)->whereMonth('expense_date', $m);
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
