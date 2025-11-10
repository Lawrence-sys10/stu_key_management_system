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
