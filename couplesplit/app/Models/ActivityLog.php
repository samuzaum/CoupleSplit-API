<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'couple_id', 'action', 'subject_type', 'subject_id', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
