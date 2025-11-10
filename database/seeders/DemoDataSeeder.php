<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Key;
use App\Models\KeyTag;
use App\Models\HrStaff;
use App\Models\PermanentStaffManual;
use App\Models\TemporaryStaff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // Create locations
        $locations = [
            [
                'name' => 'Main Office',
                'campus' => 'Main Campus',
                'building' => 'Administration Block',
                'room' => '101',
                'description' => 'Main administrative office',
            ],
            [
                'name' => 'IT Department',
                'campus' => 'Main Campus',
                'building' => 'ICT Center',
                'room' => '205',
                'description' => 'Information Technology department',
            ],
            [
                'name' => 'Library',
                'campus' => 'Main Campus',
                'building' => 'Library Complex',
                'room' => 'Main Desk',
                'description' => 'Main library entrance',
            ],
            [
                'name' => 'Security Office',
                'campus' => 'Main Campus',
                'building' => 'Security Post',
                'room' => 'Control Room',
                'description' => 'Main security control room',
            ],
            [
                'name' => 'Medical Center',
                'campus' => 'Medical Campus',
                'building' => 'Health Center',
                'room' => 'Reception',
                'description' => 'University medical center',
            ],
        ];

        foreach ($locations as $locationData) {
            Location::firstOrCreate(
                ['name' => $locationData['name']],
                $locationData
            );
        }

        // Create keys
        $keys = [
            ['code' => 'ADM001', 'label' => 'Main Office Key', 'location_id' => 1, 'key_type' => 'physical'],
            ['code' => 'ADM002', 'label' => 'Office Cabinet Key', 'location_id' => 1, 'key_type' => 'physical'],
            ['code' => 'IT001', 'label' => 'Server Room Key', 'location_id' => 2, 'key_type' => 'physical'],
            ['code' => 'IT002', 'label' => 'Network Cabinet Key', 'location_id' => 2, 'key_type' => 'physical'],
            ['code' => 'LIB001', 'label' => 'Library Main Door', 'location_id' => 3, 'key_type' => 'master'],
            ['code' => 'SEC001', 'label' => 'Security Office', 'location_id' => 4, 'key_type' => 'physical'],
            ['code' => 'MED001', 'label' => 'Medical Store', 'location_id' => 5, 'key_type' => 'physical'],
            ['code' => 'MED002', 'label' => 'Consultation Room', 'location_id' => 5, 'key_type' => 'physical'],
        ];

        foreach ($keys as $keyData) {
            $key = Key::firstOrCreate(
                ['code' => $keyData['code']],
                $keyData
            );

            // Generate QR tags for each key
            KeyTag::firstOrCreate(
                ['key_id' => $key->id],
                [
                    'uuid' => Str::uuid(),
                    'is_active' => true,
                    'printed_at' => now(),
                ]
            );
        }

        // Create HR staff
        $hrStaff = [
            ['staff_id' => 'STU001', 'name' => 'Dr. Kwame Mensah', 'phone' => '0234567001', 'dept' => 'Administration', 'email' => 'k.mensah@stu.edu.gh', 'status' => 'active'],
            ['staff_id' => 'STU002', 'name' => 'Prof. Ama Serwaa', 'phone' => '0234567002', 'dept' => 'Academic', 'email' => 'a.serwaa@stu.edu.gh', 'status' => 'active'],
            ['staff_id' => 'STU003', 'name' => 'Mr. Kofi Asare', 'phone' => '0234567003', 'dept' => 'IT', 'email' => 'k.asare@stu.edu.gh', 'status' => 'active'],
            ['staff_id' => 'STU004', 'name' => 'Ms. Efua Boateng', 'phone' => '0234567004', 'dept' => 'Library', 'email' => 'e.boateng@stu.edu.gh', 'status' => 'active'],
            ['staff_id' => 'STU005', 'name' => 'Dr. Yaw Osei', 'phone' => '0234567005', 'dept' => 'Medical', 'email' => 'y.osei@stu.edu.gh', 'status' => 'active'],
        ];

        foreach ($hrStaff as $staff) {
            HrStaff::firstOrCreate(
                ['staff_id' => $staff['staff_id']],
                $staff
            );
        }

        // Create some manual staff entries
        PermanentStaffManual::firstOrCreate(
            ['phone' => '0234567101'],
            [
                'name' => 'Mr. James Arthur',
                'staff_id' => 'STU-M001',
                'dept' => 'Maintenance',
                'added_by' => 1, // Admin user
            ]
        );

        // Create temporary staff
        TemporaryStaff::firstOrCreate(
            ['phone' => '0234567201'],
            [
                'name' => 'Visitor - John Smith',
                'id_number' => 'VIS001',
                'dept' => 'External',
            ]
        );

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Created: 5 locations, 8 keys, 5 HR staff, 1 manual staff, 1 temporary staff');
    }
}
