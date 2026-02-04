<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoupleInvitation extends Model
{
    protected $fillable = [
        'couple_id',
        'email',
        'token',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }
}
