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
