<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expense;
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
        $couple = $user->couples()->firstOrFail();

        $partner = $couple->users()
            ->where('users.id', '!=', $user->id)
            ->first();

        if (!$partner) {
            return redirect()->route('dashboard')
                ->with('error', 'Seu parceiro(a) ainda não aceitou o convite.');
        }

        $openDebits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->whereColumn('used_amount', '<', 'amount')
            ->orderBy('created_at')
            ->get();

        $personalUnpaid = Expense::where('couple_id', $couple->id)
            ->where('is_shared', false)
            ->where('paid_by', $user->id)
            ->whereNull('paid_at')
            ->orderBy('expense_date')
            ->get();

        return view('payments.create', [
            'partner'        => $partner,
            'openDebits'     => $openDebits,
            'personalUnpaid' => $personalUnpaid,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_type' => 'required|in:partner,personal',
            'amount'       => 'required_if:payment_type,partner|nullable|numeric|min:0.01',
            'expense_ids'  => 'required_if:payment_type,personal|nullable|array',
            'expense_ids.*'=> 'exists:expenses,id',
        ]);

        $user    = Auth::user();
        $couple  = $user->couples()->firstOrFail();

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

        // personal: marca as despesas selecionadas como pagas
        $ids = $request->expense_ids ?? [];
        Expense::whereIn('id', $ids)
            ->where('couple_id', $couple->id)
            ->where('paid_by', $user->id)
            ->where('is_shared', false)
            ->update(['paid_at' => Carbon::now()]);

        $this->activity->log($user, $couple->id, 'payment', 'expense', null,
            "Marcou " . count($ids) . " despesa(s) pessoal(is) como pagas");

        return redirect()->route('expenses.index')->with('success', 'Despesas pessoais marcadas como pagas!');
    }
}
