<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@stu.edu.gh'],
            [
                'name' => 'System Administrator',
                'phone' => '0234567890',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create HR user
        $hr = User::firstOrCreate(
            ['email' => 'hr@stu.edu.gh'],
            [
                'name' => 'HR Manager',
                'phone' => '0234567891',
                'password' => Hash::make('hr123'),
                'email_verified_at' => now(),
            ]
        );
        $hr->assignRole('hr');

        // Create Security user
        $security = User::firstOrCreate(
            ['email' => 'security@stu.edu.gh'],
            [
                'name' => 'Security Officer',
                'phone' => '0234567892',
                'password' => Hash::make('security123'),
                'email_verified_at' => now(),
            ]
        );
        $security->assignRole('security');

        // Create Auditor user
        $auditor = User::firstOrCreate(
            ['email' => 'auditor@stu.edu.gh'],
            [
                'name' => 'System Auditor',
                'phone' => '0234567893',
                'password' => Hash::make('auditor123'),
                'email_verified_at' => now(),
            ]
        );
        $auditor->assignRole('auditor');

        $this->command->info('Default users created successfully!');
        $this->command->info('Admin: admin@stu.edu.gh / admin123');
        $this->command->info('HR: hr@stu.edu.gh / hr123');
        $this->command->info('Security: security@stu.edu.gh / security123');
        $this->command->info('Auditor: auditor@stu.edu.gh / auditor123');
    }
}
