<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            // วิธีการตรวจเช็ค — how to inspect this item (procedure/steps)
            $table->text('check_method')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn('check_method');
        });
    }
};
