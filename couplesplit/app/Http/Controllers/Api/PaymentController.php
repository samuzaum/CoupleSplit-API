<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user    = Auth::user();
        $couple  = $user->couples()->firstOrFail();
        $partner = $couple->users()->where('users.id', '!=', $user->id)->firstOrFail();

        $payment = $this->service->process($user, $couple, $partner, (float) $request->amount);

        return response()->json($payment, 201);
    }
}
