<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work areas / document categories (e.g. IT Support, HR, QA).
 * Groups form templates in the register and the navigation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();     // e.g. IT
            $table->string('name');               // e.g. IT Support
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_categories');
    }
};
