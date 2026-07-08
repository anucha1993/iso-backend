<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidence files (images / PDF) attached to a maintenance record so the
 * reviewer (ผู้ตรวจสอบ) can open and monitor them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_record_id')->constrained('maintenance_records')->cascadeOnDelete();
            $table->unsignedTinyInteger('month')->nullable();     // optional link to a month (1-12)
            $table->string('caption')->nullable();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_attachments');
    }
};
