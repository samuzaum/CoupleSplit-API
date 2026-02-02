<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $fillable = [
    'couple_id',
    'paid_by',
    'card_id',
    'description',
    'amount',
    'expense_date',
    'billing_date',
    'is_shared',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'billing_date' => 'date',
    ];


    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }
}
