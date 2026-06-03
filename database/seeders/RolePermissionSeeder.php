<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings\Role;
use App\Models\Settings\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Daftar permission per modul.
     */
    private array $permissions = [
        // Dashboard
        'dashboard.view',

        // Invoices
        'invoices.view',
        'invoices.create',
        'invoices.edit',
        'invoices.delete',
        'invoices.post',

        // Customers
        'customers.view',
        'customers.create',
        'customers.edit',
        'customers.delete',

        // Reports
        'reports.view',
        'reports.export',

        // Settings
        'settings.view',
        'settings.companies.manage',
        'settings.taxes.manage',
        'settings.invoice-settings.manage',
        'settings.users.manage',
        'settings.roles.manage',
    ];

    /**
     * Role dan permission yang diberikan ke masing-masing role.
     */
    private array $roles = [
        'super-admin' => null, // null = semua permission
        'admin' => [
            'dashboard.view',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.post',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'reports.view', 'reports.export',
            'settings.view', 'settings.companies.manage', 'settings.taxes.manage',
            'settings.invoice-settings.manage', 'settings.users.manage',
        ],
        'staff' => [
            'dashboard.view',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'customers.view', 'customers.create', 'customers.edit',
            'reports.view',
        ],
    ];

    public function run(): void
    {
        // Buat semua permission
        foreach ($this->permissions as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web']
            );
        }

        $this->command->info('✅ ' . count($this->permissions) . ' permissions created.');

        // Buat role dan assign permission
        foreach ($this->roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            if ($rolePermissions === null) {
                // super-admin: semua permission
                $role->syncPermissions(Permission::all());
                $this->command->info("✅ Role [{$roleName}] assigned ALL permissions.");
            } else {
                $role->syncPermissions($rolePermissions);
                $this->command->info("✅ Role [{$roleName}] assigned " . count($rolePermissions) . " permissions.");
            }
        }
    }
}
