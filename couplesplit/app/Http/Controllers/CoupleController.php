<?php

namespace App\Http\Controllers;

use App\Models\Couple;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return redirect('/')
            ->with('success', 'Casal criado com sucesso!');
    }
}
