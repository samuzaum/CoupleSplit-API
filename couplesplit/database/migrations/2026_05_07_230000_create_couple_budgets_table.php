<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couple_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('couple_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['couple_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couple_budgets');
    }
};
