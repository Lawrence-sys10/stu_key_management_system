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
