<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KeyController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Auth::routes();

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        
        // Password routes
        Route::get('/password', [ProfileController::class, 'editPassword'])->name('password.edit');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        
        Route::get('/activity', [ProfileController::class, 'activityLog'])->name('activity');
        Route::get('/shifts', [ProfileController::class, 'shiftHistory'])->name('shift-history');
        Route::post('/shift/start', [ProfileController::class, 'startShift'])->name('start-shift');
        Route::post('/shift/end', [ProfileController::class, 'endShift'])->name('end-shift');
        
        // Add profile deletion route
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Kiosk Routes
    Route::prefix('kiosk')->name('kiosk.')->middleware(['role:admin|security', 'kiosk'])->group(function () {
        Route::get('/', [KioskController::class, 'index'])->name('index');
        Route::get('/scan', [KioskController::class, 'scan'])->name('scan');
        Route::post('/scan/process', [KioskController::class, 'processScan'])->name('process-scan');
        Route::get('/checkout/{key}', [KioskController::class, 'checkoutForm'])->name('checkout');
        Route::post('/checkout/{key}', [KioskController::class, 'processCheckout'])->name('process-checkout');
        Route::get('/checkin/{key}', [KioskController::class, 'checkinForm'])->name('checkin');
        Route::post('/checkin/{key}', [KioskController::class, 'processCheckin'])->name('process-checkin');
        Route::get('/search-holder', [KioskController::class, 'searchHolder'])->name('search-holder');
        Route::post('/temporary-staff', [KioskController::class, 'createTemporaryStaff'])->name('create-temporary-staff');
        Route::post('/permanent-manual-staff', [KioskController::class, 'createPermanentManualStaff'])->name('create-permanent-manual-staff');
    });

    // Key Management Routes
    Route::prefix('keys')->name('keys.')->middleware(['role:admin|security|hr'])->group(function () {
        Route::get('/', [KeyController::class, 'index'])->name('index');
        Route::get('/create', [KeyController::class, 'create'])->name('create')->middleware('role:admin');
        Route::post('/', [KeyController::class, 'store'])->name('store')->middleware('role:admin');
        Route::get('/{key}', [KeyController::class, 'show'])->name('show');
        Route::get('/{key}/edit', [KeyController::class, 'edit'])->name('edit')->middleware('role:admin');
        Route::put('/{key}', [KeyController::class, 'update'])->name('update')->middleware('role:admin');
        Route::delete('/{key}', [KeyController::class, 'destroy'])->name('destroy')->middleware('role:admin');
        Route::post('/{key}/generate-tags', [KeyController::class, 'generateTags'])->name('generate-tags')->middleware('role:admin');
        Route::get('/{key}/print-tags', [KeyController::class, 'printTags'])->name('print-tags')->middleware('role:admin');
        Route::post('/{key}/mark-lost', [KeyController::class, 'markAsLost'])->name('mark-lost')->middleware('role:admin|security');
    });

    // Location Management Routes
    Route::prefix('locations')->name('locations.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/create', [LocationController::class, 'create'])->name('create');
        Route::post('/', [LocationController::class, 'store'])->name('store');
        Route::get('/{location}', [LocationController::class, 'show'])->name('show');
        Route::get('/{location}/edit', [LocationController::class, 'edit'])->name('edit');
        Route::put('/{location}', [LocationController::class, 'update'])->name('update');
        Route::delete('/{location}', [LocationController::class, 'destroy'])->name('destroy');
        Route::get('/api/buildings', [LocationController::class, 'getBuildings'])->name('api.buildings');
        Route::get('/api/rooms', [LocationController::class, 'getRooms'])->name('api.rooms');
    });

    // HR Management Routes
    Route::prefix('hr')->name('hr.')->middleware(['role:admin|hr'])->group(function () {
        Route::get('/dashboard', [HrController::class, 'dashboard'])->name('dashboard');
        
        // HR Staff Routes
        Route::prefix('staff')->name('staff.')->group(function () {
            Route::get('/', [HrController::class, 'hrStaffIndex'])->name('index');
            Route::get('/create', [HrController::class, 'hrStaffCreate'])->name('create');
            Route::post('/', [HrController::class, 'hrStaffStore'])->name('store');
            Route::get('/{hrStaff}', [HrController::class, 'hrStaffShow'])->name('show');
            Route::get('/{hrStaff}/edit', [HrController::class, 'hrStaffEdit'])->name('edit');
            Route::put('/{hrStaff}', [HrController::class, 'hrStaffUpdate'])->name('update');
            Route::delete('/{hrStaff}', [HrController::class, 'hrStaffDestroy'])->name('destroy');
        });

        // Manual Staff Routes
        Route::prefix('manual-staff')->name('manual-staff.')->group(function () {
            Route::get('/', [HrController::class, 'manualStaffIndex'])->name('index');
            Route::get('/create', [HrController::class, 'createManualStaff'])->name('create');
            Route::post('/', [HrController::class, 'storeManualStaff'])->name('store');
            Route::get('/{permanentStaffManual}', [HrController::class, 'showManualStaff'])->name('show');
            Route::get('/{permanentStaffManual}/edit', [HrController::class, 'editManualStaff'])->name('edit');
            Route::put('/{permanentStaffManual}', [HrController::class, 'updateManualStaff'])->name('update');
            Route::delete('/{permanentStaffManual}', [HrController::class, 'destroyManualStaff'])->name('destroy');
        });

        // Import Routes
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/hr-staff', [HrController::class, 'importHrStaffForm'])->name('form');
            Route::post('/hr-staff', [HrController::class, 'importHrStaff'])->name('hr-staff');
        });

        // Discrepancy Routes
        Route::prefix('discrepancies')->name('discrepancies.')->group(function () {
            Route::get('/', [HrController::class, 'discrepanciesIndex'])->name('index');
            Route::post('/{keyLog}/resolve', [HrController::class, 'resolveDiscrepancy'])->name('resolve');
            Route::post('/bulk-resolve', [HrController::class, 'bulkResolveDiscrepancies'])->name('bulk-resolve');
        });
    });

    // Report Routes - FIXED: Made key-activity accessible to security role
    Route::prefix('reports')->name('reports.')->middleware(['role:admin|hr|auditor|security'])->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/analytics', [ReportController::class, 'analyticsDashboard'])->name('analytics');
        
        // Key Activity Route - FIXED: Made accessible to security role
        Route::get('/key-activity', [ReportController::class, 'keyActivity'])->name('key-activity');
        
        Route::get('/current-holders', [ReportController::class, 'currentHolders'])->name('current-holders');
        Route::get('/overdue-keys', [ReportController::class, 'overdueKeys'])->name('overdue-keys');
        Route::get('/staff-activity', [ReportController::class, 'staffActivity'])->name('staff-activity');
        Route::get('/security-performance', [ReportController::class, 'securityPerformance'])->name('security-performance');
        
        // Additional Report Routes
        Route::get('/location-usage', [ReportController::class, 'locationUsage'])->name('location-usage');
        Route::get('/holder-statistics', [ReportController::class, 'holderStatistics'])->name('holder-statistics');
        Route::get('/system-metrics', [ReportController::class, 'systemMetrics'])->name('system-metrics');
        Route::get('/audit-trail', [ReportController::class, 'keyAuditTrail'])->name('audit-trail');
        Route::get('/peak-usage', [ReportController::class, 'peakUsage'])->name('peak-usage');
        Route::get('/key-utilization', [ReportController::class, 'keyUtilization'])->name('key-utilization');
        Route::get('/discrepancies', [ReportController::class, 'discrepancyReport'])->name('discrepancies');
        Route::get('/return-timeliness', [ReportController::class, 'returnTimeliness'])->name('return-timeliness');
        
        // Export routes
        Route::post('/export/key-activity', [ReportController::class, 'exportKeyActivity'])->name('export-key-activity');
        Route::post('/export/bulk', [ReportController::class, 'bulkExport'])->name('bulk-export');
    });

    // Admin Routes
    Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
        Route::get('/users', [AdminController::class, 'userManagement'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('store-user');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('update-user');
        Route::get('/settings', [AdminController::class, 'systemSettings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'updateSettings'])->name('update-settings');
        Route::get('/system-health', [AdminController::class, 'systemHealth'])->name('system-health');
    });
    
    // Debug and Testing Routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/check-table', function() {
            try {
                $columns = \DB::select('DESCRIBE key_logs');
                $count = \DB::table('key_logs')->count();
                
                return response()->json([
                    'table_exists' => true,
                    'column_count' => count($columns),
                    'row_count' => $count,
                    'columns' => $columns
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'table_exists' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        });
        
        Route::get('/debug-table', function() {
            $columns = \DB::select('DESCRIBE key_logs');
            echo "<h3>key_logs table structure:</h3>";
            echo "<pre>";
            print_r($columns);
            echo "</pre>";
            
            $sampleData = \DB::table('key_logs')
                ->leftJoin('keys', 'key_logs.key_id', '=', 'keys.id')
                ->leftJoin('users', 'key_logs.receiver_user_id', '=', 'users.id')
                ->select('key_logs.*', 'keys.label as key_label', 'keys.code as key_code', 'users.name as receiver_name')
                ->limit(5)
                ->get();
                
            echo "<h3>Sample key_logs data with joins:</h3>";
            echo "<pre>";
            print_r($sampleData->toArray());
            echo "</pre>";
            
            // Test the key activity query
            $testQuery = \App\Models\KeyLog::with(['key.location', 'receiver'])
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            echo "<h3>Eloquent query test:</h3>";
            echo "<pre>";
            print_r($testQuery->toArray());
            echo "</pre>";
        });
        
        Route::get('/test-key-activity', [ReportController::class, 'keyActivity'])->name('test-key-activity');
    });
    
    Route::get('/test-profile-edit', function() {
        $user = auth()->user();
        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_phone' => $user->phone,
            'message' => 'User data is available'
        ]);
    });
    
    Route::post('/email/verification-notification', function () {
        return redirect()->back()->with('status', 'Verification link sent!');
    })->middleware(['auth'])->name('verification.send');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Test route for key activity without auth (for debugging)
Route::get('/test-key-logs', function() {
    try {
        $logs = \App\Models\KeyLog::with(['key.location', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'total_logs' => \App\Models\KeyLog::count(),
            'sample_data' => $logs,
            'sample_count' => $logs->count()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});