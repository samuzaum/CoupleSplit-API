<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Balance;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $couple = $user->couples()->first();

        if (!$couple) {
            return view('dashboard');
        }

        $partner = $couple->users()
            ->where('users.id', '!=', $user->id)
            ->first();

        /* =========================
         * CRÉDITOS (o que você tem a receber)
         * ========================= */
        $credits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'credit')
            ->get();

        $creditAvailable = $credits->sum(fn ($b) =>
            $b->amount - $b->used_amount
        );

        /* =========================
         * DÉBITOS (o que você deve)
         * ========================= */
        $debits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->get();

        $debitAvailable = $debits->sum(fn ($b) =>
            $b->amount - $b->used_amount
        );

        /* =========================
         * SALDO LÍQUIDO
         * ========================= */
        $netBalance = $creditAvailable - $debitAvailable;

        /* =========================
         * DÉBITOS EM ABERTO (LISTAGEM)
         * ========================= */
        $openDebits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->whereColumn('used_amount', '<', 'amount')
            ->orderBy('created_at')
            ->get();

        /* =========================
         * CRÉDITOS EM ABERTO
         * ========================= */
        $openCredits = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'credit')
            ->whereColumn('used_amount', '<', 'amount')
            ->orderBy('created_at')
            ->get();

        return view('dashboard', compact(
            'partner',
            'netBalance',
            'creditAvailable',
            'debitAvailable',
            'openDebits',
            'openCredits'
        ));
    }
}
