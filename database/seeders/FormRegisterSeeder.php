<?php

namespace Database\Seeders;

use App\Models\FormCategory;
use App\Models\FormTemplate;
use Illuminate\Database\Seeder;

class FormRegisterSeeder extends Seeder
{
    public function run(): void
    {
        $it = FormCategory::updateOrCreate(
            ['code' => 'IT'],
            [
                'name' => 'IT Support',
                'description' => 'งานสารสนเทศและโครงสร้างพื้นฐาน',
                'icon' => 'server',
                'order' => 1,
                'is_active' => true,
            ],
        );

        FormTemplate::updateOrCreate(
            ['code' => 'FM-IT-02'],
            [
                'form_category_id' => $it->id,
                'name' => 'บำรุงรักษา Server',
                'revision' => '04',
                'frequency_note' => 'ทุกเดือน (รายเดือน)',
                'tags' => ['รายเดือน', 'IT', 'Server'],
                'module_key' => 'server_maintenance',
                'route' => '/records',
                'description' => 'บันทึกข้อมูลการบำรุงรักษาระบบ Server รายเดือน พร้อมวิเคราะห์ CPU/Memory/Disk',
                'confidentiality' => 'confidential',
                'effective_date' => '2024-01-01',
                'order' => 1,
                'is_active' => true,
            ],
        );
    }
}
