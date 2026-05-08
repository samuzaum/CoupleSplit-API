<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('split_ratio', 5, 4)->default(0.5000)->after('is_shared');
            $table->boolean('is_recurring')->default(false)->after('split_ratio');
            $table->foreignId('parent_id')->nullable()->constrained('expenses')->nullOnDelete()->after('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['split_ratio', 'is_recurring', 'parent_id']);
        });
    }
};
