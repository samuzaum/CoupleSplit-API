<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = ['name', 'type', 'closing_day'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
