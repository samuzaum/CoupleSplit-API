<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChartsController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $couple = $user->couples()->first();

        if (!$couple) {
            return redirect()->route('dashboard')
                ->with('error', 'Você ainda não faz parte de um casal.');
        }

        $expenses = Expense::where('couple_id', $couple->id)
            ->where('is_shared', true)
            ->get();

        // gastos por categoria
        $byCategory = $expenses
            ->groupBy('category')
            ->map(fn($g) => round($g->sum('amount'), 2))
            ->mapWithKeys(function ($total, $key) {
                $label = \App\Models\Expense::CATEGORIES[$key] ?? 'Outros';
                return [$label => $total];
            });

        // gastos por mês (últimos 6 meses)
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        $byMonth = $months->mapWithKeys(function ($ym) use ($expenses) {
            [$year, $month] = explode('-', $ym);
            $total = $expenses->filter(function ($e) use ($year, $month) {
                return $e->billing_date &&
                    $e->billing_date->year == $year &&
                    $e->billing_date->month == $month;
            })->sum('amount');

            $label = Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M/y');
            return [$label => round($total, 2)];
        });

        return view('charts.index', [
            'byCategory' => $byCategory,
            'byMonth'    => $byMonth,
        ]);
    }
}
