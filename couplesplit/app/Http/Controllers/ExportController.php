<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function expenses(Request $request): StreamedResponse
    {
        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $query = Expense::where('couple_id', $couple->id)
            ->with(['payer'])
            ->orderBy('expense_date');

        if ($request->filled('month')) {
            try {
                $m = Carbon::createFromFormat('Y-m', $request->month);
                $query->whereYear('expense_date', $m->year)
                      ->whereMonth('expense_date', $m->month);
            } catch (\Exception $e) {}
        }

        if ($request->filled('type')) {
            if ($request->type === 'shared')   $query->where('is_shared', true);
            if ($request->type === 'personal') $query->where('is_shared', false);
        }

        $expenses = $query->get();
        $month    = $request->filled('month') ? $request->month : 'tudo';
        $filename = "despesas_{$month}.csv";

        return response()->streamDownload(function () use ($expenses, $user) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Descrição', 'Categoria', 'Valor (R$)', 'Tipo', 'Pago por', 'Sua parte (R$)', 'Status sua parte'], ';');

            foreach ($expenses as $expense) {
                $isMe    = $expense->paid_by === $user->id;
                $ratio   = $expense->split_ratio ?? 0.5;
                $myShare = $expense->is_shared
                    ? round($expense->amount * ($isMe ? $ratio : (1 - $ratio)), 2)
                    : $expense->amount;

                fputcsv($out, [
                    $expense->expense_date->format('d/m/Y'),
                    $expense->description,
                    $expense->category ?? 'Sem categoria',
                    number_format($expense->amount, 2, ',', '.'),
                    $expense->is_shared ? 'Compartilhada' : 'Pessoal',
                    $expense->payer->name,
                    number_format($myShare, 2, ',', '.'),
                    $expense->is_shared ? ($isMe ? 'Paguei' : 'Devo') : ($expense->paid_at ? 'Paga' : 'Pendente'),
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function payments(Request $request): StreamedResponse
    {
        $user   = Auth::user();
        $couple = $user->couples()->firstOrFail();

        $query = Payment::where('couple_id', $couple->id)
            ->with(['fromUser', 'toUser'])
            ->orderBy('payment_date');

        if ($request->filled('month')) {
            try {
                $m = Carbon::createFromFormat('Y-m', $request->month);
                $query->whereYear('payment_date', $m->year)
                      ->whereMonth('payment_date', $m->month);
            } catch (\Exception $e) {}
        }

        $payments = $query->get();
        $month    = $request->filled('month') ? $request->month : 'tudo';
        $filename = "pagamentos_{$month}.csv";

        return response()->streamDownload(function () use ($payments, $user) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'De', 'Para', 'Valor (R$)', 'Observação'], ';');

            foreach ($payments as $payment) {
                fputcsv($out, [
                    $payment->payment_date->format('d/m/Y'),
                    $payment->fromUser->name,
                    $payment->toUser->name,
                    number_format($payment->amount, 2, ',', '.'),
                    $payment->note ?? '',
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
