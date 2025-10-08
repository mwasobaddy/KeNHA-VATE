<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view dashboard',
            'view profile',
            'edit profile',
            'view notifications',
            'manage staff',
            'approve staff',
            'view reports',
            'manage users',
            'manage roles',
            'manage permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor']);

        // Assign permissions to roles
        $userRole->syncPermissions([
            'view dashboard',
            'view profile',
            'edit profile',
            'view notifications',
        ]);

        $supervisorRole->syncPermissions([
            'view dashboard',
            'view profile',
            'edit profile',
            'view notifications',
            'manage staff',
            'approve staff',
        ]);

        $adminRole->syncPermissions(Permission::all());
    }
}
