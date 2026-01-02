<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\Location;
use App\Models\KeyTag;
use App\Models\KeyLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KeyController extends Controller
{
    public function index()
    {
        // Get locations for the filter dropdown
        $locations = Location::active()->get();

        $keys = Key::with([
            'location',
            'keyTags',
            'currentHolder' // This will load the KeyLog relationship
        ])
            ->latest()
            ->paginate(20);

        return view('keys.index', compact('keys', 'locations'));
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

        DB::transaction(function () use ($validated, $request) {
            $key = Key::create([
                'code' => $validated['code'],
                'label' => $validated['label'],
                'description' => $validated['description'],
                'key_type' => $validated['key_type'],
                'location_id' => $validated['location_id'],
                'status' => 'available', // Set initial status
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
        // FIXED: Use proper eager loading and accessor
        $key->load([
            'location',
            'keyTags',
            'keyLogs.receiver',
            'lastLog'
        ]);

        // FIXED: Use the accessor instead of direct relationship
        $currentLog = $key->current_holder;
        $history = $key->keyLogs()
            ->with(['receiver'])
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
            return redirect()->back()->with('error', 'No active QR tags found for this key.')
                ->with('showGenerateTags', true); // Add this to show generate tags prompt
        }

        // Check if we're in print preview mode
        if (request()->has('preview')) {
            return view('keys.print-tags', compact('key', 'tags'));
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
    public function scan($uuid)
    {
        $tag = KeyTag::where('uuid', $uuid)->with('key')->firstOrFail();

        if (!$tag->is_active) {
            return redirect()->route('keys.show', $tag->key_id)
                ->with('error', 'This QR tag has been deactivated.');
        }

        return redirect()->route('keys.show', $tag->key_id)
            ->with('info', 'QR code scanned successfully.');
    }
    public function markAsLost(Key $key)
    {
        if (!$key->isCheckedOut()) {
            return redirect()->back()->with('error', 'Only checked out keys can be marked as lost.');
        }

        // FIXED: Use the accessor instead of direct relationship
        $currentHolder = $key->current_holder;

        if (!$currentHolder) {
            return redirect()->back()->with('error', 'Unable to find current checkout record for this key.');
        }

        DB::transaction(function () use ($key, $currentHolder) {
            $key->update(['status' => 'lost']);

            // Log the loss
            KeyLog::create([
                'key_id' => $key->id,
                'action' => 'lost', // Use 'lost' action instead of 'checkin'
                'holder_type' => $currentHolder->holder_type,
                'holder_id' => $currentHolder->holder_id,
                'holder_name' => $currentHolder->holder_name,
                'holder_phone' => $currentHolder->holder_phone,
                'receiver_user_id' => auth()->id(),
                'receiver_name' => auth()->user()->name,
                'returned_from_log_id' => $currentHolder->id,
                'notes' => 'Key reported as lost',
                'verified' => false,
                'discrepancy' => true,
            ]);
        });

        return redirect()->route('keys.show', $key)
            ->with('warning', 'Key marked as lost. Security team has been notified.');
    }

    // ADDITIONAL HELPER METHODS

    public function checkoutForm(Key $key)
    {
        if (!$key->isAvailable()) {
            return redirect()->back()->with('error', 'Key is not available for checkout.');
        }

        return view('keys.checkout', compact('key'));
    }

    public function checkout(Request $request, Key $key)
    {
        if (!$key->isAvailable()) {
            return redirect()->back()->with('error', 'Key is not available for checkout.');
        }

        $validated = $request->validate([
            'holder_type' => 'required|in:student,staff,visitor,contractor',
            'holder_id' => 'required_if:holder_type,student,staff',
            'holder_name' => 'required|string|max:255',
            'holder_phone' => 'required|string|max:20',
            'expected_return_at' => 'nullable|date|after:now',
        ]);

        try {
            $holderData = [
                'type' => $validated['holder_type'],
                'id' => $validated['holder_id'] ?? null,
                'name' => $validated['holder_name'],
                'phone' => $validated['holder_phone'],
            ];

            $key->checkout($holderData, auth()->id(), $validated['expected_return_at'] ?? null);

            return redirect()->route('keys.show', $key)
                ->with('success', 'Key checked out successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error during checkout: ' . $e->getMessage());
        }
    }

    public function checkinForm(Key $key)
    {
        if (!$key->isCheckedOut()) {
            return redirect()->back()->with('error', 'Key is not currently checked out.');
        }

        return view('keys.checkin', compact('key'));
    }

    public function checkin(Request $request, Key $key)
    {
        if (!$key->isCheckedOut()) {
            return redirect()->back()->with('error', 'Key is not currently checked out.');
        }

        $validated = $request->validate([
            'signature' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // FIXED: Use the accessor in the checkin method too
            $key->checkin(auth()->id(), $validated['signature'], $validated['photo'], $validated['notes']);

            return redirect()->route('keys.show', $key)
                ->with('success', 'Key checked in successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error during checkin: ' . $e->getMessage());
        }
    }
}
