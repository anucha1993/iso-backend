<?php

namespace Database\Seeders;

use App\Models\ClientMachine;
use Illuminate\Database\Seeder;

/**
 * A handful of sample client machines for FM-IT-03.
 * The real ~200 machines are added/imported via the UI.
 */
class ClientMachineSeeder extends Seeder
{
    public function run(): void
    {
        $depts = ['บัญชี', 'จัดซื้อ', 'บุคคล', 'ขาย', 'คลังสินค้า', 'ผลิต'];

        for ($i = 1; $i <= 12; $i++) {
            ClientMachine::updateOrCreate(
                ['name' => sprintf('PC-%03d', $i)],
                [
                    'asset_tag' => sprintf('AST-%04d', $i),
                    'owner' => 'ผู้ใช้งาน '.$i,
                    'department' => $depts[($i - 1) % count($depts)],
                    'floor' => 'ชั้น '.(intdiv($i - 1, 4) + 1),
                    'os' => 'Windows 11 Pro',
                    'is_active' => true,
                ],
            );
        }
    }
}
