<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CoupleController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CoupleInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SavingsGoalController;
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
    Route::get('/couple/join', [CoupleController::class, 'joinForm'])
    ->name('couple.join.form');

    Route::post('/couple/join', [CoupleController::class, 'join'])
        ->name('couple.join');
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

    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])
        ->name('expenses.edit');

    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])
        ->name('expenses.update');

    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])
        ->name('expenses.destroy');

    Route::post('/expenses/{expense}/dispute', [ExpenseController::class, 'dispute'])
        ->name('expenses.dispute');

    Route::post('/expenses/{expense}/undispute', [ExpenseController::class, 'undispute'])
        ->name('expenses.undispute');
    /*
    |--------------------------------------------------------------------------
    | Pagamentos
    |--------------------------------------------------------------------------
    */
    Route::get('/payments/create', [PaymentController::class, 'create'])
        ->name('payments.create');

    Route::post('/payments', [PaymentController::class, 'store'])
        ->name('payments.store');

    Route::get('/payments/history', [PaymentHistoryController::class, 'index'])
        ->name('payments.history');

    Route::patch('/payments/{payment}', [PaymentHistoryController::class, 'update'])
        ->name('payments.update');

    /*
    |--------------------------------------------------------------------------
    | Calendário
    |--------------------------------------------------------------------------
    */
    Route::get('/calendar', [CalendarController::class, 'index'])
        ->name('calendar.index');

    Route::get('/charts', [ChartsController::class, 'index'])
        ->name('charts.index');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->name('categories.store');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy');

    Route::post('/budgets', [BudgetController::class, 'store'])
        ->name('budgets.store');

    Route::patch('/budgets/{budget}', [BudgetController::class, 'update'])
        ->name('budgets.update');

    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])
        ->name('budgets.destroy');

    /*
    |--------------------------------------------------------------------------
    | Notificações
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/dismiss', [NotificationController::class, 'dismiss'])
        ->name('notifications.dismiss');

    Route::get('/summary', [SummaryController::class, 'index'])
        ->name('summary.index');

    Route::get('/calculator', [CalculatorController::class, 'index'])
        ->name('calculator.index');

    Route::get('/activity', [ActivityController::class, 'index'])
        ->name('activity.index');

    Route::get('/export/expenses', [ExportController::class, 'expenses'])
        ->name('export.expenses');

    Route::get('/export/payments', [ExportController::class, 'payments'])
        ->name('export.payments');

    Route::get('/goals', [SavingsGoalController::class, 'index'])->name('goals.index');
    Route::post('/goals', [SavingsGoalController::class, 'store'])->name('goals.store');
    Route::post('/goals/{goal}/contribute', [SavingsGoalController::class, 'contribute'])->name('goals.contribute');
    Route::delete('/goals/{goal}', [SavingsGoalController::class, 'destroy'])->name('goals.destroy');
});

/*
|--------------------------------------------------------------------------
| Auth (login, register, etc)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
