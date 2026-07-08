<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Switch the Memory and Disk metrics from "Free %" (higher is better) to
 * "Usage %" (higher is worse) so all three metrics (CPU/Memory/Disk) are
 * consistently usage-based. Existing readings are converted: used = 100 - free.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_readings', function (Blueprint $table) {
            $table->renameColumn('memory_free', 'memory_used');
            $table->renameColumn('disk_free', 'disk_used');
        });

        DB::table('maintenance_readings')->whereNotNull('memory_used')
            ->update(['memory_used' => DB::raw('100 - memory_used')]);
        DB::table('maintenance_readings')->whereNotNull('disk_used')
            ->update(['disk_used' => DB::raw('100 - disk_used')]);

        DB::table('checklist_standards')->where('metric_key', 'memory_free')->update([
            'metric_key' => 'memory_used',
            'label' => 'Memory: Usage (%)',
            'direction' => 'lower_better',
            'warn_threshold' => 70,
            'critical_threshold' => 90,
            'normal_text' => '0-70%',
            'risk_text' => '70-90%',
            'fault_text' => 'มากกว่า 90%',
        ]);
        DB::table('checklist_standards')->where('metric_key', 'disk_free')->update([
            'metric_key' => 'disk_used',
            'label' => 'Disk: Usage (%)',
            'direction' => 'lower_better',
            'warn_threshold' => 70,
            'critical_threshold' => 90,
            'normal_text' => '0-70%',
            'risk_text' => '70-90%',
            'fault_text' => 'มากกว่า 90%',
        ]);
    }

    public function down(): void
    {
        DB::table('checklist_standards')->where('metric_key', 'memory_used')->update([
            'metric_key' => 'memory_free',
            'label' => 'Memory: Physical Free (%)',
            'direction' => 'higher_better',
            'warn_threshold' => 30,
            'critical_threshold' => 10,
            'normal_text' => 'มากกว่า 30%',
            'risk_text' => '10-30%',
            'fault_text' => 'น้อยกว่า 10%',
        ]);
        DB::table('checklist_standards')->where('metric_key', 'disk_used')->update([
            'metric_key' => 'disk_free',
            'label' => 'Disk free / (%)',
            'direction' => 'higher_better',
            'warn_threshold' => 30,
            'critical_threshold' => 10,
            'normal_text' => 'มากกว่า 30%',
            'risk_text' => '10-30%',
            'fault_text' => 'น้อยกว่า 10%',
        ]);

        DB::table('maintenance_readings')->whereNotNull('memory_used')
            ->update(['memory_used' => DB::raw('100 - memory_used')]);
        DB::table('maintenance_readings')->whereNotNull('disk_used')
            ->update(['disk_used' => DB::raw('100 - disk_used')]);

        Schema::table('maintenance_readings', function (Blueprint $table) {
            $table->renameColumn('memory_used', 'memory_free');
            $table->renameColumn('disk_used', 'disk_free');
        });
    }
};
