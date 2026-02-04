<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CoupleController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CoupleInvitationController;
use App\Http\Controllers\DashboardController;
/*
|--------------------------------------------------------------------------
| Página inicial pública
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Rotas autenticadas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');
    Route::post('/debts/settle', [DashboardController::class, 'settle'])
    ->name('debts.settle');

    /*
    |--------------------------------------------------------------------------
    | Perfil
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Casal
    |--------------------------------------------------------------------------
    */
    Route::get('/couples/create', [CoupleController::class, 'create'])
        ->name('couples.create');

    Route::post('/couples', [CoupleController::class, 'store'])
        ->name('couples.store');

    // página principal do casal (configurações, membros, renda futuramente)
    Route::get('/couple', [CoupleController::class, 'show'])
        ->name('couple.show');

    /*
    |--------------------------------------------------------------------------
    | Convites do casal
    |--------------------------------------------------------------------------
    */
    Route::post('/couples/{couple}/invite', [CoupleInvitationController::class, 'store'])
        ->name('couples.invite');

    Route::get('/invitations/{token}', [CoupleInvitationController::class, 'accept'])
        ->name('invitations.accept');

    /*
    |--------------------------------------------------------------------------
    | Cartões
    |--------------------------------------------------------------------------
    */
    Route::get('/cards', [CardController::class, 'index'])
        ->name('cards.index');

    Route::get('/cards/create', [CardController::class, 'create'])
        ->name('cards.create');

    Route::post('/cards', [CardController::class, 'store'])
        ->name('cards.store');

    Route::get('/cards/{card}/edit', [CardController::class, 'edit'])
        ->name('cards.edit');

    Route::put('/cards/{card}', [CardController::class, 'update'])
        ->name('cards.update');

    Route::delete('/cards/{card}', [CardController::class, 'destroy'])
        ->name('cards.destroy');

    /*
    |--------------------------------------------------------------------------
    | Despesas
    |--------------------------------------------------------------------------
    */
    Route::get('/expenses', [ExpenseController::class, 'index'])
        ->name('expenses.index');

    Route::get('/expenses/couple', [ExpenseController::class, 'couple'])
        ->name('expenses.couple');

    Route::get('/expenses/personal', [ExpenseController::class, 'personal'])
        ->name('expenses.personal');

    Route::get('/expenses/create', [ExpenseController::class, 'create'])
        ->name('expenses.create');

    Route::post('/expenses', [ExpenseController::class, 'store'])
        ->name('expenses.store');
    
});

/*
|--------------------------------------------------------------------------
| Auth (login, register, etc)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
