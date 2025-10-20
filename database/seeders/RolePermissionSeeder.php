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

            // Idea permissions
            'review_ideas',
            'delete_ideas', //admin and manager only

            // Collaboration permissions
            'manage_collaboration', // enable/disable collaboration on ideas
            'invite_collaborators', // send collaboration invitations
            'manage_collaborators', // add/remove collaborators, change permissions
            'create_revisions', // create revision suggestions
            'manage_revisions', // accept/reject/rollback revisions
            'view_collaboration_requests', // view incoming collaboration requests
            'respond_to_collaboration_requests', // accept/decline collaboration requests
            'view_collaboration_activity', // view collaboration history and audit logs
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
            'manage_collaboration',
            'manage_collaborators',
            'manage_revisions',
            'view_collaboration_requests',
            'respond_to_collaboration_requests',
            'view_collaboration_activity',
        ]);

        $userRole->syncPermissions([
            'view dashboard',
            'view profile',
            'edit profile',
            'view notifications',
            'invite_collaborators',
            'create_revisions',
            'view_collaboration_requests',
            'respond_to_collaboration_requests',
            'view_collaboration_activity',
        ]);
    }
}
