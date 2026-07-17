<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make FM-IT-03 (client maintenance) checklist DB-driven so it can be edited
 * from the /admin/checklist UI. Adds a stable `key` to checklist_items and
 * seeds the 7 existing hardcoded tasks with their original keys, so existing
 * client_ma_entries.tasks (keyed by those strings) keep matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('checklist_items', 'key')) {
            Schema::table('checklist_items', function (Blueprint $table) {
                $table->string('key')->nullable()->after('form_template_id');
            });
        }

        $formId = DB::table('form_templates')->where('module_key', 'client_maintenance')->value('id');
        if (! $formId) {
            return;
        }

        $tasks = [
            ['key' => 'patch', 'name' => 'อัปเดต Windows / แพตช์ความปลอดภัย'],
            ['key' => 'antivirus', 'name' => 'อัปเดต Antivirus / EDR + สแกน'],
            ['key' => 'disk_cleanup', 'name' => 'ล้างไฟล์ขยะ / Temp'],
            ['key' => 'disk_space', 'name' => 'ตรวจพื้นที่ว่างดิสก์'],
            ['key' => 'software', 'name' => 'อัปเดตซอฟต์แวร์ third-party'],
            ['key' => 'reboot', 'name' => 'รีสตาร์ทให้แพตช์สมบูรณ์'],
            ['key' => 'agent', 'name' => 'ตรวจสถานะ Action1 agent'],
        ];

        $order = 1;
        foreach ($tasks as $t) {
            $exists = DB::table('checklist_items')
                ->where('form_template_id', $formId)
                ->where('key', $t['key'])
                ->exists();
            if (! $exists) {
                DB::table('checklist_items')->insert([
                    'form_template_id' => $formId,
                    'key' => $t['key'],
                    'name' => $t['name'],
                    'order' => $order,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $order++;
        }
    }

    public function down(): void
    {
        // Non-destructive: keep the column and seeded rows.
    }
};
