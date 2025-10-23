<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create roles first
        $this->call(RolePermissionSeeder::class);
        
        // Create admin user
        $this->call(AdminUserSeeder::class);
        
        // Demo data for development
        if (app()->isLocal()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
