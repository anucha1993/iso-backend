<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-month approval rounds (รอบการอนุมัติรายเดือน). Each month of a maintenance
 * record has its own status + signatures so months can be submitted / approved
 * independently and past months lock once submitted/approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_record_id')->constrained('maintenance_records')->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1-12
            $table->string('status')->default('draft'); // draft | submitted | approved | rejected

            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prepared_name')->nullable();
            $table->string('prepared_position')->nullable();
            $table->string('prepared_signature_path')->nullable();
            $table->timestamp('prepared_signed_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_name')->nullable();
            $table->string('approved_position')->nullable();
            $table->string('approved_signature_path')->nullable();
            $table->timestamp('approved_signed_at')->nullable();

            $table->string('rejected_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();
            $table->unique(['maintenance_record_id', 'month']);
        });

        // Backfill 12 draft rounds for every existing record.
        $now = now();
        $records = DB::table('maintenance_records')->pluck('id');
        $rows = [];
        foreach ($records as $recordId) {
            foreach (range(1, 12) as $m) {
                $rows[] = [
                    'maintenance_record_id' => $recordId,
                    'month' => $m,
                    'status' => 'draft',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if ($rows !== []) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('maintenance_rounds')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_rounds');
    }
};
