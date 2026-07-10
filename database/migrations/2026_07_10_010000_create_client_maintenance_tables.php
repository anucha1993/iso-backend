<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master list of client machines (~200 PCs).
        Schema::create('client_machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('asset_tag')->nullable();
            $table->string('owner')->nullable();
            $table->string('department')->nullable();
            $table->string('os')->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Monthly batch maintenance record (FM-IT-03).
        Schema::create('client_ma_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->nullable();
            $table->integer('year');
            $table->unsignedTinyInteger('month');
            $table->string('responsible')->nullable();
            $table->json('tasks')->nullable();
            $table->json('report_files')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by')->nullable();
            $table->string('prepared_name')->nullable();
            $table->string('prepared_position')->nullable();
            $table->string('prepared_signature_path')->nullable();
            $table->timestamp('prepared_signed_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->string('approved_name')->nullable();
            $table->string('approved_position')->nullable();
            $table->string('approved_signature_path')->nullable();
            $table->timestamp('approved_signed_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->unique(['year', 'month']);
        });

        // Per-machine status within a monthly record.
        Schema::create('client_ma_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_ma_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_machine_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('done'); // done | issue | na
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['client_ma_record_id', 'client_machine_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_ma_entries');
        Schema::dropIfExists('client_ma_records');
        Schema::dropIfExists('client_machines');
    }
};
