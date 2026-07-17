<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_ma_entries', function (Blueprint $table) {
            $table->string('snap_name')->nullable()->after('client_machine_id');
            $table->string('snap_owner')->nullable()->after('snap_name');
            $table->string('snap_floor')->nullable()->after('snap_owner');
            $table->string('snap_department')->nullable()->after('snap_floor');
        });

        // Backfill existing entries with the machine's current master data.
        DB::statement('UPDATE client_ma_entries e JOIN client_machines m ON e.client_machine_id = m.id '
            .'SET e.snap_name = m.name, e.snap_owner = m.owner, e.snap_floor = m.floor, e.snap_department = m.department');
    }

    public function down(): void
    {
        Schema::table('client_ma_entries', function (Blueprint $table) {
            $table->dropColumn(['snap_name', 'snap_owner', 'snap_floor', 'snap_department']);
        });
    }
};
