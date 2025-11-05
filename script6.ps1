# Step 6: Generate Jobs, Services, Middleware & Routes
Write-Host "Creating Jobs, Services, Middleware and Routes..." -ForegroundColor Green

# Create directories
$jobsDir = ".\app\Jobs"
$servicesDir = ".\app\Services"
$middlewareDir = ".\app\Http\Middleware"
if (!(Test-Path $jobsDir)) { New-Item -ItemType Directory -Path $jobsDir -Force }
if (!(Test-Path $servicesDir)) { New-Item -ItemType Directory -Path $servicesDir -Force }
if (!(Test-Path $middlewareDir)) { New-Item -ItemType Directory -Path $middlewareDir -Force }

# 1. Create Jobs

# SendCheckoutNotification Job
@'
<?php

namespace App\Jobs;

use App\Models\KeyLog;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCheckoutNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $keyLog;
    public $tries = 3;

    public function __construct(KeyLog $keyLog)
    {
        $this->keyLog = $keyLog->withoutRelations();
    }

    public function handle(NotificationService $notificationService)
    {
        try {
            $notificationService->sendCheckoutNotification($this->keyLog);
        } catch (\Exception $e) {
            \Log::error('Failed to send checkout notification: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Exception $exception)
    {
        \Log::error('SendCheckoutNotification job failed: ' . $exception->getMessage());
    }
}
'@ | Out-File -FilePath .\app\Jobs\SendCheckoutNotification.php -Encoding UTF8

# SendReturnNotification Job
@'
<?php

namespace App\Jobs;

use App\Models\KeyLog;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReturnNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $keyLog;
    public $tries = 3;

    public function __construct(KeyLog $keyLog)
    {
        $this->keyLog = $keyLog->withoutRelations();
    }

    public function handle(NotificationService $notificationService)
    {
        try {
            $notificationService->sendReturnNotification($this->keyLog);
        } catch (\Exception $e) {
            \Log::error('Failed to send return notification: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Exception $exception)
    {
        \Log::error('SendReturnNotification job failed: ' . $exception->getMessage());
    }
}
'@ | Out-File -FilePath .\app\Jobs\SendReturnNotification.php -Encoding UTF8

# SendOverdueNotification Job
@'
<?php

namespace App\Jobs;

use App\Models\KeyLog;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOverdueNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $keyLog;
    public $tries = 3;

    public function __construct(KeyLog $keyLog)
    {
        $this->keyLog = $keyLog->withoutRelations();
    }

    public function handle(NotificationService $notificationService)
    {
        try {
            $notificationService->sendOverdueNotification($this->keyLog);
        } catch (\Exception $e) {
            \Log::error('Failed to send overdue notification: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Exception $exception)
    {
        \Log::error('SendOverdueNotification job failed: ' . $exception->getMessage());
    }
}
'@ | Out-File -FilePath .\app\Jobs\SendOverdueNotification.php -Encoding UTF8

# ProcessHrSync Job
@'
<?php

namespace App\Jobs;

use App\Models\HrStaff;
use App\Services\HrSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessHrSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function handle(HrSyncService $hrSyncService)
    {
        try {
            if (!config('services.hr_sync.enabled', false)) {
                Log::info('HR Sync is disabled in configuration');
                return;
            }

            $result = $hrSyncService->syncStaff();

            Log::info('HR Sync completed', [
                'new_records' => $result['new_records'],
                'updated_records' => $result['updated_records'],
                'total_records' => $result['total_records'],
            ]);

        } catch (\Exception $e) {
            Log::error('HR Sync failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Exception $exception)
    {
        Log::error('ProcessHrSync job failed: ' . $exception->getMessage());
    }
}
'@ | Out-File -FilePath .\app\Jobs\ProcessHrSync.php -Encoding UTF8

# CheckOverdueKeys Job
@'
<?php

namespace App\Jobs;

use App\Models\KeyLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckOverdueKeys implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $overdueKeys = KeyLog::overdue()->get();

        foreach ($overdueKeys as $keyLog) {
            // Check if we already sent a notification for this overdue key today
            $today = now()->format('Y-m-d');
            $existingNotification = \App\Models\Notification::where('key_log_id', $keyLog->id)
                ->where('template_key', 'overdue_notice')
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingNotification) {
                SendOverdueNotification::dispatch($keyLog);
            }
        }

        Log::info('Overdue keys check completed', ['count' => $overdueKeys->count()]);
    }
}
'@ | Out-File -FilePath .\app\Jobs\CheckOverdueKeys.php -Encoding UTF8

# 2. Create Services

# NotificationService
@'
<?php

namespace App\Services;

use App\Models\KeyLog;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendCheckoutNotification(KeyLog $keyLog)
    {
        if (!Setting::getValue('notifications.checkout_enabled', false)) {
            return;
        }

        $template = Setting::getValue('notifications.checkout_template', 
            'Key {KEY_LABEL} ({LOCATION}) checked out by {NAME} at {TIME}. Expected return: {DUE}.');

        $message = $this->replaceTemplateVariables($template, $keyLog);

        return $this->sendSms($keyLog->holder_phone, $message, 'checkout_notice', $keyLog);
    }

    public function sendReturnNotification(KeyLog $keyLog)
    {
        if (!Setting::getValue('notifications.return_enabled', false)) {
            return;
        }

        $template = Setting::getValue('notifications.return_template',
            'Thanks, {NAME}. Key {KEY_LABEL} returned at {TIME}.');

        $message = $this->replaceTemplateVariables($template, $keyLog);

        return $this->sendSms($keyLog->holder_phone, $message, 'return_confirm', $keyLog);
    }

    public function sendOverdueNotification(KeyLog $keyLog)
    {
        if (!Setting::getValue('notifications.overdue_enabled', false)) {
            return;
        }

        $template = Setting::getValue('notifications.overdue_template',
            'Reminder: Key {KEY_LABEL} is overdue. Please return ASAP.');

        $message = $this->replaceTemplateVariables($template, $keyLog);

        return $this->sendSms($keyLog->holder_phone, $message, 'overdue_notice', $keyLog);
    }

    private function replaceTemplateVariables($template, KeyLog $keyLog)
    {
        $variables = [
            '{KEY_LABEL}' => $keyLog->key->label,
            '{KEY_CODE}' => $keyLog->key->code,
            '{LOCATION}' => $keyLog->key->location->full_address,
            '{NAME}' => $keyLog->holder_name,
            '{PHONE}' => $keyLog->holder_phone,
            '{TIME}' => $keyLog->created_at->format('M j, Y g:i A'),
            '{DUE}' => $keyLog->expected_return_at ? $keyLog->expected_return_at->format('M j, Y g:i A') : 'Not specified',
            '{OFFICER}' => $keyLog->receiver_name,
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    private function sendSms($to, $message, $templateKey, KeyLog $keyLog)
    {
        $provider = config('services.sms.default', 'hubtel');

        try {
            if ($provider === 'hubtel') {
                return $this->sendViaHubtel($to, $message, $templateKey, $keyLog);
            }

            // Add other providers here (twilio, etc.)
            Log::warning("SMS provider not implemented: {$provider}");

        } catch (\Exception $e) {
            Log::error("SMS sending failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function sendViaHubtel($to, $message, $templateKey, KeyLog $keyLog)
    {
        $clientId = config('services.hubtel.client_id');
        $clientSecret = config('services.hubtel.client_secret');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Hubtel credentials not configured');
        }

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->post('https://sms.hubtel.com/v1/messages/send', [
                'from' => config('services.hubtel.sender_id', 'STU-Keys'),
                'to' => $this->formatPhoneNumber($to),
                'content' => $message,
            ]);

        $notification = Notification::create([
            'key_log_id' => $keyLog->id,
            'channel' => 'sms',
            'to' => $to,
            'template_key' => $templateKey,
            'payload_json' => [
                'message' => $message,
                'provider' => 'hubtel',
                'provider_response' => $response->json(),
            ],
            'status' => $response->successful() ? 'sent' : 'failed',
            'sent_at' => $response->successful() ? now() : null,
            'error' => $response->successful() ? null : $response->body(),
        ]);

        if (!$response->successful()) {
            throw new \Exception('Hubtel API error: ' . $response->body());
        }

        return $notification;
    }

    private function formatPhoneNumber($phone)
    {
        // Ensure phone number is in E.164 format
        $phone = preg_replace('/\D/', '', $phone);
        
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '233' . substr($phone, 1);
        }
        
        if (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
            return '233' . $phone;
        }

        return $phone;
    }
}
'@ | Out-File -FilePath .\app\Services\NotificationService.php -Encoding UTF8

# HrSyncService
@'
<?php

namespace App\Services;

use App\Models\HrStaff;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrSyncService
{
    public function syncStaff()
    {
        $lastSync = Setting::getValue('hr.last_sync', null);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.hr_api.key'),
            'Accept' => 'application/json',
        ])->get(config('services.hr_api.base_url') . '/staff', [
            'updated_since' => $lastSync,
        ]);

        if (!$response->successful()) {
            throw new \Exception('HR API request failed: ' . $response->body());
        }

        $staffData = $response->json();
        $newRecords = 0;
        $updatedRecords = 0;

        foreach ($staffData['data'] ?? [] as $staff) {
            $existing = HrStaff::where('staff_id', $staff['staff_id'])->first();

            if ($existing) {
                $existing->update([
                    'name' => $staff['name'],
                    'phone' => $staff['phone'],
                    'dept' => $staff['department'],
                    'email' => $staff['email'],
                    'status' => $staff['status'],
                    'synced_at' => now(),
                ]);
                $updatedRecords++;
            } else {
                HrStaff::create([
                    'staff_id' => $staff['staff_id'],
                    'name' => $staff['name'],
                    'phone' => $staff['phone'],
                    'dept' => $staff['department'],
                    'email' => $staff['email'],
                    'status' => $staff['status'],
                    'source' => 'api',
                    'synced_at' => now(),
                ]);
                $newRecords++;
            }
        }

        Setting::setValue('hr.last_sync', now()->toISOString());

        return [
            'new_records' => $newRecords,
            'updated_records' => $updatedRecords,
            'total_records' => count($staffData['data'] ?? []),
        ];
    }

    public function testConnection()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hr_api.key'),
                'Accept' => 'application/json',
            ])->get(config('services.hr_api.base_url') . '/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('HR API connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
'@ | Out-File -FilePath .\app\Services\HrSyncService.php -Encoding UTF8

# QRService
@'
<?php

namespace App\Services;

use App\Models\Key;
use App\Models\KeyTag;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRService
{
    public function generateQRCode(KeyTag $keyTag, $size = 200)
    {
        $url = route('kiosk.scan-result', ['uuid' => $keyTag->uuid]);
        $content = "stu-keys://k/{$keyTag->uuid}";

        return QrCode::size($size)
            ->format('png')
            ->generate($content);
    }

    public function generateQRCodeForPrint(KeyTag $keyTag)
    {
        return $this->generateQRCode($keyTag, 150);
    }

    public function generatePrintSheet($keyTags)
    {
        $html = view('keys.print-sheet', compact('keyTags'))->render();
        
        // This would typically use DomPDF or similar
        // For now, return HTML that can be printed
        return $html;
    }

    public function storeQRCodeImage(KeyTag $keyTag)
    {
        $qrCode = $this->generateQRCode($keyTag, 300);
        $path = "qr-codes/{$keyTag->uuid}.png";
        
        Storage::disk('public')->put($path, $qrCode);
        
        return $path;
    }

    public function getQRCodeUrl(KeyTag $keyTag)
    {
        $path = "qr-codes/{$keyTag->uuid}.png";
        
        if (!Storage::disk('public')->exists($path)) {
            $this->storeQRCodeImage($keyTag);
        }
        
        return Storage::disk('public')->url($path);
    }
}
'@ | Out-File -FilePath .\app\Services\QRService.php -Encoding UTF8

# 3. Create Middleware

# RoleMiddleware
@'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role, $guard = null)
    {
        $authGuard = app('auth')->guard($guard);

        if ($authGuard->guest()) {
            return redirect()->route('login');
        }

        $roles = is_array($role)
            ? $role
            : explode('|', $role);

        if (!$authGuard->user()->hasAnyRole($roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
'@ | Out-File -FilePath .\app\Http\Middleware\RoleMiddleware.php -Encoding UTF8

# KioskMiddleware
@'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KioskMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasRole('security')) {
            abort(403, 'Only security personnel can access the kiosk.');
        }

        // Check if security officer is on shift
        if (!auth()->user()->isOnShift() && !$request->is('kiosk/start-shift')) {
            return redirect()->route('kiosk.start-shift')
                ->with('warning', 'Please start your shift before accessing the kiosk.');
        }

        return $next($request);
    }
}
'@ | Out-File -FilePath .\app\Http\Middleware\KioskMiddleware.php -Encoding UTF8

# 4. Create Route Files

# Web Routes
@'
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

Auth::routes(['register' => false]);

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
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('update-password');
        Route::get('/activity', [ProfileController::class, 'activityLog'])->name('activity');
        Route::get('/shifts', [ProfileController::class, 'shiftHistory'])->name('shift-history');
        Route::post('/shift/start', [ProfileController::class, 'startShift'])->name('start-shift');
        Route::post('/shift/end', [ProfileController::class, 'endShift'])->name('end-shift');
    });

    // Kiosk Routes
    Route::prefix('kiosk')->name('kiosk.')->middleware(['role:security', 'kiosk'])->group(function () {
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
            Route::get('/{hrStaff}', [HrController::class, 'hrStaffShow'])->name('show');
        });

        // Manual Staff Routes
        Route::prefix('manual-staff')->name('manual-staff.')->group(function () {
            Route::get('/', [HrController::class, 'manualStaffIndex'])->name('index');
            Route::get('/create', [HrController::class, 'createManualStaff'])->name('create');
            Route::post('/', [HrController::class, 'storeManualStaff'])->name('store');
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

    // Report Routes
    Route::prefix('reports')->name('reports.')->middleware(['role:admin|hr|auditor'])->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/key-activity', [ReportController::class, 'keyActivity'])->name('key-activity');
        Route::get('/current-holders', [ReportController::class, 'currentHolders'])->name('current-holders');
        Route::get('/overdue-keys', [ReportController::class, 'overdueKeys'])->name('overdue-keys');
        Route::get('/staff-activity', [ReportController::class, 'staffActivity'])->name('staff-activity');
        Route::get('/security-performance', [ReportController::class, 'securityPerformance'])->name('security-performance');
        Route::get('/analytics', [ReportController::class, 'analyticsDashboard'])->name('analytics');
        Route::post('/export/key-activity', [ReportController::class, 'exportKeyActivity'])->name('export-key-activity');
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
});
'@ | Out-File -FilePath .\routes\web.php -Encoding UTF8

# API Routes
@'
<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Key Operations
    Route::post('/keys/scan', [ApiController::class, 'scanKey'])->name('api.keys.scan');
    Route::get('/keys/{key}', [ApiController::class, 'getKeyDetails'])->name('api.keys.details');
    Route::post('/keys/checkout', [ApiController::class, 'checkoutKey'])->name('api.keys.checkout');
    Route::post('/keys/checkin', [ApiController::class, 'checkinKey'])->name('api.keys.checkin');
    
    // Staff Search
    Route::get('/staff/search', [ApiController::class, 'searchStaff'])->name('api.staff.search');
    
    // Dashboard Data
    Route::get('/dashboard/stats', [ApiController::class, 'getDashboardStats'])->name('api.dashboard.stats');
    Route::get('/dashboard/activity', [ApiController::class, 'getRecentActivity'])->name('api.dashboard.activity');
});

// Public endpoints (if any)
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});
'@ | Out-File -FilePath .\routes\api.php -Encoding UTF8

Write-Host "✅ All Jobs, Services, Middleware and Routes created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in:" -ForegroundColor Cyan
Write-Host "   - app/Jobs/ (5 jobs)" -ForegroundColor Cyan
Write-Host "   - app/Services/ (3 services)" -ForegroundColor Cyan
Write-Host "   - app/Http/Middleware/ (2 middleware)" -ForegroundColor Cyan
Write-Host "   - routes/ (web.php, api.php)" -ForegroundColor Cyan