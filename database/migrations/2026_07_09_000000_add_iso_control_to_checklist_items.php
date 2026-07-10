<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ISO/IEC 27001:2022 Annex A reference tag for each checklist item.
 * Kept separate from the item name so it can be shown as a badge in the UI
 * but excluded from the printed form (PDF).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->string('iso_control')->nullable()->after('frequency_note');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn('iso_control');
        });
    }
};
