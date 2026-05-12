<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoupleBudget extends Model
{
    protected $fillable = ['couple_id', 'category', 'amount'];

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    /** Gasto real desta categoria no mês atual */
    public function spentThisMonth(): float
    {
        return (float) Expense::where('couple_id', $this->couple_id)
            ->where('is_shared', true)
            ->where('category', $this->category)
            ->whereYear('expense_date', Carbon::now()->year)
            ->whereMonth('expense_date', Carbon::now()->month)
            ->sum('amount');
    }
}
