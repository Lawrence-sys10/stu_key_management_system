# Step 4: Generate Core Controllers (Part 1)
Write-Host "Creating STU Key Management Core Controllers..." -ForegroundColor Green

# 1. Create Controller base directory if not exists
$controllersDir = ".\app\Http\Controllers"
if (!(Test-Path $controllersDir)) {
    New-Item -ItemType Directory -Path $controllersDir -Force
}

# 2. Create Admin Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\KeyLog;
use App\Models\User;
use App\Models\Location;
use App\Models\HrStaff;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_keys' => Key::count(),
            'available_keys' => Key::available()->count(),
            'checked_out_keys' => Key::checkedOut()->count(),
            'total_staff' => HrStaff::active()->count(),
            'active_shifts' => \App\Models\SecurityShift::active()->count(),
            'pending_discrepancies' => KeyLog::withDiscrepancy()->unverified()->count(),
            'overdue_keys' => KeyLog::overdue()->count(),
        ];

        $recentActivity = KeyLog::with(['key.location', 'receiver'])
            ->latest()
            ->limit(10)
            ->get();

        $busiestLocations = Location::withCount(['keys as recent_checkouts' => function($query) {
            $query->join('key_logs', 'keys.id', '=', 'key_logs.key_id')
                  ->where('key_logs.action', 'checkout')
                  ->where('key_logs.created_at', '>=', now()->subDays(7));
        }])->orderBy('recent_checkouts', 'desc')
           ->limit(5)
           ->get();

        return view('admin.dashboard', compact('stats', 'recentActivity', 'busiestLocations'));
    }

    public function userManagement()
    {
        $users = User::with('roles')->latest()->get();
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:8|confirmed',
            'roles' => 'required|array',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => bcrypt($validated['password']),
            ]);

            $user->syncRoles($validated['roles']);
        });

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'password' => 'nullable|min:8|confirmed',
            'roles' => 'required|array',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ];

            if ($request->filled('password')) {
                $updateData['password'] = bcrypt($validated['password']);
            }

            $user->update($updateData);
            $user->syncRoles($validated['roles']);
        });

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    public function systemSettings()
    {
        $settings = Setting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        foreach ($request->except(['_token', '_method']) as $key => $value) {
            Setting::setValue($key, $value);
        }

        return redirect()->route('admin.settings')->with('success', 'Settings updated successfully.');
    }

    public function systemHealth()
    {
        $health = [
            'queue_workers' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count(),
            'disk_space' => disk_free_space(base_path()) / 1024 / 1024 / 1024, // GB
            'last_cron' => Setting::getValue('last_cron_run'),
            'pending_notifications' => \App\Models\Notification::pending()->count(),
        ];

        return view('admin.health', compact('health'));
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\AdminController.php -Encoding UTF8

# 3. Create Kiosk Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\KeyLog;
use App\Models\KeyTag;
use App\Models\HrStaff;
use App\Models\PermanentStaffManual;
use App\Models\TemporaryStaff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KioskController extends Controller
{
    public function index()
    {
        return view('kiosk.index');
    }

    public function scan()
    {
        return view('kiosk.scan');
    }

    public function processScan(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string',
        ]);

        $keyTag = KeyTag::with(['key.location'])->where('uuid', $request->uuid)->first();

        if (!$keyTag) {
            return response()->json(['error' => 'Key tag not found'], 404);
        }

        $key = $keyTag->key;

        return response()->json([
            'key' => $key,
            'key_tag' => $keyTag,
            'current_status' => $key->status,
            'current_holder' => $key->currentHolder,
        ]);
    }

    public function checkoutForm(Key $key)
    {
        if (!$key->isAvailable()) {
            return redirect()->route('kiosk.scan')->with('error', 'Key is not available for checkout.');
        }

        return view('kiosk.checkout', compact('key'));
    }

    public function processCheckout(Request $request, Key $key)
    {
        $validated = $request->validate([
            'holder_type' => 'required|in:hr,perm_manual,temp',
            'holder_id' => 'required_if:holder_type,hr,perm_manual,temp',
            'holder_name' => 'required|string|max:255',
            'holder_phone' => 'required|string|max:20',
            'expected_return_at' => 'nullable|date|after:now',
            'signature' => 'nullable|string', // base64 encoded signature
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $key) {
            // Handle file uploads
            $signaturePath = null;
            $photoPath = null;

            if (!empty($validated['signature'])) {
                $signaturePath = $this->storeSignature($validated['signature']);
            }

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('key-photos', 'public');
            }

            // Verify holder data
            $verified = $this->verifyHolderData($validated['holder_type'], $validated['holder_id'], $validated['holder_phone']);
            $discrepancy = !$verified;

            // Create checkout log
            $log = KeyLog::create([
                'key_id' => $key->id,
                'action' => 'checkout',
                'holder_type' => $validated['holder_type'],
                'holder_id' => $validated['holder_id'],
                'holder_name' => $validated['holder_name'],
                'holder_phone' => $validated['holder_phone'],
                'receiver_user_id' => auth()->id(),
                'receiver_name' => auth()->user()->name,
                'expected_return_at' => $validated['expected_return_at'],
                'signature_path' => $signaturePath,
                'photo_path' => $photoPath,
                'notes' => $validated['notes'],
                'verified' => $verified,
                'discrepancy' => $discrepancy,
            ]);

            // Update key status
            $key->update([
                'status' => 'checked_out',
                'last_log_id' => $log->id,
            ]);

            // Queue notifications
            if (Setting::getValue('notifications.checkout_enabled', false)) {
                \App\Jobs\SendCheckoutNotification::dispatch($log);
            }
        });

        return redirect()->route('kiosk.index')
            ->with('success', "Key {$key->label} checked out successfully.");
    }

    public function checkinForm(Key $key)
    {
        if (!$key->isCheckedOut()) {
            return redirect()->route('kiosk.scan')->with('error', 'Key is not currently checked out.');
        }

        $currentCheckout = $key->currentHolder;

        return view('kiosk.checkin', compact('key', 'currentCheckout'));
    }

    public function processCheckin(Request $request, Key $key)
    {
        $validated = $request->validate([
            'signature' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $key) {
            // Handle file uploads
            $signaturePath = null;
            $photoPath = null;

            if (!empty($validated['signature'])) {
                $signaturePath = $this->storeSignature($validated['signature']);
            }

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('key-photos', 'public');
            }

            // Create checkin log
            $log = $key->checkin(
                auth()->id(),
                $signaturePath,
                $photoPath,
                $validated['notes']
            );

            // Queue notifications
            if (Setting::getValue('notifications.return_enabled', false)) {
                \App\Jobs\SendReturnNotification::dispatch($log);
            }
        });

        return redirect()->route('kiosk.index')
            ->with('success', "Key {$key->label} checked in successfully.");
    }

    public function searchHolder(Request $request)
    {
        $search = $request->get('q');

        if (empty($search)) {
            return response()->json([]);
        }

        $results = [];

        // Search HR Staff
        $hrStaff = HrStaff::active()
            ->search($search)
            ->limit(10)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'phone' => $staff->phone,
                    'type' => 'hr',
                    'type_label' => 'HR Staff',
                    'dept' => $staff->dept,
                    'staff_id' => $staff->staff_id,
                ];
            });

        $results = $hrStaff->merge($results);

        // Search Permanent Manual Staff
        $permStaff = PermanentStaffManual::search($search)
            ->limit(10)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'phone' => $staff->phone,
                    'type' => 'perm_manual',
                    'type_label' => 'Permanent Staff (Manual)',
                    'dept' => $staff->dept,
                    'staff_id' => $staff->staff_id,
                ];
            });

        $results = $results->merge($permStaff);

        // Search Temporary Staff
        $tempStaff = TemporaryStaff::search($search)
            ->limit(10)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'phone' => $staff->phone,
                    'type' => 'temp',
                    'type_label' => 'Temporary Staff',
                    'id_number' => $staff->id_number,
                ];
            });

        $results = $results->merge($tempStaff);

        return response()->json($results->values());
    }

    public function createTemporaryStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'id_number' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('temp-staff-photos', 'public');
        }

        $staff = TemporaryStaff::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'id_number' => $validated['id_number'],
            'photo_path' => $photoPath,
        ]);

        return response()->json([
            'id' => $staff->id,
            'name' => $staff->name,
            'phone' => $staff->phone,
            'type' => 'temp',
            'type_label' => 'Temporary Staff',
        ]);
    }

    public function createPermanentManualStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'staff_id' => 'nullable|string|max:50',
            'dept' => 'nullable|string|max:100',
        ]);

        $staff = PermanentStaffManual::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'staff_id' => $validated['staff_id'],
            'dept' => $validated['dept'],
            'added_by' => auth()->id(),
        ]);

        return response()->json([
            'id' => $staff->id,
            'name' => $staff->name,
            'phone' => $staff->phone,
            'type' => 'perm_manual',
            'type_label' => 'Permanent Staff (Manual)',
            'staff_id' => $staff->staff_id,
        ]);
    }

    private function storeSignature($base64Signature)
    {
        $image = str_replace('data:image/png;base64,', '', $base64Signature);
        $image = str_replace(' ', '+', $image);
        $imageName = 'signatures/' . uniqid() . '.png';
        
        Storage::disk('public')->put($imageName, base64_decode($image));
        
        return $imageName;
    }

    private function verifyHolderData($holderType, $holderId, $holderPhone)
    {
        if ($holderType === 'hr') {
            $staff = HrStaff::where('id', $holderId)->first();
            return $staff && $staff->phone === $holderPhone;
        }

        if ($holderType === 'perm_manual') {
            $staff = PermanentStaffManual::where('id', $holderId)->first();
            return $staff && $staff->phone === $holderPhone;
        }

        if ($holderType === 'temp') {
            $staff = TemporaryStaff::where('id', $holderId)->first();
            return $staff && $staff->phone === $holderPhone;
        }

        return false;
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\KioskController.php -Encoding UTF8

# 4. Create Key Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\Location;
use App\Models\KeyTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KeyController extends Controller
{
    public function index()
    {
        $keys = Key::with(['location', 'keyTags', 'currentHolder'])
            ->latest()
            ->paginate(20);

        return view('keys.index', compact('keys'));
    }

    public function create()
    {
        $locations = Location::active()->get();
        return view('keys.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:keys',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'key_type' => 'required|in:physical,electronic,master,duplicate',
            'location_id' => 'required|exists:locations,id',
            'generate_qr' => 'boolean',
            'qr_count' => 'nullable|integer|min:1|max:5',
        ]);

        DB::transaction(function () use ($validated) {
            $key = Key::create([
                'code' => $validated['code'],
                'label' => $validated['label'],
                'description' => $validated['description'],
                'key_type' => $validated['key_type'],
                'location_id' => $validated['location_id'],
            ]);

            // Generate QR tags if requested
            if ($request->boolean('generate_qr')) {
                $count = $validated['qr_count'] ?? 1;
                $this->generateKeyTags($key, $count);
            }
        });

        return redirect()->route('keys.index')
            ->with('success', 'Key created successfully.');
    }

    public function show(Key $key)
    {
        $key->load(['location', 'keyTags', 'keyLogs.receiver', 'keyLogs.holder']);
        
        $currentLog = $key->currentHolder;
        $history = $key->keyLogs()
            ->with(['receiver', 'holder'])
            ->latest()
            ->paginate(10);

        return view('keys.show', compact('key', 'currentLog', 'history'));
    }

    public function edit(Key $key)
    {
        $locations = Location::active()->get();
        return view('keys.edit', compact('key', 'locations'));
    }

    public function update(Request $request, Key $key)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:keys,code,' . $key->id,
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'key_type' => 'required|in:physical,electronic,master,duplicate',
            'location_id' => 'required|exists:locations,id',
            'status' => 'required|in:available,checked_out,lost,maintenance',
        ]);

        $key->update($validated);

        return redirect()->route('keys.index')
            ->with('success', 'Key updated successfully.');
    }

    public function destroy(Key $key)
    {
        if ($key->isCheckedOut()) {
            return redirect()->back()->with('error', 'Cannot delete a key that is currently checked out.');
        }

        $key->delete();

        return redirect()->route('keys.index')
            ->with('success', 'Key deleted successfully.');
    }

    public function generateTags(Key $key, Request $request)
    {
        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:10',
        ]);

        $this->generateKeyTags($key, $validated['count']);

        return redirect()->route('keys.show', $key)
            ->with('success', "{$validated['count']} QR tags generated successfully.");
    }

    public function printTags(Key $key)
    {
        $tags = $key->keyTags()->active()->get();
        
        if ($tags->isEmpty()) {
            return redirect()->back()->with('error', 'No active QR tags found for this key.');
        }

        return view('keys.print-tags', compact('key', 'tags'));
    }

    private function generateKeyTags(Key $key, $count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            KeyTag::create([
                'key_id' => $key->id,
                'uuid' => Str::uuid(),
                'is_active' => true,
            ]);
        }
    }

    public function markAsLost(Key $key)
    {
        if (!$key->isCheckedOut()) {
            return redirect()->back()->with('error', 'Only checked out keys can be marked as lost.');
        }

        $key->update(['status' => 'lost']);

        // Log the loss
        KeyLog::create([
            'key_id' => $key->id,
            'action' => 'checkin', // Special case for lost keys
            'holder_type' => $key->currentHolder->holder_type,
            'holder_id' => $key->currentHolder->holder_id,
            'holder_name' => $key->currentHolder->holder_name,
            'holder_phone' => $key->currentHolder->holder_phone,
            'receiver_user_id' => auth()->id(),
            'receiver_name' => auth()->user()->name,
            'returned_from_log_id' => $key->currentHolder->id,
            'notes' => 'Key reported as lost',
            'verified' => false,
            'discrepancy' => true,
        ]);

        return redirect()->route('keys.show', $key)
            ->with('warning', 'Key marked as lost. Security team has been notified.');
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\KeyController.php -Encoding UTF8

# 5. Create Location Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::withCount(['keys as total_keys', 'keys as available_keys' => function($query) {
            $query->where('status', 'available');
        }])->latest()->paginate(20);

        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        $campuses = $this->getAvailableCampuses();
        return view('locations.create', compact('campuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'campus' => 'required|string|max:100',
            'building' => 'required|string|max:100',
            'room' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        Location::create($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Location created successfully.');
    }

    public function show(Location $location)
    {
        $location->load(['keys.keyTags', 'keys.currentHolder']);
        
        $keys = $location->keys()
            ->with(['keyTags', 'currentHolder'])
            ->latest()
            ->paginate(20);

        return view('locations.show', compact('location', 'keys'));
    }

    public function edit(Location $location)
    {
        $campuses = $this->getAvailableCampuses();
        return view('locations.edit', compact('location', 'campuses'));
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'campus' => 'required|string|max:100',
            'building' => 'required|string|max:100',
            'room' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        if ($location->keys()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete location that has keys assigned.');
        }

        $location->delete();

        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    public function getBuildings(Request $request)
    {
        $campus = $request->get('campus');
        
        if (!$campus) {
            return response()->json([]);
        }

        $buildings = Location::where('campus', $campus)
            ->distinct()
            ->pluck('building')
            ->map(function ($building) {
                return ['id' => $building, 'text' => $building];
            });

        return response()->json($buildings);
    }

    public function getRooms(Request $request)
    {
        $campus = $request->get('campus');
        $building = $request->get('building');
        
        if (!$campus || !$building) {
            return response()->json([]);
        }

        $rooms = Location::where('campus', $campus)
            ->where('building', $building)
            ->whereNotNull('room')
            ->distinct()
            ->pluck('room')
            ->map(function ($room) {
                return ['id' => $room, 'text' => $room];
            });

        return response()->json($rooms);
    }

    private function getAvailableCampuses()
    {
        return [
            'Main Campus' => 'Main Campus',
            'Medical Campus' => 'Medical Campus',
            'Engineering Campus' => 'Engineering Campus',
            'City Campus' => 'City Campus',
        ];
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\LocationController.php -Encoding UTF8

Write-Host "✅ First 5 core controllers created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in app/Http/Controllers/" -ForegroundColor Cyan
Write-Host "➡️ Controllers: Admin, Kiosk, Key, Location" -ForegroundColor Yellow