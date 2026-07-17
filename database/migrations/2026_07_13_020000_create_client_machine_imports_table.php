<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_machine_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->string('uploaded_by_name')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('changes')->nullable(); // per-machine field-level diffs
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_machine_imports');
    }
};
