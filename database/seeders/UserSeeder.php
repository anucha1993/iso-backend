<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@iso.local'],
            [
                'name' => 'ผู้ดูแลระบบ',
                'password' => Hash::make('Password123!'),
                'position' => 'ผู้ดูแลระบบ (IT Administrator)',
                'employee_code' => 'ADM-001',
                'is_active' => true,
            ],
        );
        $admin->syncRoles(['admin']);

        $approver = User::updateOrCreate(
            ['email' => 'approver@iso.local'],
            [
                'name' => 'นายอนุชา โยธานันท์',
                'password' => Hash::make('Password123!'),
                'position' => 'หัวหน้าฝ่าย IT Support',
                'employee_code' => 'APV-001',
                'is_active' => true,
            ],
        );
        $approver->syncRoles(['approver']);

        $preparer = User::updateOrCreate(
            ['email' => 'preparer@iso.local'],
            [
                'name' => 'เจ้าหน้าที่ IT Support',
                'password' => Hash::make('Password123!'),
                'position' => 'IT Support',
                'employee_code' => 'PRP-001',
                'is_active' => true,
            ],
        );
        $preparer->syncRoles(['preparer']);
    }
}
