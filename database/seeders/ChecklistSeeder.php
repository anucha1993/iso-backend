<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\ChecklistStandard;
use Illuminate\Database\Seeder;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['order' => 1, 'name' => 'ตรวจสอบการอัพเดท Windows Server'],
            ['order' => 2, 'name' => 'ทำการ Defragmenter ฮาร์ดิส'],
            ['order' => 3, 'name' => 'ตรวจสอบการทำงานของโปรแกรมสแกนไวรัส'],
            ['order' => 4, 'name' => 'ตรวจสอบความผิดปกติของ Hardware'],
            ['order' => 5, 'name' => 'Memory Check'],
            ['order' => 6, 'name' => 'Network Check'],
            ['order' => 7, 'name' => 'Hard Drive Check พื้นที่ที่เหลือ'],
            ['order' => 8, 'name' => 'ตรวจสอบสิทธิ์ Users AD'],
            ['order' => 9, 'name' => 'ตรวจสอบการปิด Port USB'],
            ['order' => 10, 'name' => 'ตรวจสอบ Virus และ Malware'],
            ['order' => 11, 'name' => 'ทำความสะอาดต่างๆ อาทิ เป่าฝุ่น เช็คทำความสะอาดเคส', 'frequency_note' => 'อย่างน้อย 1 ครั้ง/ปี'],
            ['order' => 12, 'name' => 'ตรวจสอบสถาณะ File Backup'],
            ['order' => 13, 'name' => 'เปลี่ยนรหัส Server', 'frequency_note' => '3 เดือนครั้ง'],
        ];

        foreach ($items as $item) {
            ChecklistItem::updateOrCreate(
                ['order' => $item['order']],
                [
                    'name' => $item['name'],
                    'frequency_note' => $item['frequency_note'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $standards = [
            [
                'metric_key' => 'cpu_load',
                'label' => 'CPU: Load usage (%)',
                'direction' => 'lower_better',
                'warn_threshold' => 50,
                'critical_threshold' => 80,
                'normal_text' => '0-50%',
                'risk_text' => '50-80%',
                'fault_text' => 'มากกว่า 80%',
                'order' => 1,
            ],
            [
                'metric_key' => 'memory_used',
                'label' => 'Memory: Usage (%)',
                'direction' => 'lower_better',
                'warn_threshold' => 70,
                'critical_threshold' => 90,
                'normal_text' => '0-70%',
                'risk_text' => '70-90%',
                'fault_text' => 'มากกว่า 90%',
                'order' => 2,
            ],
            [
                'metric_key' => 'disk_used',
                'label' => 'Disk: Usage (%)',
                'direction' => 'lower_better',
                'warn_threshold' => 70,
                'critical_threshold' => 90,
                'normal_text' => '0-70%',
                'risk_text' => '70-90%',
                'fault_text' => 'มากกว่า 90%',
                'order' => 3,
            ],
        ];

        foreach ($standards as $standard) {
            ChecklistStandard::updateOrCreate(
                ['metric_key' => $standard['metric_key']],
                $standard + ['unit' => '%', 'is_active' => true],
            );
        }
    }
}
