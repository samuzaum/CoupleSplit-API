<?php

use App\Models\Couple;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function coupleWithUsers(): array
{
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $couple = Couple::create(['name' => 'Casal Teste']);
    $couple->users()->attach([$userA->id, $userB->id]);
    return [$couple, $userA, $userB];
}

it('redirects unauthenticated users from expenses', function () {
    $this->get(route('expenses.index'))->assertRedirect(route('login'));
});

it('authenticated user can view expenses list', function () {
    [$couple, $userA] = coupleWithUsers();
    $this->actingAs($userA)->get(route('expenses.index'))->assertOk();
});

it('can create a shared expense', function () {
    [$couple, $userA, $userB] = coupleWithUsers();

    $this->actingAs($userA)->post(route('expenses.store'), [
        'description'  => 'Supermercado',
        'amount'       => '250.00',
        'expense_date' => '2025-05-01',
        'is_shared'    => '1',
        'split_ratio'  => '50',
    ])->assertRedirect(route('expenses.index'));

    expect(Expense::count())->toBe(1);
    expect(Expense::first()->description)->toBe('Supermercado');
});

it('can create a personal expense', function () {
    [$couple, $userA] = coupleWithUsers();

    $this->actingAs($userA)->post(route('expenses.store'), [
        'description'  => 'Lanche',
        'amount'       => '30.00',
        'expense_date' => '2025-05-01',
        'is_shared'    => '0',
    ])->assertRedirect(route('expenses.index'));

    $expense = Expense::first();
    expect((bool) $expense->is_shared)->toBeFalse();
});

it('validates required fields on expense creation', function () {
    [$couple, $userA] = coupleWithUsers();

    $this->actingAs($userA)->post(route('expenses.store'), [])
        ->assertSessionHasErrors(['description', 'amount', 'expense_date']);
});

it('cannot delete an expense from another couple', function () {
    [$couple, $userA, $userB] = coupleWithUsers();
    [$coupleB, $userC] = coupleWithUsers();

    $expense = Expense::create([
        'couple_id'    => $couple->id,
        'paid_by'      => $userA->id,
        'description'  => 'Despesa do Casal A',
        'amount'       => 100,
        'expense_date' => now(),
        'billing_date' => now(),
        'is_shared'    => false,
        'split_ratio'  => 1.0,
    ]);

    $this->actingAs($userC)
        ->delete(route('expenses.destroy', $expense))
        ->assertForbidden();
});

it('can filter expenses by month', function () {
    [$couple, $userA] = coupleWithUsers();

    Expense::create([
        'couple_id' => $couple->id, 'paid_by' => $userA->id,
        'description' => 'Janeiro', 'amount' => 50,
        'expense_date' => '2025-01-15', 'billing_date' => '2025-01-15',
        'is_shared' => false, 'split_ratio' => 1.0,
    ]);

    Expense::create([
        'couple_id' => $couple->id, 'paid_by' => $userA->id,
        'description' => 'Fevereiro', 'amount' => 80,
        'expense_date' => '2025-02-10', 'billing_date' => '2025-02-10',
        'is_shared' => false, 'split_ratio' => 1.0,
    ]);

    $response = $this->actingAs($userA)
        ->get(route('expenses.index', ['month' => '2025-01']));

    $response->assertOk()->assertSee('Janeiro')->assertDontSee('Fevereiro');
});
