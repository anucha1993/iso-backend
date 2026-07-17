<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce reusable "standard profiles" (e.g. Server/PC, UPS, Network).
 * Standards belong to a profile; each form template picks one profile (or none).
 * Existing global CPU/Mem/Disk standards move into a default profile which is
 * assigned to the server + client maintenance forms so nothing breaks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->foreignId('standard_profile_id')->nullable()->after('id');
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->foreignId('standard_profile_id')->nullable()->after('module_key');
        });

        // Move existing standards into a default profile.
        $profileId = DB::table('standard_profiles')->insertGetId([
            'name' => 'Server / PC (CPU/Mem/Disk)',
            'description' => 'เกณฑ์มาตรฐานเดิม (ย้ายอัตโนมัติ)',
            'order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('checklist_standards')->whereNull('standard_profile_id')->update(['standard_profile_id' => $profileId]);
        DB::table('form_templates')
            ->whereIn('module_key', ['server_maintenance', 'client_maintenance'])
            ->update(['standard_profile_id' => $profileId]);

        // Swap the unique index from (form_template_id, metric_key) to (profile, metric_key).
        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->dropUnique(['form_template_id', 'metric_key']);
            $table->unique(['standard_profile_id', 'metric_key']);
            $table->dropColumn('form_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->foreignId('form_template_id')->nullable()->after('id');
        });
        Schema::table('checklist_standards', function (Blueprint $table) {
            $table->dropUnique(['standard_profile_id', 'metric_key']);
            $table->unique(['form_template_id', 'metric_key']);
            $table->dropColumn('standard_profile_id');
        });
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('standard_profile_id');
        });
        Schema::dropIfExists('standard_profiles');
    }
};
