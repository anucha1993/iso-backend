<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Form register (ทะเบียนแบบฟอร์ม). Each row is a document/form the system
 * supports. `module_key` maps to the code/UI module that implements it,
 * `route` is where the frontend navigates for that form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_category_id')->constrained('form_categories')->cascadeOnDelete();
            $table->string('code')->unique();     // e.g. FM-IT-02
            $table->string('name');               // e.g. บันทึกการบำรุงรักษา Server
            $table->string('revision')->nullable(); // e.g. 04
            $table->string('module_key');         // e.g. server_maintenance
            $table->string('route')->nullable();  // e.g. /records
            $table->string('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
