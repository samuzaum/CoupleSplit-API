<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseInstallment extends Model
{
    protected $fillable = [
        'expense_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'date',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
