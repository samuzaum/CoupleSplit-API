<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoupleBudget extends Model
{
    protected $fillable = ['couple_id', 'category', 'amount'];

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }
}
