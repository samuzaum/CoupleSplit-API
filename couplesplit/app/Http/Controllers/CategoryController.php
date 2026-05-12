<?php

namespace App\Http\Controllers;

use App\Models\CoupleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);

        $couple = Auth::user()->currentCoupleOrFail();

        $exists = $couple->categories()->where('name', $request->name)->exists();
        $isDefault = in_array($request->name, \App\Models\Expense::CATEGORIES);

        if ($exists || $isDefault) {
            return back()->with('cat_error', 'Essa categoria já existe.');
        }

        $couple->categories()->create(['name' => $request->name]);

        return back()->with('cat_success', 'Categoria criada com sucesso!');
    }

    public function destroy(CoupleCategory $category)
    {
        $couple = Auth::user()->currentCoupleOrFail();
        abort_if($category->couple_id !== $couple->id, 403);

        $category->delete();

        return back()->with('cat_success', 'Categoria removida.');
    }
}
