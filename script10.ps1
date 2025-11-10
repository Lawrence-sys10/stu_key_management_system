# Step 10: Generate Seeders, Config Files & Essential Components
Write-Host "Creating Final Components - Seeders, Configs & More..." -ForegroundColor Green

# Create additional directories
$seedersDir = ".\database\seeders"
$configDir = ".\config"
$publicDir = ".\public"
if (!(Test-Path $seedersDir)) { New-Item -ItemType Directory -Path $seedersDir -Force }
if (!(Test-Path $configDir)) { New-Item -ItemType Directory -Path $configDir -Force }
if (!(Test-Path $publicDir)) { New-Item -ItemType Directory -Path $publicDir -Force }

# 1. Create RolePermissionSeeder
@'
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
'@ | Out-File -FilePath .\database\seeders\RolePermissionSeeder.php -Encoding UTF8

# 2. Create AdminUserSeeder
@'
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
'@ | Out-File -FilePath .\database\seeders\AdminUserSeeder.php -Encoding UTF8

# 3. Create DemoDataSeeder
@'
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
'@ | Out-File -FilePath .\database\seeders\DemoDataSeeder.php -Encoding UTF8

# 4. Create Settings Config File
@'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | STU Key Management Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the STU Key Management System
    |
    */

    'hr_sync' => [
        'enabled' => env('FEATURE_HR_SYNC', false),
        'base_url' => env('HR_API_BASE'),
        'api_key' => env('HR_API_KEY'),
        'sync_cron' => env('HR_API_SYNC_CRON', '*/30 * * * *'),
    ],

    'notifications' => [
        'sms_provider' => env('SMS_PROVIDER', 'hubtel'),
        'whatsapp_provider' => env('WHATSAPP_PROVIDER', 'none'),
        
        'hubtel' => [
            'api_key' => env('HUBTEL_API_KEY'),
            'client_id' => env('HUBTEL_CLIENT_ID'),
            'client_secret' => env('HUBTEL_CLIENT_SECRET'),
            'sender_id' => env('HUBTEL_SENDER_ID', 'STU-Keys'),
        ],
    ],

    'pwa' => [
        'offline_tolerance_hours' => env('PWA_OFFLINE_TOLERANCE_HOURS', 4),
        'background_sync_interval' => env('PWA_BACKGROUND_SYNC_INTERVAL', 5),
    ],

    'features' => [
        'otp_verification' => env('FEATURE_OTP_VERIFICATION', false),
        'shift_rosters' => env('FEATURE_SHIFT_ROSTERS', false),
        'api_integration' => env('FEATURE_API_INTEGRATION', true),
    ],

    'security' => [
        'require_signature_checkout' => true,
        'require_signature_checkin' => false,
        'photo_optional' => true,
        'max_photo_size' => 2048, // KB
        'auto_logout_minutes' => 120,
    ],

    'reports' => [
        'retention_days' => 365,
        'export_limit' => 10000,
    ],
];
'@ | Out-File -FilePath .\config\stu_keys.php -Encoding UTF8

# 5. Create Service Worker for PWA
@'
// STU Key Management Service Worker
const CACHE_NAME = 'stu-keys-v1.0.0';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
    '/offline',
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    if (event.request.method !== 'GET') return;
    
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }

                return fetch(event.request)
                    .then(function(response) {
                        // Check if we received a valid response
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response
                        var responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(function() {
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline');
                        }
                    });
            }
        )
    );
});

self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(
            syncPendingActions()
        );
    }
});

async function syncPendingActions() {
    // Get pending actions from IndexedDB
    const pendingActions = await getPendingActions();
    
    for (const action of pendingActions) {
        try {
            const response = await fetch(action.url, {
                method: action.method,
                headers: action.headers,
                body: action.body
            });
            
            if (response.ok) {
                await removePendingAction(action.id);
            }
        } catch (error) {
            console.log('Sync failed for action:', action.id, error);
        }
    }
}

// Background sync for periodic updates
self.addEventListener('periodicsync', function(event) {
    if (event.tag === 'content-update') {
        event.waitUntil(updateCachedContent());
    }
});

