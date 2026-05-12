<?php

namespace App\Http\Controllers;

use App\Models\Couple;
use App\Models\CoupleInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CoupleController extends Controller
{
    public function create()
    {
        return view('couples.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        $couple = Couple::create([
            'name' => $request->name,
        ]);

        // vincula o usuário logado ao casal
        $couple->users()->attach(Auth::id());

        // gera código de convite
        $token = strtoupper(Str::random(6));

        CoupleInvitation::create([
            'couple_id' => $couple->id,
            'email' => null,
            'token' => $token,
        ]);

        return view('couples.created', [
            'token' => $token
        ]);
    }

    /*
    |--------------------------------------
    | Tela para inserir código de convite
    |--------------------------------------
    */
    public function show()
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

        if (!$couple) {
            return redirect()->route('dashboard');
        }

        $partner = $couple->users()->where('users.id', '!=', $user->id)->first();

        return view('couples.show', compact('couple', 'partner'));
    }

    public function leave()
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

        if (!$couple) {
            return redirect()->route('dashboard');
        }

        // desvincula o usuário
        $couple->users()->detach($user->id);

        // se não sobrou ninguém, deleta o casal
        if ($couple->users()->count() === 0) {
            $couple->delete();
        }

        return redirect()->route('dashboard')
            ->with('success', 'Você saiu do casal.');
    }

    public function joinForm()
    {
        return view('couples.join');
    }

    /*
    |--------------------------------------
    | Entrar no casal via código
    |--------------------------------------
    */
    public function join(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $invitation = CoupleInvitation::where('token', $request->token)
            ->whereNull('accepted_at')
            ->first();

        if (!$invitation) {
            return back()->withErrors([
                'token' => 'Código inválido ou já utilizado.'
            ]);
        }

        $user = Auth::user();

        // impede entrar em mais de um casal
        if ($user->couples()->exists()) {
            return redirect()->route('couple.show')
                ->with('error', 'Você já faz parte de um casal.');
        }

        // adiciona usuário ao casal
        $invitation->couple->users()->attach($user->id);

        // marca convite como aceito
        $invitation->accepted_at = now();
        $invitation->save();

        return redirect()->route('dashboard');
    }
}