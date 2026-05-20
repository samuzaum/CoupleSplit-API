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
use Illuminate\Support\Carbon;

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
        'cycle_start_day',
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

    /**
     * Retorna o início e fim do ciclo financeiro do usuário.
     * Se cycle_start_day for nulo, usa o mês calendário (dia 1).
     *
     * @return array{0: Carbon, 1: Carbon}  [$start, $end]
     */
    public function financialCycle(?Carbon $reference = null): array
    {
        $ref = ($reference ?? Carbon::now())->copy()->startOfDay();
        $day = $this->cycle_start_day ?? 1;

        // Início do ciclo: dia $day do mês de $ref, limitado ao último dia do mês
        $candidateStart = $ref->copy()->startOfMonth();
        $candidateStart->setDay(min($day, $candidateStart->daysInMonth));

        // Se o candidato ainda está no futuro, retrocede um mês
        if ($candidateStart->gt($ref)) {
            $candidateStart->subMonthNoOverflow();
            $candidateStart->setDay(min($day, $candidateStart->daysInMonth));
        }

        $candidateStart->startOfDay();

        // Fim do ciclo: próxima ocorrência do dia $day, menos um dia
        $cycleEnd = $candidateStart->copy()->addMonthNoOverflow();
        $cycleEnd->setDay(min($day, $cycleEnd->daysInMonth));
        $cycleEnd->subDay()->endOfDay();

        return [$candidateStart, $cycleEnd];
    }
}
