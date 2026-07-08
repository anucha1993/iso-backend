<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One maintenance record per server per year (FM-IT-02).
 * Holds workflow status + immutable signature snapshots for
 * preparer (ผู้ตรวจเช็ค) and approver (ผู้ตรวจสอบ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');        // Buddhist era, e.g. 2569
            $table->string('responsible')->nullable();   // ผู้รับผิดชอบ snapshot
            $table->string('status')->default('draft')->index(); // draft, submitted, approved, rejected

            // ผู้จัดทำ / ผู้ตรวจเช็ค (preparer)
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prepared_name')->nullable();
            $table->string('prepared_position')->nullable();
            $table->string('prepared_signature_path')->nullable();
            $table->timestamp('prepared_signed_at')->nullable();

            // ผู้อนุมัติ / ผู้ตรวจสอบ (approver)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_name')->nullable();
            $table->string('approved_position')->nullable();
            $table->string('approved_signature_path')->nullable();
            $table->timestamp('approved_signed_at')->nullable();

            $table->string('rejected_reason')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
