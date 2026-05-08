<?php

use App\Models\Balance;
use App\Models\Couple;
use App\Models\Payment;
use App\Models\User;
use App\Services\ExpenseService;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function coupleForPayment(): array
{
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $couple = Couple::create(['name' => 'Casal']);
    $couple->users()->attach([$userA->id, $userB->id]);
    return [$couple, $userA, $userB];
}

it('redirects unauthenticated user from payment creation', function () {
    $this->get(route('payments.create'))->assertRedirect(route('login'));
});

it('can register a partner payment', function () {
    [$couple, $userA, $userB] = coupleForPayment();

    // create a debt first
    $expense = Expense::create([
        'couple_id' => $couple->id, 'paid_by' => $userA->id,
        'description' => 'Despesa', 'amount' => 100,
        'expense_date' => Carbon::today(), 'billing_date' => Carbon::today(),
        'is_shared' => true, 'split_ratio' => 0.5,
    ]);
    app(ExpenseService::class)->createBalances($expense);

    $this->actingAs($userB)->post(route('payments.store'), [
        'payment_type' => 'partner',
        'amount'       => '50.00',
    ])->assertRedirect(route('dashboard'));

    expect(Payment::count())->toBe(1);

    $debit = Balance::where('user_id', $userB->id)->where('type', 'debit')->first();
    expect((float) $debit->used_amount)->toBe(50.0);
});

it('validates amount when registering partner payment', function () {
    [$couple, $userA, $userB] = coupleForPayment();

    $this->actingAs($userB)->post(route('payments.store'), [
        'payment_type' => 'partner',
        'amount'       => '',
    ])->assertSessionHasErrors('amount');
});

it('can view payment history', function () {
    [$couple, $userA, $userB] = coupleForPayment();

    $this->actingAs($userA)->get(route('payments.history'))->assertOk();
});

it('can update payment note and date', function () {
    [$couple, $userA, $userB] = coupleForPayment();

    $payment = Payment::create([
        'couple_id'    => $couple->id,
        'from_user_id' => $userA->id,
        'to_user_id'   => $userB->id,
        'amount'       => 50,
        'payment_date' => Carbon::today(),
    ]);

    $this->actingAs($userA)->patch(route('payments.update', $payment), [
        'payment_date' => '2025-03-15',
        'note'         => 'Pix do almoço',
    ])->assertRedirect();

    $payment->refresh();
    expect($payment->note)->toBe('Pix do almoço');
    expect($payment->payment_date->format('Y-m-d'))->toBe('2025-03-15');
});
