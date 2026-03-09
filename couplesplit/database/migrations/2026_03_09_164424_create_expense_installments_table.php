<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_installments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('expense_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('installment_number');

            $table->decimal('amount',10,2);

            $table->date('due_date');

            $table->date('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_installments');
    }
};