<?php

namespace App\Http\Controllers;

use App\Models\Couple;
use App\Models\CoupleInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;



class CoupleInvitationController extends Controller
{
        public function store(Request $request, Couple $couple)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        CoupleInvitation::create([
            'couple_id' => $couple->id,
            'email' => $request->email,
            'token' => Str::uuid(),
        ]);

        // por enquanto sem e-mail real
        return back()->with('success', 'Convite enviado');
    }
    public function accept(string $token)
{
    \Log::info('Invitation accept start', ['token' => $token]);

    $invitation = CoupleInvitation::where('token', $token)
        ->whereNull('accepted_at')
        ->firstOrFail();

    \Log::info('Invitation found', ['invitation_id' => $invitation->id]);

    $user = auth()->user();
    \Log::info('User', ['user_id' => optional($user)->id, 'email' => optional($user)->email]);

    if (!$user || $user->email !== $invitation->email) {
        abort(403);
    }

    if (!$invitation->couple->users()->where('user_id', $user->id)->exists()) {
        $invitation->couple->users()->attach($user->id);
        \Log::info('User attached to couple');
    }

    $invitation->update(['accepted_at' => now()]);
    \Log::info('Invitation accepted');

    return redirect('/dashboard');
}

}
