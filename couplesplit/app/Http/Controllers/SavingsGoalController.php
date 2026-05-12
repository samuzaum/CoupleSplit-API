<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavingsGoalController extends Controller
{
    public function index()
    {
        $couple = Auth::user()->currentCoupleOrFail();
        $goals  = SavingsGoal::where('couple_id', $couple->id)->orderBy('created_at')->get();

        return view('goals.index', compact('goals', 'couple'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:1',
            'target_date'   => 'nullable|date|after:today',
            'color'         => 'nullable|string|size:7',
        ]);

        $couple = Auth::user()->currentCoupleOrFail();

        SavingsGoal::create([
            'couple_id'     => $couple->id,
            'name'          => $request->name,
            'target_amount' => $request->target_amount,
            'target_date'   => $request->target_date,
            'color'         => $request->color ?? '#000000',
        ]);

        return back()->with('success', 'Meta criada!');
    }

    public function contribute(Request $request, SavingsGoal $goal)
    {
        $couple = Auth::user()->currentCoupleOrFail();
        abort_if($goal->couple_id !== $couple->id, 403);

        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $goal->increment('current_amount', $request->amount);

        return back()->with('success', 'Contribuição registrada!');
    }

    public function destroy(SavingsGoal $goal)
    {
        $couple = Auth::user()->currentCoupleOrFail();
        abort_if($goal->couple_id !== $couple->id, 403);

        $goal->delete();

        return back()->with('success', 'Meta removida.');
    }
}
