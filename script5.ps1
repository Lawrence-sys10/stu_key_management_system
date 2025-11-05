# Step 5: Generate Remaining Controllers (Part 2)
Write-Host "Creating Remaining STU Key Management Controllers..." -ForegroundColor Green

# 6. Create HR Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\HrStaff;
use App\Models\PermanentStaffManual;
use App\Models\KeyLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\HrStaffImport;
use App\Imports\TemporaryStaffImport;
use Maatwebsite\Excel\Facades\Excel;

class HrController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_hr_staff' => HrStaff::count(),
            'active_hr_staff' => HrStaff::active()->count(),
            'total_manual_staff' => PermanentStaffManual::count(),
            'pending_discrepancies' => KeyLog::withDiscrepancy()->unverified()->count(),
        ];

        $recentDiscrepancies = KeyLog::withDiscrepancy()
            ->unverified()
            ->with(['key.location', 'receiver'])
            ->latest()
            ->limit(10)
            ->get();

        $recentManualAdditions = PermanentStaffManual::with('addedBy')
            ->latest()
            ->limit(10)
            ->get();

        return view('hr.dashboard', compact('stats', 'recentDiscrepancies', 'recentManualAdditions'));
    }

    public function hrStaffIndex(Request $request)
    {
        $query = HrStaff::query();

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('dept') && $request->dept) {
            $query->where('dept', $request->dept);
        }

        $staff = $query->latest()->paginate(20);

        $departments = HrStaff::distinct()->pluck('dept')->filter();
        $statuses = ['active', 'inactive'];

        return view('hr.staff.index', compact('staff', 'departments', 'statuses'));
    }

    public function hrStaffShow(HrStaff $hrStaff)
    {
        $hrStaff->load('keyLogs.key.location', 'keyLogs.receiver');
        
        $currentKeys = $hrStaff->getCurrentHeldKeys();
        $keyHistory = $hrStaff->keyLogs()
            ->with(['key.location', 'receiver'])
            ->latest()
            ->paginate(10);

        return view('hr.staff.show', compact('hrStaff', 'currentKeys', 'keyHistory'));
    }

    public function importHrStaffForm()
    {
        return view('hr.import.hr-staff');
    }

    public function importHrStaff(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'update_existing' => 'boolean',
        ]);

        try {
            $import = new HrStaffImport($request->boolean('update_existing', true));
            Excel::import($import, $request->file('csv_file'));

            $imported = $import->getImportedCount();
            $updated = $import->getUpdatedCount();
            $errors = $import->getErrors();

            $message = "Import completed: {$imported} new records, {$updated} updated records.";

            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' errors occurred.';
                return redirect()->route('hr.import.form')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('hr.staff.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('hr.import.form')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function manualStaffIndex(Request $request)
    {
        $query = PermanentStaffManual::with('addedBy');

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $staff = $query->latest()->paginate(20);

        return view('hr.manual-staff.index', compact('staff'));
    }

    public function createManualStaff()
    {
        return view('hr.manual-staff.create');
    }

    public function storeManualStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:permanent_staff_manual,phone',
            'staff_id' => 'nullable|string|max:50|unique:permanent_staff_manual,staff_id',
            'dept' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        PermanentStaffManual::create([
            ...$validated,
            'added_by' => auth()->id(),
        ]);

        return redirect()->route('hr.manual-staff.index')
            ->with('success', 'Manual staff record created successfully.');
    }

    public function discrepanciesIndex()
    {
        $discrepancies = KeyLog::withDiscrepancy()
            ->unverified()
            ->with(['key.location', 'receiver', 'holder'])
            ->latest()
            ->paginate(20);

        return view('hr.discrepancies.index', compact('discrepancies'));
    }

    public function resolveDiscrepancy(KeyLog $keyLog, Request $request)
    {
        $request->validate([
            'action' => 'required|in:verify,reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($keyLog, $request) {
            if ($request->action === 'verify') {
                $keyLog->markAsVerified();
                $message = 'Discrepancy resolved and verified.';
            } else {
                $keyLog->update([
                    'notes' => $request->notes . ' [Rejected by HR]',
                    'discrepancy_reason' => $request->notes,
                ]);
                $message = 'Discrepancy marked as rejected.';
            }
        });

        return redirect()->route('hr.discrepancies.index')
            ->with('success', $message);
    }

    public function bulkResolveDiscrepancies(Request $request)
    {
        $request->validate([
            'log_ids' => 'required|array',
            'log_ids.*' => 'exists:key_logs,id',
            'action' => 'required|in:verify,reject',
        ]);

        $resolved = 0;
        
        foreach ($request->log_ids as $logId) {
            $log = KeyLog::find($logId);
            if ($log && $log->discrepancy && !$log->verified) {
                if ($request->action === 'verify') {
                    $log->markAsVerified();
                } else {
                    $log->update([
                        'discrepancy_reason' => 'Bulk rejected by HR',
                    ]);
                }
                $resolved++;
            }
        }

        return redirect()->route('hr.discrepancies.index')
            ->with('success', "{$resolved} discrepancies resolved successfully.");
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\HrController.php -Encoding UTF8

# 7. Create Report Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\KeyLog;
use App\Models\Key;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function keyActivity(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'key_id' => 'nullable|exists:keys,id',
            'location_id' => 'nullable|exists:locations,id',
            'receiver_id' => 'nullable|exists:users,id',
            'action' => 'nullable|in:checkout,checkin',
        ]);

        $query = KeyLog::with(['key.location', 'receiver', 'holder'])
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);

        if (!empty($filters['key_id'])) {
            $query->where('key_id', $filters['key_id']);
        }

        if (!empty($filters['location_id'])) {
            $query->whereHas('key', function ($q) use ($filters) {
                $q->where('location_id', $filters['location_id']);
            });
        }

        if (!empty($filters['receiver_id'])) {
            $query->where('receiver_user_id', $filters['receiver_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $logs = $query->latest()->paginate(50);

        $keys = Key::all();
        $locations = Location::all();
        $receivers = User::role('security')->get();

        return view('reports.key-activity', compact('logs', 'filters', 'keys', 'locations', 'receivers'));
    }

    public function currentHolders()
    {
        $currentHolders = KeyLog::openCheckouts()
            ->with(['key.location', 'holder', 'receiver'])
            ->latest()
            ->paginate(50);

        return view('reports.current-holders', compact('currentHolders'));
    }

    public function overdueKeys()
    {
        $overdueKeys = KeyLog::overdue()
            ->with(['key.location', 'holder', 'receiver'])
            ->latest()
            ->paginate(50);

        return view('reports.overdue-keys', compact('overdueKeys'));
    }

    public function staffActivity(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'staff_type' => 'nullable|in:hr,perm_manual,temp',
        ]);

        $query = KeyLog::with(['key.location', 'receiver'])
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->where('action', 'checkout');

        if (!empty($filters['staff_type'])) {
            $query->where('holder_type', $filters['staff_type']);
        }

        $staffActivity = $query->select(
                'holder_type',
                'holder_id',
                'holder_name',
                'holder_phone',
                DB::raw('COUNT(*) as total_checkouts'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, 
                    (SELECT created_at FROM key_logs AS k2 
                     WHERE k2.returned_from_log_id = key_logs.id)
                )) as avg_duration_minutes')
            )
            ->groupBy('holder_type', 'holder_id', 'holder_name', 'holder_phone')
            ->orderBy('total_checkouts', 'desc')
            ->paginate(50);

        return view('reports.staff-activity', compact('staffActivity', 'filters'));
    }

    public function securityPerformance(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $performance = User::role('security')
            ->withCount(['keyLogsAsReceiver as total_transactions' => function($query) use ($filters) {
                $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            }])
            ->withCount(['keyLogsAsReceiver as checkout_count' => function($query) use ($filters) {
                $query->where('action', 'checkout')
                      ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            }])
            ->withCount(['keyLogsAsReceiver as checkin_count' => function($query) use ($filters) {
                $query->where('action', 'checkin')
                      ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            }])
            ->having('total_transactions', '>', 0)
            ->orderBy('total_transactions', 'desc')
            ->paginate(20);

        return view('reports.security-performance', compact('performance', 'filters'));
    }

    public function analyticsDashboard()
    {
        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');

        // Basic stats
        $stats = [
            'today_checkouts' => KeyLog::whereDate('created_at', $today)
                ->where('action', 'checkout')
                ->count(),
            'week_checkouts' => KeyLog::whereDate('created_at', '>=', $weekAgo)
                ->where('action', 'checkout')
                ->count(),
            'avg_checkout_duration' => KeyLog::checkin()
                ->whereDate('created_at', '>=', $weekAgo)
                ->average(DB::raw('TIMESTAMPDIFF(MINUTE, 
                    (SELECT created_at FROM key_logs AS k2 WHERE k2.id = key_logs.returned_from_log_id),
                    key_logs.created_at)')),
            'busiest_location' => Location::withCount(['keyLogs as recent_checkouts' => function($query) use ($weekAgo) {
                $query->where('action', 'checkout')
                      ->whereDate('created_at', '>=', $weekAgo);
            }])->orderBy('recent_checkouts', 'desc')
               ->first(),
        ];

        // Hourly activity for today
        $hourlyActivity = KeyLog::whereDate('created_at', $today)
            ->where('action', 'checkout')
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        // Top keys this week
        $topKeys = Key::withCount(['keyLogs as recent_checkouts' => function($query) use ($weekAgo) {
                $query->where('action', 'checkout')
                      ->whereDate('created_at', '>=', $weekAgo);
            }])
            ->orderBy('recent_checkouts', 'desc')
            ->limit(10)
            ->get();

        return view('reports.analytics', compact('stats', 'hourlyActivity', 'topKeys'));
    }

    public function exportKeyActivity(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $logs = KeyLog::with(['key.location', 'receiver', 'holder'])
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->latest()
            ->get();

        if ($filters['format'] === 'csv') {
            return $this->exportToCsv($logs);
        } elseif ($filters['format'] === 'excel') {
            return $this->exportToExcel($logs);
        } else {
            return $this->exportToPdf($logs, $filters);
        }
    }

    private function exportToCsv($logs)
    {
        $fileName = 'key-activity-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Date', 'Time', 'Action', 'Key Code', 'Key Label', 'Location',
                'Holder Name', 'Holder Phone', 'Holder Type', 'Security Officer',
                'Expected Return', 'Verified', 'Discrepancy'
            ]);

            // Data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d'),
                    $log->created_at->format('H:i:s'),
                    $log->action,
                    $log->key->code,
                    $log->key->label,
                    $log->key->location->full_address,
                    $log->holder_name,
                    $log->holder_phone,
                    $log->holder_type_label,
                    $log->receiver_name,
                    $log->expected_return_at ? $log->expected_return_at->format('Y-m-d H:i') : 'N/A',
                    $log->verified ? 'Yes' : 'No',
                    $log->discrepancy ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($logs)
    {
        // Implementation for Excel export
        // This would use Maatwebsite/Excel package
        return response()->json(['message' => 'Excel export to be implemented']);
    }

    private function exportToPdf($logs, $filters)
    {
        // Implementation for PDF export
        // This would use DomPDF or similar
        return response()->json(['message' => 'PDF export to be implemented']);
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\ReportController.php -Encoding UTF8

# 8. Create API Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\KeyTag;
use App\Models\HrStaff;
use App\Models\KeyLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['scanKey', 'getKeyDetails']);
    }

    public function scanKey(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string',
        ]);

        $keyTag = KeyTag::with(['key.location'])->where('uuid', $request->uuid)->first();

        if (!$keyTag) {
            return response()->json([
                'success' => false,
                'message' => 'Key tag not found'
            ], 404);
        }

        $key = $keyTag->key;
        $currentHolder = $key->currentHolder;

        return response()->json([
            'success' => true,
            'data' => [
                'key' => [
                    'id' => $key->id,
                    'code' => $key->code,
                    'label' => $key->label,
                    'key_type' => $key->key_type,
                    'status' => $key->status,
                    'location' => $key->location->only(['name', 'campus', 'building', 'room']),
                ],
                'current_holder' => $currentHolder ? [
                    'name' => $currentHolder->holder_name,
                    'phone' => $currentHolder->holder_phone,
                    'type' => $currentHolder->holder_type_label,
                    'checked_out_at' => $currentHolder->created_at->toISOString(),
                    'expected_return' => $currentHolder->expected_return_at?->toISOString(),
                ] : null,
            ]
        ]);
    }

    public function getKeyDetails(Key $key)
    {
        $key->load(['location', 'keyTags', 'currentHolder']);

        return response()->json([
            'success' => true,
            'data' => $key
        ]);
    }

    public function searchStaff(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $search = $request->q;

        $cacheKey = 'staff_search_' . md5($search);
        $results = Cache::remember($cacheKey, 300, function () use ($search) {
            $results = [];

            // Search HR Staff
            $hrStaff = HrStaff::active()
                ->search($search)
                ->limit(5)
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
                        'verified' => true,
                    ];
                });

            $results = $hrStaff->merge($results);

            return $results->values();
        });

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    public function checkoutKey(Request $request)
    {
        $request->validate([
            'key_id' => 'required|exists:keys,id',
            'holder_type' => 'required|in:hr,perm_manual,temp',
            'holder_id' => 'required',
            'holder_name' => 'required|string',
            'holder_phone' => 'required|string',
            'expected_return_at' => 'nullable|date|after:now',
            'signature' => 'nullable|string',
        ]);

        $key = Key::findOrFail($request->key_id);

        if (!$key->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Key is not available for checkout'
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $key) {
                $signaturePath = null;
                if (!empty($request->signature)) {
                    $signaturePath = $this->storeSignature($request->signature);
                }

                $verified = $this->verifyHolderData($request->holder_type, $request->holder_id, $request->holder_phone);

                $log = KeyLog::create([
                    'key_id' => $key->id,
                    'action' => 'checkout',
                    'holder_type' => $request->holder_type,
                    'holder_id' => $request->holder_id,
                    'holder_name' => $request->holder_name,
                    'holder_phone' => $request->holder_phone,
                    'receiver_user_id' => auth()->id(),
                    'receiver_name' => auth()->user()->name,
                    'expected_return_at' => $request->expected_return_at,
                    'signature_path' => $signaturePath,
                    'verified' => $verified,
                    'discrepancy' => !$verified,
                ]);

                $key->update([
                    'status' => 'checked_out',
                    'last_log_id' => $log->id,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Key checked out successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkinKey(Request $request)
    {
        $request->validate([
            'key_id' => 'required|exists:keys,id',
            'signature' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $key = Key::findOrFail($request->key_id);

        if (!$key->isCheckedOut()) {
            return response()->json([
                'success' => false,
                'message' => 'Key is not currently checked out'
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $key) {
                $signaturePath = null;
                if (!empty($request->signature)) {
                    $signaturePath = $this->storeSignature($request->signature);
                }

                $key->checkin(
                    auth()->id(),
                    $signaturePath,
                    null,
                    $request->notes
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Key checked in successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkin failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDashboardStats()
    {
        $stats = Cache::remember('dashboard_stats', 300, function () {
            return [
                'total_keys' => Key::count(),
                'available_keys' => Key::available()->count(),
                'checked_out_keys' => Key::checkedOut()->count(),
                'overdue_keys' => KeyLog::overdue()->count(),
                'today_checkouts' => KeyLog::whereDate('created_at', today())
                    ->where('action', 'checkout')
                    ->count(),
                'pending_discrepancies' => KeyLog::withDiscrepancy()->unverified()->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getRecentActivity()
    {
        $activity = KeyLog::with(['key.location', 'receiver'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'key_code' => $log->key->code,
                    'key_label' => $log->key->label,
                    'location' => $log->key->location->full_address,
                    'action' => $log->action,
                    'holder_name' => $log->holder_name,
                    'receiver_name' => $log->receiver_name,
                    'created_at' => $log->created_at->toISOString(),
                    'is_discrepancy' => $log->discrepancy,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $activity
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

        // For manual and temp staff, we trust the kiosk input
        return true;
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\ApiController.php -Encoding UTF8

# 9. Create Profile Controller
@'
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $recentActivity = $user->keyLogsAsReceiver()
            ->with(['key.location'])
            ->latest()
            ->limit(10)
            ->get();

        $currentShift = $user->current_shift;

        return view('profile.show', compact('user', 'recentActivity', 'currentShift'));
    }

    public function edit()
    {
        return view('profile.edit');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'required|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return redirect()->route('profile.show')
            ->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Password updated successfully.');
    }

    public function activityLog()
    {
        $activity = auth()->user()->keyLogsAsReceiver()
            ->with(['key.location', 'holder'])
            ->latest()
            ->paginate(20);

        return view('profile.activity', compact('activity'));
    }

    public function shiftHistory()
    {
        $shifts = auth()->user()->securityShifts()
            ->latest()
            ->paginate(20);

        return view('profile.shift-history', compact('shifts'));
    }

    public function startShift(Request $request)
    {
        $user = auth()->user();

        if ($user->isOnShift()) {
            return redirect()->back()->with('error', 'You are already on an active shift.');
        }

        $user->securityShifts()->create([
            'start_at' => now(),
            'notes' => $request->notes,
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Shift started successfully.');
    }

    public function endShift(Request $request)
    {
        $user = auth()->user();
        $currentShift = $user->current_shift;

        if (!$currentShift) {
            return redirect()->back()->with('error', 'No active shift found.');
        }

        $currentShift->update([
            'end_at' => now(),
            'notes' => $currentShift->notes . "\n" . $request->notes,
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Shift ended successfully.');
    }
}
'@ | Out-File -FilePath .\app\Http\Controllers\ProfileController.php -Encoding UTF8

Write-Host "✅ All 9 controllers created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in app/Http/Controllers/" -ForegroundColor Cyan
Write-Host "➡️ Controllers: HR, Report, API, Profile" -ForegroundColor Yellow