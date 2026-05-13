<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Couple;
use App\Models\Card;
use App\Models\Expense;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'monthly_income',
        'notification_dismissals',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'notification_dismissals'  => 'array',
        ];
    }

    /* ======================
       RELATIONSHIPS
    ====================== */

    public function benefits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\UserBenefit::class);
    }

    public function couples(): BelongsToMany
    {
        return $this->belongsToMany(Couple::class)->withTimestamps();
    }

    /** Retorna o casal atual do usuário ou null */
    public function currentCouple(): ?Couple
    {
        return $this->couples()->first();
    }

    /** Retorna o casal atual ou lança 404 */
    public function currentCoupleOrFail(): Couple
    {
        return $this->couples()->firstOrFail();
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function expensesPaid(): HasMany
    {
        return $this->hasMany(Expense::class, 'paid_by');
    }
}
