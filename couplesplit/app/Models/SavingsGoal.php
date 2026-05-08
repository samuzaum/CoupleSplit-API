<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsGoal extends Model
{
    protected $fillable = ['couple_id', 'name', 'target_amount', 'current_amount', 'target_date', 'color'];

    protected $casts = [
        'target_date'    => 'date',
        'target_amount'  => 'float',
        'current_amount' => 'float',
    ];

    public function getProgressAttribute(): int
    {
        if ($this->target_amount <= 0) return 0;
        return min(100, (int) round($this->current_amount / $this->target_amount * 100));
    }

    public function getRemainingAttribute(): float
    {
        return max(0, round($this->target_amount - $this->current_amount, 2));
    }
}
