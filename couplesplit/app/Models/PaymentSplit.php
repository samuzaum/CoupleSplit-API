<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSplit extends Model
{
    protected $fillable = [
        'payment_id',
        'expense_split_id',
        'amount',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function expenseSplit(): BelongsTo
    {
        return $this->belongsTo(ExpenseSplit::class);
    }
}
