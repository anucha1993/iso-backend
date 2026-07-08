<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link each server-maintenance record to its form-register template so the
 * dashboard/register can aggregate generically across future forms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->foreignId('form_template_id')->nullable()->after('id')
                ->constrained('form_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropForeign(['form_template_id']);
            $table->dropColumn('form_template_id');
        });
    }
};
