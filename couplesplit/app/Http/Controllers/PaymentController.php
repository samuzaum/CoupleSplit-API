<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /* =========================
     * CREATE (TELA)
     * ========================= */
    public function create()
    {
        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $partner = $couple->users()
            ->where('users.id', '!=', $user->id)
            ->firstOrFail();

        // débitos abertos DO USUÁRIO
        $openDebits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->whereColumn('used_amount', '<', 'amount')
            ->orderBy('created_at')
            ->get();

        return view('payments.create', compact(
            'partner'    => $partner,
            'openDebits' => $openDebits,
        ));
    }

    /* =========================
     * STORE (REGRA DE NEGÓCIO)
     * ========================= */
    public function store(Request $request)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:0.01',
            'balances' => 'array',
        ]);

        DB::transaction(function () use ($request) {
            $user   = Auth::user();
            $couple = $user->couples()->firstOrFail();

            $partner = $couple->users()
                ->where('users.id', '!=', $user->id)
                ->firstOrFail();

            $remaining = $request->amount;

            // 1️⃣ registra o pagamento (histórico)
            $payment = Payment::create([
                'couple_id'     => $couple->id,
                'from_user_id'  => $user->id,
                'to_user_id'    => $partner->id,
                'amount'        => $request->amount,
                'payment_date'  => Carbon::now(),
            ]);

            // 2️⃣ consome débitos selecionados (ou todos)
            $debits = Balance::where('user_id', $user->id)
                ->where('related_user_id', $partner->id)
                ->where('type', 'debit')
                ->whereColumn('used_amount', '<', 'amount')
                ->when(
                    $request->filled('balances'),
                    fn ($q) => $q->whereIn('id', $request->balances)
                )
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($debits as $debit) {
                if ($remaining <= 0) break;

                $available = $debit->amount - $debit->used_amount;
                $consume   = min($available, $remaining);

                $debit->increment('used_amount', $consume);
                $remaining -= $consume;
            }

            // 3️⃣ sobrou dinheiro → vira crédito
            if ($remaining > 0) {
                Balance::create([
                    'couple_id'       => $couple->id,
                    'user_id'         => $partner->id,
                    'related_user_id' => $user->id,
                    'amount'          => $remaining,
                    'used_amount'     => 0,
                    'type'            => 'credit',
                    'origin'          => 'payment',
                    'origin_table'    => 'payments',
                    'origin_id'       => $payment->id,
                ]);
            }
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Pagamento registrado com sucesso!');
    }
}
