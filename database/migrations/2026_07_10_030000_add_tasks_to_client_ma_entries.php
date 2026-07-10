<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_ma_entries', function (Blueprint $table) {
            $table->json('tasks')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('client_ma_entries', function (Blueprint $table) {
            $table->dropColumn('tasks');
        });
    }
};
