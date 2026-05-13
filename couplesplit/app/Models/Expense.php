<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    const CATEGORIES = [
        'Alimentação',
        'Transporte',
        'Moradia',
        'Saúde',
        'Lazer',
        'Educação',
        'Vestuário',
        'Outros',
    ];

    protected $fillable = [
        'couple_id',
        'paid_by',
        'card_id',
        'description',
        'notes',
        'category',
        'amount',
        'expense_date',
        'billing_date',
        'is_shared',
        'split_ratio',
        'is_recurring',
        'parent_id',
        'paid_at',
        'status',
        'benefit_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'billing_date' => 'date',
        'paid_at'      => 'datetime',
        'split_ratio'  => 'float',
        'is_recurring' => 'boolean',
        'is_shared'    => 'boolean',
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
    public function installments()
    {
        return $this->hasMany(ExpenseInstallment::class);
    }
}
