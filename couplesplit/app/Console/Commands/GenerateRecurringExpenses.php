<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Services\ActivityService;
use App\Services\ExpenseService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringExpenses extends Command
{
    protected $signature   = 'expenses:generate-recurring';
    protected $description = 'Gera cópias mensais das despesas recorrentes';

    public function __construct(private ExpenseService $service, private ActivityService $activity)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $now = Carbon::now();

        // Templates: is_recurring = true e sem parent_id (não são cópias)
        $templates = Expense::where('is_recurring', true)
            ->whereNull('parent_id')
            ->get();

        foreach ($templates as $template) {
            // Verifica se já existe cópia no mês atual
            $alreadyExists = Expense::where('parent_id', $template->id)
                ->whereYear('expense_date', $now->year)
                ->whereMonth('expense_date', $now->month)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $expenseDate = $template->expense_date->copy()->setYear($now->year)->setMonth($now->month);
            $billingDate = $this->service->calculateBillingDate($template->card_id, $expenseDate);

            $copy = Expense::create([
                'couple_id'    => $template->couple_id,
                'paid_by'      => $template->paid_by,
                'card_id'      => $template->card_id,
                'description'  => $template->description,
                'category'     => $template->category,
                'amount'       => $template->amount,
                'expense_date' => $expenseDate,
                'billing_date' => $billingDate,
                'is_shared'    => $template->is_shared,
                'split_ratio'  => $template->split_ratio,
                'is_recurring' => false,
                'parent_id'    => $template->id,
            ]);

            $this->service->createBalances($copy);

            $payer = $copy->payer;
            if ($payer) {
                $this->activity->log(
                    $payer,
                    $copy->couple_id,
                    'created',
                    'expense',
                    $copy->id,
                    "Despesa recorrente gerada automaticamente: \"{$copy->description}\" (R$ " . number_format($copy->amount, 2, ',', '.') . ")"
                );
            }

            $this->info("Gerado: {$template->description} ({$expenseDate->format('m/Y')})");
        }

        $this->info('Despesas recorrentes processadas.');
    }
}
