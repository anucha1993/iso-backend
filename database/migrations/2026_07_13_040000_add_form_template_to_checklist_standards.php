<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow threshold standards to be scoped per form template.
 * form_template_id NULL = global default set (shared by any form without its own).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->foreignId('form_template_id')->nullable()->after('id');
        });

        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->dropUnique(['metric_key']);
            $table->unique(['form_template_id', 'metric_key']);
        });
    }

    public function down(): void
    {
        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->dropUnique(['form_template_id', 'metric_key']);
            $table->unique(['metric_key']);
        });

        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->dropColumn('form_template_id');
        });
    }
};
