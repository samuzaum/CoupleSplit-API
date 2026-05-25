<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseInstallment;
use App\Models\Payment;
use App\Services\ActivityService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service, private ActivityService $activity) {}

    public function create()
    {
        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        $partner = $couple->users()
            ->where('users.id', '!=', $user->id)
            ->first();

        if (!$partner) {
            return redirect()->route('dashboard')
                ->with('error', 'Seu parceiro(a) ainda não aceitou o convite.');
        }

        $openDebits = $this->service->openBalances($user, $partner)
            ->where('type', 'debit')
            ->orderBy('created_at')
            ->get()
            ->map(function ($balance) {
                $balance->remaining = round($balance->amount - $balance->used_amount, 2);
                $balance->label     = $this->service->descriptionForBalance($balance) ?? 'Saldo';
                return $balance;
            });

        $personalUnpaid = Expense::where('couple_id', $couple->id)
            ->where('is_shared', false)
            ->where('paid_by', $user->id)
            ->whereDoesntHave('installments')
            ->whereNull('paid_at')
            ->orderBy('expense_date')
            ->get();

        $personalInstallments = ExpenseInstallment::whereHas('expense', function ($query) use ($couple, $user) {
                $query->where('couple_id', $couple->id)
                    ->where('paid_by', $user->id)
                    ->where('is_shared', false);
            })
            ->whereNull('paid_at')
            ->whereDate('due_date', '<=', Carbon::now()->endOfMonth())
            ->with('expense.card')
            ->orderBy('due_date')
            ->get();

        return view('payments.create', [
            'partner'              => $partner,
            'openDebits'           => $openDebits,
            'personalUnpaid'       => $personalUnpaid,
            'personalInstallments' => $personalInstallments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_type'       => 'required|in:partner,personal',
            'amount'             => 'required_if:payment_type,partner|nullable|numeric|min:0.01',
            'expense_ids'        => 'nullable|array',
            'expense_ids.*'      => 'exists:expenses,id',
            'installment_ids'    => 'nullable|array',
            'installment_ids.*'  => 'exists:expense_installments,id',
        ]);

        $user   = Auth::user();
        $couple = $user->currentCoupleOrFail();

        if ($request->payment_type === 'partner') {
            $partner = $couple->users()->where('users.id', '!=', $user->id)->first();
            if (!$partner) {
                return redirect()->route('dashboard')
                    ->with('error', 'Seu parceiro(a) ainda não aceitou o convite.');
            }

            $this->service->process($user, $couple, $partner, (float) $request->amount);

            $formatted = number_format((float) $request->amount, 2, ',', '.');
            $this->activity->log($user, $couple->id, 'payment', 'payment', null,
                "Registrou pagamento de R$ {$formatted} para {$partner->name}");

            return redirect()->route('dashboard')->with('success', 'Pagamento registrado com sucesso!');
        }

        $expenseIds = $request->expense_ids ?? [];
        $installmentIds = $request->installment_ids ?? [];

        if (empty($expenseIds) && empty($installmentIds)) {
            return back()->withErrors(['payment' => 'Selecione pelo menos uma despesa ou parcela.']);
        }

        Expense::whereIn('id', $expenseIds)
            ->where('couple_id', $couple->id)
            ->where('paid_by', $user->id)
            ->where('is_shared', false)
            ->whereDoesntHave('installments')
            ->update(['paid_at' => Carbon::now()]);

        ExpenseInstallment::whereIn('id', $installmentIds)
            ->whereHas('expense', function ($query) use ($couple, $user) {
                $query->where('couple_id', $couple->id)
                    ->where('paid_by', $user->id)
                    ->where('is_shared', false);
            })
            ->whereDate('due_date', '<=', Carbon::now()->endOfMonth())
            ->update(['paid_at' => Carbon::now()]);

        $count = count($expenseIds) + count($installmentIds);
        $this->activity->log($user, $couple->id, 'payment', 'expense', null,
            "Marcou {$count} item(ns) pessoal(is) como pagos");

        return redirect()->route('payments.create')->with('success', 'Itens pessoais marcados como pagos!');
    }

    public function index(Request $request)
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

        if (!$couple) {
            return redirect()->route('dashboard')
                ->with('error', 'Você ainda não faz parte de um casal.');
        }

        $query = Payment::where('couple_id', $couple->id)
            ->with(['fromUser', 'toUser', 'items']);

        $monthFilter = null;
        if ($request->filled('month')) {
            try {
                $monthFilter = Carbon::createFromFormat('Y-m', $request->month);
                $query->whereYear('payment_date', $monthFilter->year)
                      ->whereMonth('payment_date', $monthFilter->month);
            } catch (\Exception $e) {
                $monthFilter = null;
            }
        }

        $directionFilter = $request->input('direction');
        if ($directionFilter === 'sent') {
            $query->where('from_user_id', $user->id);
        } elseif ($directionFilter === 'received') {
            $query->where('to_user_id', $user->id);
        }

        $payments = $query->orderByDesc('payment_date')->orderByDesc('created_at')->get();

        return view('payments.history', compact('payments', 'monthFilter', 'directionFilter'));
    }

    public function update(Request $request, Payment $payment)
    {
        $couple = Auth::user()->currentCoupleOrFail();
        abort_if($payment->couple_id !== $couple->id, 403);

        $request->validate([
            'payment_date' => 'required|date',
            'note'         => 'nullable|string|max:255',
        ]);

        $payment->update([
            'payment_date' => $request->payment_date,
            'note'         => $request->note,
        ]);

        return back()->with('success', 'Pagamento atualizado.');
    }
}
