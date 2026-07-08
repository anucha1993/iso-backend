<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly measured readings + free-text maintenance note (การปรับปรุงรักษา).
 * cpu_load / memory_free / disk_free are analysed against checklist_standards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_record_id')->constrained('maintenance_records')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');        // 1-12
            $table->date('check_date')->nullable();      // วันที่ตรวจเช็ค
            $table->decimal('cpu_load', 5, 2)->nullable();     // CPU load usage (%)
            $table->decimal('memory_free', 5, 2)->nullable();  // Memory physical free (%)
            $table->decimal('disk_free', 5, 2)->nullable();    // Disk free (%)
            $table->text('note')->nullable();            // การปรับปรุงรักษา
            $table->timestamps();

            $table->unique(['maintenance_record_id', 'month'], 'reading_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_readings');
    }
};