async function updateCachedContent() {
    const cache = await caches.open(CACHE_NAME);
    const requests = await cache.keys();
    
    for (const request of requests) {
        try {
            const networkResponse = await fetch(request);
            if (networkResponse.ok) {
                await cache.put(request, networkResponse);
            }
        } catch (error) {
            console.log('Failed to update:', request.url);
        }
    }
}

// IndexedDB for offline actions
function getPendingActions() {
    return new Promise((resolve) => {
        const request = indexedDB.open('StuKeysOffline', 1);
        
        request.onupgradeneeded = function(event) {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pendingActions')) {
                db.createObjectStore('pendingActions', { keyPath: 'id' });
            }
        };
        
        request.onsuccess = function(event) {
            const db = event.target.result;
            const transaction = db.transaction(['pendingActions'], 'readonly');
            const store = transaction.objectStore('pendingActions');
            const getAll = store.getAll();
            
            getAll.onsuccess = function() {
                resolve(getAll.result);
            };
        };
        
        request.onerror = function() {
            resolve([]);
        };
    });
}

function removePendingAction(id) {
    return new Promise((resolve) => {
        const request = indexedDB.open('StuKeysOffline', 1);
        
        request.onsuccess = function(event) {
            const db = event.target.result;
            const transaction = db.transaction(['pendingActions'], 'readwrite');
            const store = transaction.objectStore('pendingActions');
            const deleteReq = store.delete(id);
            
            deleteReq.onsuccess = function() {
                resolve(true);
            };
            
            deleteReq.onerror = function() {
                resolve(false);
            };
        };
    });
}
'@ | Out-File -FilePath .\public\sw.js -Encoding UTF8

# 6. Create PWA Manifest
@'
{
    "name": "STU Key Management System",
    "short_name": "STU Keys",
    "description": "Secure key handover and management system for STU University",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#3b82f6",
    "theme_color": "#3b82f6",
    "orientation": "portrait-primary",
    "scope": "/",
    "lang": "en",
    "categories": ["business", "productivity", "utilities"],
    "icons": [
        {
            "src": "/images/icons/icon-72x72.png",
            "sizes": "72x72",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/images/icons/icon-96x96.png",
            "sizes": "96x96",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/images/icons/icon-128x128.png",
            "sizes": "128x128",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/images/icons/icon-144x144.png",
            "sizes": "144x144",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/images/icons/icon-152x152.png",
            "sizes": "152x152",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/images/icons/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/images/icons/icon-384x384.png",
            "sizes": "384x384",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/images/icons/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any maskable"
        }
    ],
    "screenshots": [
        {
            "src": "/images/screenshots/dashboard.png",
            "sizes": "1280x720",
            "type": "image/png",
            "form_factor": "wide",
            "label": "Dashboard overview"
        },
        {
            "src": "/images/screenshots/kiosk.png",
            "sizes": "720x1280",
            "type": "image/png",
            "form_factor": "narrow",
            "label": "Kiosk interface"
        }
    ]
}
'@ | Out-File -FilePath .\public\manifest.json -Encoding UTF8

# 7. Create Import Classes
@'
<?php

namespace App\Imports;

