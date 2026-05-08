<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentItem extends Model
{
    protected $fillable = ['payment_id', 'balance_id', 'amount', 'description'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(Balance::class);
    }
}
