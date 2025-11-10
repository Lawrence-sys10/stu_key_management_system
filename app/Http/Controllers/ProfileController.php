<?php

namespace App\Http\Controllers;

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

        // Simple counts that will definitely work
        $currentKeysCount = $user->keyLogsAsReceiver()->count(); // Temporary - count all transactions
        $totalActivities = $user->keyLogsAsReceiver()->count();

        return view('profile.show', compact(
            'user',
            'recentActivity',
            'currentShift',
            'currentKeysCount',
            'totalActivities'
        ));
    }

    public function edit()
    {
        $user = auth()->user();
        
        // Debug: Log information
        \Log::info('Profile edit accessed', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'route' => request()->url()
        ]);
        
        // Debug: Check if we can access user properties
        logger('User phone: ' . ($user->phone ?? 'NOT SET'));
        logger('User email: ' . $user->email);
        
        // If you want to see what's happening, uncomment this line:
        // dd($user); // This will dump user data and stop execution
        
        return view('profile.edit', compact('user'));
    }

    // ADD THIS METHOD - Profile Update
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $user->update($validated);
            
            \Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'changes' => $validated
            ]);
            
            return redirect()->route('profile.show')
                ->with('success', 'Profile updated successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update profile. Please try again.')
                ->withInput();
        }
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
        $user = auth()->user();
        
        // Use the safe method that doesn't load the holder relationship
        $activity = $user->keyLogsAsReceiver()
            ->with(['key.location', 'receiver']) // Only load safe relationships
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

        if (! $currentShift) {
            return redirect()->back()->with('error', 'No active shift found.');
        }

        $currentShift->update([
            'end_at' => now(),
            'notes' => $currentShift->notes."\n".$request->notes,
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Shift ended successfully.');
    }

    // Add this method to your ProfileController
    public function editPassword()
    {
        return view('profile.password');
    }
}