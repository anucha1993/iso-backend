<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\ChecklistStandard;
use App\Models\FormTemplate;
use Illuminate\Database\Seeder;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $templateId = FormTemplate::where('module_key', 'server_maintenance')->value('id');
        // FM-IT-02 Rev.05 — Server maintenance checklist. The ISO/IEC 27001:2022
        // Annex A reference is stored in `iso_control` (a tag), NOT in the name,
        // so it never appears on the printed form (PDF).
        $items = [
            ['order' => 1,  'iso' => 'A.8.8',  'name' => 'ตรวจสอบและติดตั้งแพตช์ OS / แอปพลิเคชัน / เฟิร์มแวร์'],
            ['order' => 2,  'iso' => 'A.8.8',  'name' => 'สแกนช่องโหว่ (Vulnerability Scan) และปิดช่องโหว่สำคัญ', 'frequency_note' => 'ทุก 3 เดือน'],
            ['order' => 3,  'iso' => 'A.8.7',  'name' => 'ตรวจ Antivirus/EDR: ทำงาน + อัปเดต Signature + ผลการสแกน'],
            ['order' => 4,  'iso' => 'A.8.14', 'name' => 'ตรวจฮาร์ดแวร์ + RAID/PSU + สุขภาพดิสก์ S.M.A.R.T.'],
            ['order' => 5,  'iso' => 'A.8.6',  'name' => 'ตรวจทรัพยากรระบบ CPU / Memory / Disk เทียบเกณฑ์'],
            ['order' => 6,  'iso' => 'A.8.20', 'name' => 'ตรวจระบบเครือข่าย / Firewall / พอร์ตที่เปิดใช้งาน'],
            ['order' => 7,  'iso' => 'A.5.18', 'name' => 'ทบทวนสิทธิ์บัญชีผู้ใช้ AD: บัญชีไม่ใช้งาน / สิทธิ์สูง / MFA', 'frequency_note' => 'ทุก 3 เดือน'],
            ['order' => 8,  'iso' => 'A.7.10', 'name' => 'ควบคุมสื่อบันทึกถอดได้ / ปิด USB Port ที่ไม่จำเป็น'],
            ['order' => 9,  'iso' => 'A.8.13', 'name' => 'ตรวจสถานะการสำรองข้อมูล + ทดสอบกู้คืน Restore', 'frequency_note' => 'ทดสอบกู้คืนทุก 3-6 เดือน'],
            ['order' => 10, 'iso' => 'A.8.15', 'name' => 'ตรวจ Log / Event ระบบและความปลอดภัย เช่น Login ล้มเหลว'],
            ['order' => 11, 'iso' => 'A.8.17', 'name' => 'ตรวจการซิงค์เวลา NTP / Time Synchronization'],
            ['order' => 12, 'iso' => 'A.8.24', 'name' => 'ตรวจใบรับรอง SSL/TLS (วันหมดอายุ) และการเข้ารหัส'],
            ['order' => 13, 'iso' => 'A.8.19', 'name' => 'ตรวจซอฟต์แวร์ที่ติดตั้ง / ซอฟต์แวร์ไม่ได้รับอนุญาต', 'frequency_note' => 'ทุก 3 เดือน'],
            ['order' => 14, 'iso' => 'A.7.11', 'name' => 'ตรวจระบบสนับสนุน: UPS / ไฟฟ้า + อุณหภูมิห้อง Server'],
            ['order' => 15, 'iso' => 'A.7.13', 'name' => 'ทำความสะอาดกายภาพ เป่าฝุ่น เช็คทำความสะอาดเคส', 'frequency_note' => 'อย่างน้อย 1 ครั้ง/ปี'],
        ];

        foreach ($items as $item) {
            ChecklistItem::updateOrCreate(
                ['form_template_id' => $templateId, 'order' => $item['order']],
                [
                    'name' => $item['name'],
                    'frequency_note' => $item['frequency_note'] ?? null,
                    'iso_control' => $item['iso'],
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
