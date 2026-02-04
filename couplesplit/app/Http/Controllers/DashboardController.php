<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\ExpenseSplit;

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

        if (!$partner) {
            return view('dashboard');
        }

        // Quanto o USUÁRIO deve (splits não pagos)
        $userDebt = ExpenseSplit::where('user_id', $user->id)
            ->where('is_paid', false)
            ->whereHas('expense', function ($q) use ($couple) {
                $q->where('couple_id', $couple->id);
            })
            ->sum('amount');

        // Quanto o PARCEIRO deve (splits não pagos)
        $partnerDebt = ExpenseSplit::where('user_id', $partner->id)
            ->where('is_paid', false)
            ->whereHas('expense', function ($q) use ($couple) {
                $q->where('couple_id', $couple->id);
            })
            ->sum('amount');

        // Saldo líquido:
        // positivo → parceiro deve
        // negativo → você deve
        $netBalance = $partnerDebt - $userDebt;

        return view('dashboard', compact(
            'partner',
            'netBalance',
            'userDebt',
            'partnerDebt'
        ));
    }
}
