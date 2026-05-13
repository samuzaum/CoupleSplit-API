<?php

namespace App\Http\Controllers;

use App\Models\UserBenefit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BenefitController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'monthly_amount' => 'required|numeric|min:1',
        ]);

        Auth::user()->benefits()->create([
            'name'           => $request->name,
            'monthly_amount' => $request->monthly_amount,
        ]);

        return back()->with('success', 'Benefício adicionado!');
    }

    public function destroy(UserBenefit $benefit)
    {
        abort_if($benefit->user_id !== Auth::id(), 403);
        $benefit->delete();

        return back()->with('success', 'Benefício removido.');
    }
}
