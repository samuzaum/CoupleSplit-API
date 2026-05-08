<?php

use App\Models\Balance;
use App\Models\Couple;
use App\Models\Expense;
use App\Models\ExpenseInstallment;
use App\Models\User;
use App\Services\ExpenseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCouple(): array
{
    $userA = User::factory()->create(['monthly_income' => 3000]);
    $userB = User::factory()->create(['monthly_income' => 2000]);
    $couple = Couple::create(['name' => 'Casal Teste']);
    $couple->users()->attach([$userA->id, $userB->id]);
    return [$couple, $userA, $userB];
}

it('creates credit and debit balances for a shared expense with 50/50 split', function () {
    [$couple, $userA, $userB] = makeCouple();

    $expense = Expense::create([
        'couple_id'    => $couple->id,
        'paid_by'      => $userA->id,
        'description'  => 'Supermercado',
        'amount'       => 100.00,
        'expense_date' => Carbon::today(),
        'billing_date' => Carbon::today(),
        'is_shared'    => true,
        'split_ratio'  => 0.5,
    ]);

    app(ExpenseService::class)->createBalances($expense);

    // payer (A) tem crédito contra B de 50
    expect((float) Balance::where('user_id', $userA->id)
        ->where('related_user_id', $userB->id)
        ->where('type', 'credit')
        ->value('amount')
    )->toBe(50.0);

    // B tem débito de 50 com A
    expect((float) Balance::where('user_id', $userB->id)
        ->where('related_user_id', $userA->id)
        ->where('type', 'debit')
        ->value('amount')
    )->toBe(50.0);
});

it('creates balances correctly with a custom split ratio', function () {
    [$couple, $userA, $userB] = makeCouple();

    $expense = Expense::create([
        'couple_id'    => $couple->id,
        'paid_by'      => $userA->id,
        'description'  => 'Aluguel',
        'amount'       => 1000.00,
        'expense_date' => Carbon::today(),
        'billing_date' => Carbon::today(),
        'is_shared'    => true,
        'split_ratio'  => 0.6, // A cobre 60%, B deve 40%
    ]);

    app(ExpenseService::class)->createBalances($expense);

    $credit = Balance::where('user_id', $userA->id)
        ->where('related_user_id', $userB->id)
        ->where('type', 'credit')
        ->value('amount');

    expect((float) $credit)->toBe(400.0);
});

it('does not create balances for a personal expense', function () {
    [$couple, $userA, $userB] = makeCouple();

    $expense = Expense::create([
        'couple_id'    => $couple->id,
        'paid_by'      => $userA->id,
        'description'  => 'Lanche pessoal',
        'amount'       => 30.00,
        'expense_date' => Carbon::today(),
        'billing_date' => Carbon::today(),
        'is_shared'    => false,
        'split_ratio'  => 1.0,
    ]);

    app(ExpenseService::class)->createBalances($expense);

    expect(Balance::count())->toBe(0);
});

it('creates installments correctly', function () {
    [$couple, $userA, $userB] = makeCouple();

    $expense = Expense::create([
        'couple_id'    => $couple->id,
        'paid_by'      => $userA->id,
        'description'  => 'Geladeira',
        'amount'       => 1200.00,
        'expense_date' => Carbon::today(),
        'billing_date' => Carbon::today(),
        'is_shared'    => true,
        'split_ratio'  => 0.5,
    ]);

    app(ExpenseService::class)->createInstallments($expense, 4);

    $installments = ExpenseInstallment::where('expense_id', $expense->id)->get();

    expect($installments)->toHaveCount(4);
    expect((float) $installments->first()->amount)->toBe(300.0);
    expect($installments->first()->installment_number)->toBe(1);
    expect($installments->last()->installment_number)->toBe(4);
});
