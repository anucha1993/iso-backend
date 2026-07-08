<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-form tags / badges (แท็ก) for the form register so forms can be labelled
 * (e.g. "รายเดือน", "สำคัญ", "ISO") and shown as coloured badges.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('frequency_note');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
