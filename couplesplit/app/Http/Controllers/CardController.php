<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{
    public function index()
    {
        $cards = Auth::user()->cards;

        return view('cards.index', compact('cards'));
    }

    public function create()
    {
        return view('cards.create');
    }
    public function edit(Card $card)
    {
        // segurança: só o dono pode editar
        abort_if($card->user_id !== auth()->id(), 403);

        return view('cards.edit', compact('card'));
    }
    public function update(Request $request, Card $card)
    {
        abort_if($card->user_id !== auth()->id(), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:credit,debit',
            'closing_day' => 'required_if:type,credit|nullable|integer|min:1|max:31',
        ]);

        $card->update([
            'name' => $request->name,
            'type' => $request->type,
            'closing_day' => $request->type === 'credit'
                ? $request->closing_day
                : null,
        ]);

        return redirect()->route('cards.index')
            ->with('success', 'Cartão atualizado com sucesso!');
    }
    public function destroy(Card $card)
{
    abort_if($card->user_id !== auth()->id(), 403);

    $card->delete();

    return redirect()->route('cards.index')
        ->with('success', 'Cartão removido!');
}

   public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|in:credit,debit',
        'closing_day' => 'required_if:type,credit|nullable|integer|min:1|max:31',
    ]);

    Auth::user()->cards()->create([
        'name' => $request->name,
        'type' => $request->type,
        'closing_day' => $request->type === 'credit'
            ? $request->closing_day
            : null,
    ]);
    
    return redirect()->route('cards.index')
        ->with('success', 'Cartão criado com sucesso!');
}
}
