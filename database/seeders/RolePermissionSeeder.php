<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.manage',
            'servers.manage',
            'records.view',
            'records.create',
            'records.update',
            'records.submit',
            'records.approve',
            'analysis.view',
            'audit.view',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Flush the cache so newly-created permissions are resolvable when syncing to roles.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        $approver = Role::findOrCreate('approver', 'web'); // ผู้ตรวจสอบ / ผู้อนุมัติ (หัวหน้า)
        $approver->syncPermissions([
            'records.view',
            'records.update', // can override-edit locked months
            'records.approve',
            'analysis.view',
        ]);

        $preparer = Role::findOrCreate('preparer', 'web'); // ผู้จัดทำ / ผู้ตรวจเช็ค
        $preparer->syncPermissions([
            'records.view',
            'records.create',
            'records.update',
            'records.submit',
            'analysis.view',
        ]);
    }
}
