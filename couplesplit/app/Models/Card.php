<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'closing_day', 'due_day'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
