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
