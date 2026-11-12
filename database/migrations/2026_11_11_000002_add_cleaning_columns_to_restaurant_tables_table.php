<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->timestamp('cleaning_started_at')->nullable()->after('status');
            $table->foreignId('ready_confirmed_by')->nullable()->after('cleaning_started_at')->constrained('users')->nullOnDelete();
            $table->timestamp('ready_confirmed_at')->nullable()->after('ready_confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropForeign(['ready_confirmed_by']);
            $table->dropColumn(['cleaning_started_at', 'ready_confirmed_by', 'ready_confirmed_at']);
        });
    }
};
