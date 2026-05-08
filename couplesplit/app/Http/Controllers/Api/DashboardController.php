<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Expense;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private PaymentService $service) {}

    public function index(): JsonResponse
    {
        $user   = Auth::user();
        $couple = $user->couples()->first();

        if (!$couple) {
            return response()->json(['message' => 'Usuário não faz parte de um casal.'], 422);
        }

        $partner = $couple->users()->where('users.id', '!=', $user->id)->first();

        $netBalance = Balance::where('user_id', $user->id)
            ->where('related_user_id', $partner->id)
            ->get()
            ->sum(function ($b) {
                $remaining = $b->amount - $b->used_amount;
                return $b->type === 'credit' ? $remaining : -$remaining;
            });

        $debitAvailable = $this->service->totalOpenDebit($user, $partner);

        $recentExpenses = Expense::where('couple_id', $couple->id)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'net_balance'     => round($netBalance, 2),
            'debit_available' => round($debitAvailable, 2),
            'partner'         => $partner->only('id', 'name', 'email'),
            'recent_expenses' => $recentExpenses,
        ]);
    }

    public function settle(): JsonResponse
    {
        $user    = Auth::user();
        $couple  = $user->couples()->firstOrFail();
        $partner = $couple->users()->where('users.id', '!=', $user->id)->firstOrFail();

        $total = $this->service->totalOpenDebit($user, $partner);

        if ($total <= 0) {
            return response()->json(['message' => 'Nenhuma dívida em aberto.'], 422);
        }

        $payment = $this->service->process($user, $couple, $partner, $total);

        return response()->json(['message' => 'Dívidas liquidadas.', 'payment' => $payment]);
    }
}
