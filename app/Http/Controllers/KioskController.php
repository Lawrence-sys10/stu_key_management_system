<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\KeyLog;
use App\Models\KeyTag;
use App\Models\HrStaff;
use App\Models\PermanentStaffManual;
use App\Models\TemporaryStaff;
use App\Models\User;
use App\Models\Setting;
use App\Helpers\IrmtsHelper;
use App\Services\HrSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    public function index()
    {
        $recentActivity = KeyLog::with(['key', 'receiver'])
            ->latest()
            ->limit(10)
            ->get();
            
        return view('kiosk.index', compact('recentActivity'));
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
            'signature' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $key, $request) {
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
            'returned_by_same_person' => 'required|boolean',
            'returned_by_type' => 'required_if:returned_by_same_person,false|in:hr,perm_manual,temp,visitor',
            'returned_by_id' => 'nullable',
            'returned_by_name' => 'required_if:returned_by_same_person,false|string|max:255',
            'returned_by_phone' => 'required_if:returned_by_same_person,false|string|max:20',
            'signature' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $key, $request) {
            // Handle file uploads
            $signaturePath = null;
            $photoPath = null;

            if (!empty($validated['signature'])) {
                $signaturePath = $this->storeSignature($validated['signature']);
            }

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('key-photos', 'public');
            }

            // Get the current checkout log
            $checkoutLog = KeyLog::where('key_id', $key->id)
                ->where('action', 'checkout')
                ->whereNull('returned_from_log_id')
                ->latest()
                ->first();

            if (!$checkoutLog) {
                throw new \Exception('No active checkout record found for this key.');
            }

            // Determine returner information
            $returnedBySamePerson = $validated['returned_by_same_person'];
            
            if ($returnedBySamePerson) {
                // Same person is returning - use original holder info
                $returnedByType = $checkoutLog->holder_type;
                $returnedById = $checkoutLog->holder_id;
                $returnedByName = $checkoutLog->holder_name;
                $returnedByPhone = $checkoutLog->holder_phone;
                $verified = true;
                $discrepancy = false;
            } else {
                // Different person is returning
                $returnedByType = $validated['returned_by_type'];
                $returnedById = $validated['returned_by_id'];
                $returnedByName = $validated['returned_by_name'];
                $returnedByPhone = $validated['returned_by_phone'];
                
                // Verify the returner's data if they're in the system
                if ($returnedById && $returnedByType !== 'visitor') {
                    $verified = $this->verifyHolderData($returnedByType, $returnedById, $returnedByPhone);
                } else {
                    $verified = false; // Manual entry for visitors or when no ID
                }
                $discrepancy = true; // Flag as discrepancy when different person returns
            }

            // Create checkin log
            $log = KeyLog::create([
                'key_id' => $key->id,
                'action' => 'checkin',
                'holder_type' => $checkoutLog->holder_type,
                'holder_id' => $checkoutLog->holder_id,
                'holder_name' => $checkoutLog->holder_name,
                'holder_phone' => $checkoutLog->holder_phone,
                'receiver_user_id' => auth()->id(),
                'receiver_name' => auth()->user()->name,
                'returned_from_log_id' => $checkoutLog->id,
                'returned_by_type' => $returnedByType,
                'returned_by_id' => $returnedById,
                'returned_by_name' => $returnedByName,
                'returned_by_phone' => $returnedByPhone,
                'actual_return_at' => now(),
                'signature_path' => $signaturePath,
                'photo_path' => $photoPath,
                'notes' => $validated['notes'],
                'verified' => $verified,
                'discrepancy' => $discrepancy,
                'discrepancy_reason' => $discrepancy ? 'Returned by different person' : null,
            ]);

            // Update the checkout log to mark it as returned
            $checkoutLog->update([
                'returned_from_log_id' => $log->id
            ]);

            // Update key status
            $key->update([
                'status' => 'available',
                'last_log_id' => $log->id,
            ]);

            // Queue notifications
            if (Setting::getValue('notifications.return_enabled', false)) {
                \App\Jobs\SendReturnNotification::dispatch($log);
                
                // If returned by different person, send special notification
                if (!$returnedBySamePerson) {
                    \App\Jobs\SendDifferentPersonReturnNotification::dispatch($log);
                }
            }
        });

        // Custom success message based on return scenario
        if ($validated['returned_by_same_person']) {
            $message = "Key {$key->label} checked in successfully by {$checkoutLog->holder_name}.";
        } else {
            $message = "Key {$key->label} returned by {$validated['returned_by_name']} on behalf of {$checkoutLog->holder_name}.";
        }

        return redirect()->route('kiosk.index')
            ->with('success', $message);
    }

    public function searchHolder(Request $request)
    {
        $search = $request->get('q');

        if (empty($search)) {
            return response()->json([]);
        }

        // Start with an empty collection
        $results = collect();

        // Search HR Staff (local database)
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
                    'source' => 'local',
                ];
            });

        $results = $results->merge($hrStaff);

        // Search Permanent Manual Staff (local database)
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
                    'source' => 'local',
                ];
            });

        $results = $results->merge($permStaff);

        // Search Temporary Staff (local database)
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
                    'source' => 'local',
                ];
            });

        $results = $results->merge($tempStaff);

        // If local search didn't find enough results, check IRMTS API
        if ($results->count() < 5) {
            try {
                // Try to find staff via IRMTS unified API
                $irmtsData = IrmtsHelper::lookup($search, 'staff');
                
                if ($irmtsData) {
                    // Determine staff type based on staff_type field
                    $staffType = 'hr';
                    $typeLabel = 'HR Staff';
                    
                    if (isset($irmtsData['staff_type'])) {
                        if ($irmtsData['staff_type'] === 'part_time') {
                            $staffType = 'part_time';
                            $typeLabel = 'Part-Time Staff';
                        }
                    }
                    
                    // Check if this staff already exists in results
                    $exists = $results->first(function ($item) use ($irmtsData) {
                        return isset($item['staff_id']) && 
                               $item['staff_id'] === ($irmtsData['staff_id'] ?? null);
                    });
                    
                    if (!$exists) {
                        // Optionally sync to local database for future lookups
                        $hrSyncService = app(HrSyncService::class);
                        $syncedStaff = $hrSyncService->syncStaffFromIrmts($irmtsData);
                        
                        $results->push([
                            'id' => $syncedStaff ? $syncedStaff->id : null, // Now in local DB if synced
                            'name' => $irmtsData['name'] ?? 'Unknown',
                            'phone' => $irmtsData['phone'] ?? '',
                            'type' => $staffType,
                            'type_label' => $typeLabel,
                            'dept' => $irmtsData['department'] ?? 'N/A',
                            'staff_id' => $irmtsData['staff_id'] ?? $search,
                            'email' => $irmtsData['email'] ?? null,
                            'source' => $syncedStaff ? 'irmts_synced' : 'irmts',
                            'irmts_data' => $irmtsData, // Store full data for verification
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('IRMTS lookup failed during search', [
                    'search' => $search,
                    'error' => $e->getMessage(),
                ]);
                // Continue without IRMTS results if API fails
            }
        }

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
        // First, try local database verification
        if ($holderType === 'hr') {
            $staff = HrStaff::where('id', $holderId)->first();
            if ($staff && $staff->phone === $holderPhone) {
                return true;
            }
        }

        if ($holderType === 'perm_manual') {
            $staff = PermanentStaffManual::where('id', $holderId)->first();
            if ($staff && $staff->phone === $holderPhone) {
                return true;
            }
        }

        if ($holderType === 'temp') {
            $staff = TemporaryStaff::where('id', $holderId)->first();
            if ($staff && $staff->phone === $holderPhone) {
                return true;
            }
        }

        // If local verification failed, try IRMTS API
        // This handles cases where staff was found via IRMTS but not in local DB
        if ($holderId === null || $holderType === 'part_time') {
            try {
                // Try to lookup by phone number or staff ID
                $identifier = $holderPhone ?: $holderId;
                $irmtsData = IrmtsHelper::lookup($identifier, 'staff');
                
                if ($irmtsData) {
                    // Verify phone matches
                    $irmtsPhone = $irmtsData['phone'] ?? '';
                    return $irmtsPhone === $holderPhone;
                }
            } catch (\Exception $e) {
                Log::warning('IRMTS verification failed', [
                    'holder_type' => $holderType,
                    'holder_id' => $holderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return false;
    }

    /**
     * NEW: Quick checkin method for same person returns (backward compatibility)
     */
    public function quickCheckin(Key $key)
    {
        if (!$key->isCheckedOut()) {
            return redirect()->route('kiosk.scan')->with('error', 'Key is not currently checked out.');
        }

        DB::transaction(function () use ($key) {
            // Get the current checkout log
            $checkoutLog = KeyLog::where('key_id', $key->id)
                ->where('action', 'checkout')
                ->whereNull('returned_from_log_id')
                ->latest()
                ->first();

            if (!$checkoutLog) {
                throw new \Exception('No active checkout record found for this key.');
            }

            // Create checkin log with same person return
            $log = KeyLog::create([
                'key_id' => $key->id,
                'action' => 'checkin',
                'holder_type' => $checkoutLog->holder_type,
                'holder_id' => $checkoutLog->holder_id,
                'holder_name' => $checkoutLog->holder_name,
                'holder_phone' => $checkoutLog->holder_phone,
                'receiver_user_id' => auth()->id(),
                'receiver_name' => auth()->user()->name,
                'returned_from_log_id' => $checkoutLog->id,
                'returned_by_type' => $checkoutLog->holder_type,
                'returned_by_id' => $checkoutLog->holder_id,
                'returned_by_name' => $checkoutLog->holder_name,
                'returned_by_phone' => $checkoutLog->holder_phone,
                'actual_return_at' => now(),
                'verified' => true,
                'discrepancy' => false,
            ]);

            // Update the checkout log to mark it as returned
            $checkoutLog->update([
                'returned_from_log_id' => $log->id
            ]);

            // Update key status
            $key->update([
                'status' => 'available',
                'last_log_id' => $log->id,
            ]);

            // Queue notifications
            if (Setting::getValue('notifications.return_enabled', false)) {
                \App\Jobs\SendReturnNotification::dispatch($log);
            }
        });

        return redirect()->route('kiosk.index')
            ->with('success', "Key {$key->label} checked in successfully.");
    }

    /**
     * NEW: Get key return history with returner information
     */
    public function keyReturnHistory(Key $key)
    {
        $checkins = KeyLog::with(['receiver', 'returnedFromLog'])
            ->where('key_id', $key->id)
            ->where('action', 'checkin')
            ->latest()
            ->paginate(20);

        return view('kiosk.return-history', compact('key', 'checkins'));
    }

    /**
     * NEW: Get statistics about different person returns
     */
    public function returnStatistics()
    {
        $totalReturns = KeyLog::checkin()->count();
        $differentPersonReturns = KeyLog::returnedByDifferentPerson()->count();
        $samePersonReturns = $totalReturns - $differentPersonReturns;

        $recentDifferentReturns = KeyLog::returnedByDifferentPerson()
            ->with(['key', 'receiver'])
            ->latest()
            ->limit(10)
            ->get();

        return view('kiosk.return-statistics', compact(
            'totalReturns',
            'differentPersonReturns',
            'samePersonReturns',
            'recentDifferentReturns'
        ));
    }
}