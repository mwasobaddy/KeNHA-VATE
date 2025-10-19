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

            // created based on application needs
            // idea
            'review_ideas',
            'delete_ideas', //admin and manager only
            
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $developerRole = Role::firstOrCreate(['name' => 'developer']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Assign permissions to roles
        $developerRole->syncPermissions(Permission::all());

        $supervisorRole->syncPermissions([
            'view dashboard',
            'view profile',
            'edit profile',
            'view notifications',
            'manage staff',
            'approve staff',
        ]);

        $userRole->syncPermissions([
            'view dashboard',
            'view profile',
            'edit profile',
            'view notifications',
        ]);
    }
}
