<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions by module
        $permissions = [
            // User Management
            'users.view',
            'users.list',
            'users.create',
            'users.edit',
            'users.delete',
            'users.assign-roles',

            // Role Management
            'roles.view',
            'roles.list',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign-permissions',

            // Permission Management
            'permissions.view',
            'permissions.list',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',

            // Invoice Management
            'invoices.view',
            'invoices.list',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'invoices.validate',

            // Document Management
            'documents.view',
            'documents.list',
            'documents.create',
            'documents.edit',
            'documents.delete',
            'documents.import',
            'document.edit-cur_loc',

            // Distribution Management
            'distribution.view',
            'distribution.manage',

            // Master Data Management
            'master.view',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'document-types.view',
            'document-types.create',
            'document-types.edit',
            'document-types.delete',
            'invoice-types.view',
            'invoice-types.create',
            'invoice-types.edit',
            'invoice-types.delete',
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.delete',
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',
            'reports.view',
            'reports.invoices.view',
            'reports.documents.view',
            'reports.distributions.view',

            // Admin Access
            'admin.access',
            'admin.full-access',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Create basic roles
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        $superAdminRole = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web'
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web'
        ]);

        $managerRole = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web'
        ]);

        // Assign permissions to roles

        // Super Admin gets all permissions
        $superAdminRole->givePermissionTo(Permission::all());

        // Admin gets most permissions except super admin specific ones
        $adminPermissions = Permission::whereNotIn('name', [
            'admin.full-access'
        ])->get();
        $adminRole->givePermissionTo($adminPermissions);

        // Manager gets limited permissions
        $managerPermissions = [
            'users.view',
            'users.list',
            'invoices.view',
            'invoices.list',
            'invoices.create',
            'invoices.edit',
            'documents.view',
            'documents.list',
            'documents.create',
            'documents.edit',
            'documents.import',
            'document.edit-cur_loc',
            'distribution.view',
            'distribution.manage',
            'suppliers.view',
            'projects.view',
            'departments.view',
            'reports.view',
            'reports.invoices.view',
            'reports.documents.view',
            'reports.distributions.view',
        ];
        $managerRole->givePermissionTo($managerPermissions);

        // User gets basic view permissions
        $userPermissions = [
            'invoices.view',
            'invoices.list',
            'documents.view',
            'documents.list',
            'distribution.view',
            'suppliers.view',
            'projects.view',
            'departments.view',
        ];
        $userRole->givePermissionTo($userPermissions);

        $this->command->info('Permissions and roles created successfully!');
    }
}
