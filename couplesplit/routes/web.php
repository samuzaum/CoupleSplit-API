<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CoupleController; 
use App\Http\Controllers\CardController;
use App\Http\Controllers\ExpenseController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/couples/create', [CoupleController::class, 'create'])
        ->name('couples.create');

    Route::post('/couples', [CoupleController::class, 'store'])
        ->name('couples.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/cards', [CardController::class, 'index'])->name('cards.index');
    Route::get('/cards/create', [CardController::class, 'create'])->name('cards.create');
    Route::post('/cards', [CardController::class, 'store'])->name('cards.store');
    Route::get('/cards/{card}/edit', [CardController::class, 'edit'])
    ->name('cards.edit');
    Route::put('/cards/{card}', [CardController::class, 'update'])
    ->name('cards.update');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])
    ->name('cards.destroy');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/expenses/create', [ExpenseController::class, 'create'])
        ->name('expenses.create');

    Route::post('/expenses', [ExpenseController::class, 'store'])
        ->name('expenses.store');
});
require __DIR__.'/auth.php';
