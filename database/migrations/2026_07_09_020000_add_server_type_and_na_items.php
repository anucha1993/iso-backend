<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Server classification (ประเภท Server: Web / File / AD ...) and a per-record
 * list of checklist items marked N/A (ไม่เกี่ยวข้อง) for that server, so the
 * printed form shows every item but flags the ones that don't apply.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('server_type')->nullable()->after('name');
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->json('na_checklist_items')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('server_type');
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn('na_checklist_items');
        });
    }
};
