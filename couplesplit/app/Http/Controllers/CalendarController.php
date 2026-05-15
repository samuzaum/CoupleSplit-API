<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $couple = $user->currentCouple();

        if (!$couple) {
            return redirect()->route('dashboard')
                ->with('error', 'Você ainda não faz parte de um casal.');
        }

        try {
            $currentMonth = $request->month
                ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Exception $e) {
            $currentMonth = Carbon::now()->startOfMonth();
        }

        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        $expenses = Expense::where('couple_id', $couple->id)
            ->with(['card', 'installments'])
            ->get();

        $events = [];

        foreach ($expenses as $expense) {
            if ($expense->installments->count() > 0) {
                $total = $expense->installments->count();
                foreach ($expense->installments as $installment) {
                    $events[] = [
                        'date'         => $installment->due_date,
                        'description'  => $expense->description,
                        'label'        => "parcela {$installment->installment_number}/{$total}",
                        'amount'       => $installment->amount,
                        'card'         => $expense->card,
                        'paid'         => $installment->isPaid(),
                        'is_recurring' => false,
                        'is_generated' => false,
                    ];
                }
            } else {
                $date = $expense->billing_date ?? $expense->expense_date;
                if (!$date) {
                    continue;
                }

                $events[] = [
                    'date'         => $date,
                    'description'  => $expense->description,
                    'label'        => null,
                    'amount'       => $expense->amount,
                    'card'         => $expense->card,
                    'paid'         => false,
                    'is_recurring' => $expense->is_recurring,
                    'is_generated' => !is_null($expense->parent_id),
                ];
            }
        }

        usort($events, fn($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        $grouped = collect($events)
            ->filter(fn($e) => $e['date']->isSameMonth($currentMonth))
            ->groupBy(fn($e) => $e['date']->format('Y-m-d'));

        return view('calendar.index', compact('grouped', 'currentMonth', 'prevMonth', 'nextMonth'));
    }
}
