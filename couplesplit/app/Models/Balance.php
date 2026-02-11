<?php
// app/Models/Balance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = [
        'couple_id',
        'user_id',
        'related_user_id',
        'amount',
        'used_amount',
        'type',
        'origin',
        'origin_table',
        'origin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
    ];

    public function available(): float
    {
        return (float) ($this->amount - $this->used_amount);
    }
}
