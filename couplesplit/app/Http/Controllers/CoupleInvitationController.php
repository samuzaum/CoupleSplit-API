<?php

namespace App\Http\Controllers;

use App\Models\Couple;
use App\Models\CoupleInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $invitation = CoupleInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        $user = auth()->user();

        if (!$user || $user->email !== $invitation->email) {
            abort(403);
        }

        if (!$invitation->couple->users()->where('user_id', $user->id)->exists()) {
            $invitation->couple->users()->attach($user->id);
        }

        $invitation->update(['accepted_at' => now()]);

        return redirect('/dashboard');
    }

}
