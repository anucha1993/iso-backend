<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Threshold standards used to classify monthly readings into
 * ปกติ (normal) / เสี่ยง (risk) / ขัดข้อง (fault).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_standards', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key')->unique();      // cpu_load, memory_free, disk_free
            $table->string('label');                     // "CPU: Load usage (%)"
            $table->string('unit')->default('%');
            $table->enum('direction', ['lower_better', 'higher_better']);
            $table->decimal('warn_threshold', 8, 2);     // boundary normal|risk
            $table->decimal('critical_threshold', 8, 2); // boundary risk|fault
            $table->string('normal_text')->nullable();   // display text ปกติ
            $table->string('risk_text')->nullable();     // display text เสี่ยง
            $table->string('fault_text')->nullable();    // display text ขัดข้อง
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_standards');
    }
};
