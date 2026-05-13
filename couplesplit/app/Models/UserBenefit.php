<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserBenefit extends Model
{
    protected $fillable = ['user_id', 'name', 'monthly_amount'];

    protected $casts = [
        'monthly_amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'benefit_id');
    }

    /** Quanto já foi usado do benefício no mês atual */
    public function usedThisMonth(): float
    {
        return (float) $this->expenses()
            ->whereYear('expense_date', Carbon::now()->year)
            ->whereMonth('expense_date', Carbon::now()->month)
            ->sum('amount');
    }

    /** Saldo restante no mês */
    public function remainingThisMonth(): float
    {
        return max(0, $this->monthly_amount - $this->usedThisMonth());
    }
}
