<?php

namespace Database\Seeders;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
 public function run()
    {
        // الصلاحيات
        Permission::firstOrCreate(['name' => 'submit complaint']);
        Permission::firstOrCreate(['name' => 'view complaint']);
        Permission::firstOrCreate(['name' => 'update complaint']);
        Permission::firstOrCreate(['name' => 'manage users']);
        Permission::firstOrCreate(['name' => 'manage roles']);

        // الأدوار
        $citizen = Role::firstOrCreate(['name' => 'citizen']);
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $admin = Role::firstOrCreate(['name' => 'admin']);

        // ربط صلاحيات بالأدوار
        $citizen->givePermissionTo(['submit complaint', 'view complaint']);
        $employee->givePermissionTo(['view complaint', 'update complaint']);
        $admin->givePermissionTo(Permission::all());
    }
}
