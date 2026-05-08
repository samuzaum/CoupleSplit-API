<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Contracts\Auth\Authenticatable;

class ActivityService
{
    public function log(Authenticatable $user, int $coupleId, string $action, string $subjectType, ?int $subjectId, string $description): void
    {
        ActivityLog::create([
            'user_id'      => $user->id,
            'couple_id'    => $coupleId,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'description'  => $description,
        ]);
    }
}