use App\Models\HrStaff;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class HrStaffImport implements ToCollection, WithHeadingRow
{
    private $updateExisting;
    private $importedCount = 0;
    private $updatedCount = 0;
    private $errors = [];

    public function __construct($updateExisting = true)
    {
        $this->updateExisting = $updateExisting;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Normalize column names
                $normalizedRow = $this->normalizeRow($row);
                
                // Validate row data
                $validator = Validator::make($normalizedRow, [
                    'staff_id' => 'required|string|max:50',
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20',
                    'status' => 'required|in:active,inactive',
                    'dept' => 'nullable|string|max:100',
                    'email' => 'nullable|email|max:255',
                ]);

                if ($validator->fails()) {
                    $this->errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Process the row
                $this->processRow($normalizedRow);

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    private function normalizeRow($row)
    {
        $normalized = [];
        
        // Handle different column name variations
        $mappings = [
            'staff_id' => ['staff_id', 'staffid', 'employee_id', 'empid'],
            'name' => ['name', 'full_name', 'employee_name'],
            'phone' => ['phone', 'phone_number', 'mobile', 'contact'],
            'dept' => ['dept', 'department', 'unit'],
            'email' => ['email', 'email_address'],
            'status' => ['status', 'active_status'],
        ];

        foreach ($mappings as $field => $possibleNames) {
            foreach ($possibleNames as $possibleName) {
                if (isset($row[$possibleName]) && !empty($row[$possibleName])) {
                    $normalized[$field] = $row[$possibleName];
                    break;
                }
            }
            
            if (!isset($normalized[$field])) {
                $normalized[$field] = null;
            }
        }

        return $normalized;
    }

    private function processRow($data)
    {
        $existingStaff = HrStaff::where('staff_id', $data['staff_id'])->first();

        if ($existingStaff) {
            if ($this->updateExisting) {
                $existingStaff->update([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'dept' => $data['dept'],
                    'email' => $data['email'],
                    'status' => $data['status'],
                    'synced_at' => now(),
                ]);
                $this->updatedCount++;
            }
        } else {
            HrStaff::create([
                'staff_id' => $data['staff_id'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'dept' => $data['dept'],
                'email' => $data['email'],
                'status' => $data['status'],
                'source' => 'csv',
                'synced_at' => now(),
            ]);
            $this->importedCount++;
        }
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
'@ | Out-File -FilePath .\app\Imports\HrStaffImport.php -Encoding UTF8

# 8. Create TemporaryStaffImport
@'
<?php

namespace App\Imports;

use App\Models\TemporaryStaff;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class TemporaryStaffImport implements ToCollection, WithHeadingRow
{
    private $importedCount = 0;
    private $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Normalize column names
                $normalizedRow = $this->normalizeRow($row);
                
                // Validate row data
                $validator = Validator::make($normalizedRow, [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20|unique:temporary_staff,phone',
                    'id_number' => 'nullable|string|max:50',
                    'dept' => 'nullable|string|max:100',
                ]);

                if ($validator->fails()) {
                    $this->errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Create temporary staff
                TemporaryStaff::create($normalizedRow);
                $this->importedCount++;

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    private function normalizeRow($row)
    {
        $normalized = [];
        
        $mappings = [
            'name' => ['name', 'full_name', 'visitor_name'],
            'phone' => ['phone', 'phone_number', 'mobile', 'contact'],
            'id_number' => ['id_number', 'id', 'visitor_id', 'identification'],
            'dept' => ['dept', 'department', 'company', 'organization'],
        ];

        foreach ($mappings as $field => $possibleNames) {
            foreach ($possibleNames as $possibleName) {
                if (isset($row[$possibleName]) && !empty($row[$possibleName])) {
                    $normalized[$field] = $row[$possibleName];
                    break;
                }
            }
            
            if (!isset($normalized[$field])) {
                $normalized[$field] = null;
            }
        }

        return $normalized;
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
'@ | Out-File -FilePath .\app\Imports\TemporaryStaffImport.php -Encoding UTF8

# 9. Create Kernel Updates with Middleware
@'
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Laravel\Jetstream\Http\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        
        // Custom middleware
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'kiosk' => \App\Http\Middleware\KioskMiddleware::class,
        'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
    ];
}
'@ | Out-File -FilePath .\app\Http\Kernel.php -Encoding UTF8

# 10. Create Offline View
@'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - STU Key Management</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .offline-container {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
        }
        .icon {
            font-size: 4rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="icon">📶</div>
        <h1>You're Offline</h1>
        <p>The STU Key Management system requires an internet connection. Please check your connection and try again.</p>
        <button class="btn" onclick="window.location.reload()">Try Again</button>
    </div>
</body>
</html>
'@ | Out-File -FilePath .\resources\views\offline.blade.php -Encoding UTF8

Write-Host "✅ Step 10 components created successfully!" -ForegroundColor Green
Write-Host "📁 Files created:" -ForegroundColor Cyan
Write-Host "   - database/seeders/ (3 seeders)" -ForegroundColor Cyan
Write-Host "   - config/stu_keys.php" -ForegroundColor Cyan
Write-Host "   - public/sw.js & manifest.json" -ForegroundColor Cyan
Write-Host "   - app/Imports/ (2 import classes)" -ForegroundColor Cyan
Write-Host "   - app/Http/Kernel.php" -ForegroundColor Cyan
Write-Host "   - resources/views/offline.blade.php" -ForegroundColor Cyan