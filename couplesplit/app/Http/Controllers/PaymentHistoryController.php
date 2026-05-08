<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryController extends Controller
{
    public function update(Request $request, Payment $payment)
    {
        $couple = Auth::user()->couples()->firstOrFail();
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

    public function index(Request $request)
    {
        $user   = Auth::user();
        $couple = $user->couples()->first();

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

        $directionFilter = $request->input('direction'); // 'sent', 'received', ''
        if ($directionFilter === 'sent') {
            $query->where('from_user_id', $user->id);
        } elseif ($directionFilter === 'received') {
            $query->where('to_user_id', $user->id);
        }

        $payments = $query->orderByDesc('payment_date')->orderByDesc('created_at')->get();

        return view('payments.history', compact('payments', 'monthFilter', 'directionFilter'));
    }
}
