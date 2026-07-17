<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot numeric metrics (CPU/Mem/Disk ฯลฯ) captured per machine per month.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_ma_entries', function (Blueprint $table) {
            $table->json('metrics')->nullable()->after('tasks');
        });
    }

    public function down(): void
    {
        Schema::table('client_ma_entries', function (Blueprint $table) {
            $table->dropColumn('metrics');
        });
    }
};
