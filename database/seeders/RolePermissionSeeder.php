<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'access dashboard',
            
            // Kiosk
            'access kiosk',
            'process checkout',
            'process checkin',
            
            // Keys
            'view keys',
            'manage keys',
            'generate qr codes',
            'mark keys lost',
            
            // Locations
            'view locations',
            'manage locations',
            
            // HR
            'view hr',
            'manage hr',
            'import staff',
            'resolve discrepancies',
            
            // Reports
            'view reports',
            'view analytics',
            'export data',
            
            // Users
            'view users',
            'manage users',
            'manage roles',
            
            // System
            'manage settings',
            'view system health',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        $hrRole = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hrRole->syncPermissions([
            'access dashboard',
            'view keys',
            'view locations',
            'view hr',
            'manage hr',
            'import staff',
            'resolve discrepancies',
            'view reports',
            'view analytics',
            'export data',
        ]);

        $securityRole = Role::firstOrCreate(['name' => 'security', 'guard_name' => 'web']);
        $securityRole->syncPermissions([
            'access dashboard',
            'access kiosk',
            'process checkout',
            'process checkin',
            'view keys',
            'view locations',
            'mark keys lost',
            'view reports',
        ]);

        $auditorRole = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditorRole->syncPermissions([
            'access dashboard',
            'view keys',
            'view locations',
            'view hr',
            'view reports',
            'view analytics',
            'export data',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
