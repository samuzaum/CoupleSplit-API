<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_benefits', function (Blueprint $table) {
            $table->foreignId('couple_id')->nullable()->after('user_id')
                  ->constrained()->nullOnDelete();
            $table->boolean('is_couple')->default(false)->after('monthly_amount');
        });
    }

    public function down(): void
    {
        Schema::table('user_benefits', function (Blueprint $table) {
            $table->dropForeign(['couple_id']);
            $table->dropColumn(['couple_id', 'is_couple']);
        });
    }
};
