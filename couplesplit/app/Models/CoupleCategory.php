<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoupleCategory extends Model
{
    protected $fillable = ['couple_id', 'name'];

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }
}
