<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoupleBudget extends Model
{
    protected $fillable = ['couple_id', 'category', 'amount'];

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    /**
     * Gasto real desta categoria no ciclo financeiro atual.
     * Se um User for passado, usa o ciclo financeiro dele (cycle_start_day).
     * Caso contrário, usa o mês calendário.
     */
    public function spentThisMonth(?User $user = null): float
    {
        if ($user) {
            [$from, $to] = $user->financialCycle();
        } else {
            $from = Carbon::now()->startOfMonth()->startOfDay();
            $to   = Carbon::now()->endOfMonth()->endOfDay();
        }

        return (float) Expense::where('couple_id', $this->couple_id)
            ->where('is_shared', true)
            ->where('category', $this->category)
            ->with('installments')
            ->get()
            ->sum(function (Expense $expense) use ($from, $to) {
                if ($expense->installments->isNotEmpty()) {
                    return $expense->installments
                        ->filter(fn($i) => $i->due_date && $i->due_date->between($from, $to))
                        ->sum('amount');
                }

                $date = $expense->expense_date;
                return $date && $date->between($from, $to) ? $expense->amount : 0;
            });
    }
}
