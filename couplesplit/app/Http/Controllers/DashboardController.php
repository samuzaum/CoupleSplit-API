<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Balance;
use App\Models\Expense;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $couple = $user->couples()->first();

        if (!$couple) {
            return view('dashboard-no-couple');
        }

        $partner = $couple->users()
            ->where('users.id', '!=', $user->id)
            ->first();


        /* =========================
         * BUSCA TODOS OS BALANCES ENTRE OS DOIS
         * ========================= */
        $balances = Balance::where(function ($q) use ($user, $partner) {

            $q->where('user_id', $user->id)
              ->where('related_user_id', $partner->id);

        })->orWhere(function ($q) use ($user, $partner) {

            $q->where('user_id', $partner->id)
              ->where('related_user_id', $user->id);

        })->get();


        /* =========================
         * SALDO LÍQUIDO
         * ========================= */
        $netBalance = 0;

        foreach ($balances as $b) {

            $remaining = $b->amount - $b->used_amount;

            if ($b->type === 'credit') {

                if ($b->user_id == $user->id) {
                    $netBalance += $remaining;
                } else {
                    $netBalance -= $remaining;
                }

            } else {

                if ($b->user_id == $user->id) {
                    $netBalance -= $remaining;
                } else {
                    $netBalance += $remaining;
                }

            }
        }


        /* =========================
         * CRÉDITOS DISPONÍVEIS
         * ========================= */
        $creditAvailable = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'credit')
            ->get()
            ->sum(fn ($b) => $b->amount - $b->used_amount);


        /* =========================
         * DÉBITOS DISPONÍVEIS
         * ========================= */
        $debitAvailable = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->where('type', 'debit')
            ->get()
            ->sum(fn ($b) => $b->amount - $b->used_amount);


        /* =========================
         * DÉBITOS EM ABERTO
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


        /* =========================
         * DESPESAS RECENTES
         * ========================= */
        $recentExpenses = Expense::where('couple_id', $couple->id)
            ->latest()
            ->take(5)
            ->get();


        return view('dashboard', compact(
            'partner',
            'netBalance',
            'creditAvailable',
            'debitAvailable',
            'openDebits',
            'openCredits',
            'recentExpenses'
        ));
    }
}