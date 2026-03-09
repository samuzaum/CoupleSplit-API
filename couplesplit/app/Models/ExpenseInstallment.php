<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseInstallment extends Model
{
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
