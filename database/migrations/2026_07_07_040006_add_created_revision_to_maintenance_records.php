<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the form revision a record was created under, so we can trace back
 * "which Rev this data was made in" even after the template Rev is bumped.
 * The template/register always shows the latest Rev; this is only a note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->string('created_revision')->nullable()->after('form_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn('created_revision');
        });
    }
};
