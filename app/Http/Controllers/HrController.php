<?php

namespace App\Http\Controllers;

use App\Models\HrStaff;
use App\Models\PermanentStaffManual;
use App\Models\KeyLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\HrStaffImport;
use Maatwebsite\Excel\Facades\Excel;

class HrController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_hr_staff' => HrStaff::count(),
            'active_hr_staff' => HrStaff::where('status', 'active')->count(),
            'total_manual_staff' => PermanentStaffManual::count(),
            'pending_discrepancies' => KeyLog::where('discrepancy', true)->where('verified', false)->count(),
        ];

        $recentDiscrepancies = KeyLog::where('discrepancy', true)
            ->where('verified', false)
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
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('staff_id', 'like', '%' . $request->search . '%');
            });
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

    public function hrStaffCreate()
    {
        return view('hr.staff.create');
    }

    public function hrStaffStore(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|string|max:50|unique:hr_staff,staff_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:hr_staff,email',
            'phone' => 'nullable|string|max:20',
            'dept' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        HrStaff::create($validated);

        return redirect()->route('hr.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function hrStaffShow(HrStaff $hrStaff)
    {
        $hrStaff->load('keyLogs.key.location', 'keyLogs.receiver');
        
        $currentKeys = $hrStaff->keyLogs()
            ->where('action', 'checkout')
            ->whereNull('returned_from_log_id')
            ->with('key.location')
            ->get();
            
        $keyHistory = $hrStaff->keyLogs()
            ->with(['key.location', 'receiver'])
            ->latest()
            ->paginate(10);

        return view('hr.staff.show', compact('hrStaff', 'currentKeys', 'keyHistory'));
    }

    public function hrStaffEdit(HrStaff $hrStaff)
    {
        return view('hr.staff.edit', compact('hrStaff'));
    }

    public function hrStaffUpdate(Request $request, HrStaff $hrStaff)
    {
        $validated = $request->validate([
            'staff_id' => 'required|string|max:50|unique:hr_staff,staff_id,' . $hrStaff->id,
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:hr_staff,email,' . $hrStaff->id,
            'phone' => 'nullable|string|max:20',
            'dept' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $hrStaff->update($validated);

        return redirect()->route('hr.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function hrStaffDestroy(HrStaff $hrStaff)
    {
        $hrStaff->delete();

        return redirect()->route('hr.staff.index')
            ->with('success', 'Staff member deleted successfully.');
    }

    public function importHrStaffForm()
    {
        return view('hr.import.hr-staff');
    }

    public function importHrStaff(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx|max:10240', // Added xlsx support
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
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('staff_id', 'like', '%' . $request->search . '%')
                  ->orWhere('dept', 'like', '%' . $request->search . '%');
            });
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

    public function destroyManualStaff(PermanentStaffManual $permanentStaffManual)
    {
        $permanentStaffManual->delete();

        return redirect()->route('hr.manual-staff.index')
            ->with('success', 'Manual staff record deleted successfully.');
    }

    public function discrepanciesIndex()
    {
        $discrepancies = KeyLog::where('discrepancy', true)
            ->where('verified', false)
            ->with(['key.location', 'receiver'])
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
                $keyLog->update([
                    'verified' => true,
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                ]);
            } else {
                $keyLog->update([
                    'notes' => ($keyLog->notes ? $keyLog->notes . ' ' : '') . $request->notes . ' [Rejected by HR]',
                    'discrepancy_reason' => $request->notes,
                ]);
            }
        });

        $message = $request->action === 'verify' 
            ? 'Discrepancy resolved and verified.' 
            : 'Discrepancy marked as rejected.';

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
                    $log->update([
                        'verified' => true,
                        'verified_at' => now(),
                        'verified_by' => auth()->id(),
                    ]);
                } else {
                    $log->update([
                        'discrepancy_reason' => 'Bulk rejected by HR',
                    ]);
                }
                $resolved++;
            }
        }

        $message = $request->action === 'verify'
            ? "{$resolved} discrepancies verified successfully."
            : "{$resolved} discrepancies rejected successfully.";

        return redirect()->route('hr.discrepancies.index')
            ->with('success', $message);
    }

    // ADDITIONAL HELPER METHODS

    public function editManualStaff(PermanentStaffManual $permanentStaffManual)
    {
        return view('hr.manual-staff.edit', compact('permanentStaffManual'));
    }

    public function updateManualStaff(Request $request, PermanentStaffManual $permanentStaffManual)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:permanent_staff_manual,phone,' . $permanentStaffManual->id,
            'staff_id' => 'nullable|string|max:50|unique:permanent_staff_manual,staff_id,' . $permanentStaffManual->id,
            'dept' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $permanentStaffManual->update($validated);

        return redirect()->route('hr.manual-staff.index')
            ->with('success', 'Manual staff record updated successfully.');
    }

    public function showManualStaff(PermanentStaffManual $permanentStaffManual)
    {
        $permanentStaffManual->load('addedBy', 'keyLogs.key.location', 'keyLogs.receiver');
        
        $currentKeys = $permanentStaffManual->keyLogs()
            ->where('action', 'checkout')
            ->whereNull('returned_from_log_id')
            ->with('key.location')
            ->get();
            
        $keyHistory = $permanentStaffManual->keyLogs()
            ->with(['key.location', 'receiver'])
            ->latest()
            ->paginate(10);

        return view('hr.manual-staff.show', compact('permanentStaffManual', 'currentKeys', 'keyHistory'));
    }
}