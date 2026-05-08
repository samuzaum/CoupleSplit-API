<?php

use App\Models\Balance;
use App\Models\Couple;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCoupleWithDebt(float $amount = 100.0, float $splitRatio = 0.5): array
{
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $couple = Couple::create(['name' => 'Casal']);
    $couple->users()->attach([$userA->id, $userB->id]);

    $expense = Expense::create([
        'couple_id'    => $couple->id,
        'paid_by'      => $userA->id,
        'description'  => 'Despesa teste',
        'amount'       => $amount,
        'expense_date' => Carbon::today(),
        'billing_date' => Carbon::today(),
        'is_shared'    => true,
        'split_ratio'  => $splitRatio,
    ]);

    app(ExpenseService::class)->createBalances($expense);

    return [$couple, $userA, $userB, $expense];
}

it('settles a debt correctly', function () {
    [$couple, $userA, $userB] = makeCoupleWithDebt(100.0);

    // B owes A 50, B pays A 50
    app(PaymentService::class)->process($userB, $couple, $userA, 50.0);

    $debit = Balance::where('user_id', $userB->id)
        ->where('related_user_id', $userA->id)
        ->where('type', 'debit')
        ->first();

    expect((float) $debit->used_amount)->toBe(50.0);
    expect((float) $debit->amount - (float) $debit->used_amount)->toBe(0.0);
});

it('creates a credit balance when payment exceeds existing debts', function () {
    [$couple, $userA, $userB] = makeCoupleWithDebt(100.0);

    // B owes 50, pays 80 → should have 30 in credit
    app(PaymentService::class)->process($userB, $couple, $userA, 80.0);

    $credit = Balance::where('user_id', $userB->id)
        ->where('related_user_id', $userA->id)
        ->where('type', 'credit')
        ->where('origin', 'payment')
        ->first();

    expect($credit)->not->toBeNull();
    expect((float) $credit->amount)->toBe(30.0);
});

it('computes total open debit correctly', function () {
    [$couple, $userA, $userB] = makeCoupleWithDebt(200.0, 0.5);

    // B owes A 100
    $service = app(PaymentService::class);
    expect($service->totalOpenDebit($userB, $userA))->toBe(100.0);

    // B pays 40
    $service->process($userB, $couple, $userA, 40.0);
    expect($service->totalOpenDebit($userB, $userA))->toBe(60.0);
});
