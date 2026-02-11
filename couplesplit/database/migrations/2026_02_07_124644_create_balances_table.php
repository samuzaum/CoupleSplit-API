<?php

// database/migrations/xxxx_xx_xx_create_balances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('couple_id')->constrained()->cascadeOnDelete();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_user_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->decimal('used_amount', 10, 2)->default(0);

            $table->enum('type', ['credit', 'debit']);
            $table->enum('origin', ['expense', 'payment', 'adjustment']);

            $table->string('origin_table');
            $table->unsignedBigInteger('origin_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};
