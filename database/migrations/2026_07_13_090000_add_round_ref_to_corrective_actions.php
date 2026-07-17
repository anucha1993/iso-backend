<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrective_actions', function (Blueprint $table) {
            // Optional round tag (e.g. "ก.ค. 2569") — the inspection round the finding belongs to
            $table->string('round_ref')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('corrective_actions', function (Blueprint $table) {
            $table->dropColumn('round_ref');
        });
    }
};
