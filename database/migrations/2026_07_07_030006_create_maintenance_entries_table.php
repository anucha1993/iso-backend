<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The check grid: for each checklist item and each month (1-12),
 * the result — checked ('/'), fault ('X') or null (not checked).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_record_id')->constrained('maintenance_records')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained('checklist_items')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');        // 1-12
            $table->enum('status', ['checked', 'fault'])->nullable(); // '/' , 'X' , null
            $table->timestamps();

            $table->unique(['maintenance_record_id', 'checklist_item_id', 'month'], 'entry_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_entries');
    }
};
