<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

        if (!$couple) {
            return redirect()->route('dashboard')->with('error', 'Você ainda não faz parte de um casal.');
        }

        $logs = ActivityLog::where('couple_id', $couple->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->take(100)
            ->get();

        return view('activity.index', compact('logs'));
    }
}
