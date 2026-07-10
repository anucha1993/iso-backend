<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scope each checklist item to a form template so the system knows which
 * checklist belongs to which form (managed from the form register).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->foreignId('form_template_id')->nullable()->after('id')
                ->constrained('form_templates')->nullOnDelete();
        });

        // Backfill existing items to the Server maintenance form (FM-IT-02).
        $templateId = DB::table('form_templates')->where('module_key', 'server_maintenance')->value('id');
        if ($templateId) {
            DB::table('checklist_items')->whereNull('form_template_id')->update(['form_template_id' => $templateId]);
        }
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_template_id');
        });
    }
};
