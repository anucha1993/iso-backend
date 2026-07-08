<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Form-level check frequency note (ความถี่ในการตรวจเช็ค) for the form register,
 * e.g. "ทุกเดือน", "ทุก 3 เดือน", "ปีละ 1 ครั้ง". Informs users how many rounds
 * the form requires and pairs with the per-month evidence attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->string('frequency_note')->nullable()->after('revision');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('frequency_note');
        });
    }
};
