<?php

namespace App\Http\Controllers;

use App\Models\KeyLog;
use App\Models\Key;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function keyActivity(Request $request)
    {
        // Set default date range (last 7 days)
        $defaultStartDate = now()->subDays(7)->format('Y-m-d');
        $defaultEndDate = now()->format('Y-m-d');
        
        // Get filters from request or use defaults
        $filters = [
            'start_date' => $request->get('start_date', $defaultStartDate),
            'end_date' => $request->get('end_date', $defaultEndDate),
            'action' => $request->get('action', '')
        ];
        
        // Build query
        $query = KeyLog::with(['key.location', 'receiver'])
            ->orderBy('created_at', 'desc');
        
        // Apply date filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
        
        // Apply action filter
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        
        // Get paginated results
        $logs = $query->paginate(25);
        
        return view('reports.key-activity', compact('logs', 'filters'));
    }

    public function currentHolders(Request $request)
    {
        $query = KeyLog::where('action', 'checkout')
            ->whereNull('returned_from_log_id')
            ->with(['key.location', 'receiver']);

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('holder_name', 'like', '%' . $request->search . '%')
                  ->orWhereHas('key', function($keyQuery) use ($request) {
                      $keyQuery->where('label', 'like', '%' . $request->search . '%')
                              ->orWhere('code', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('key.location', function($locationQuery) use ($request) {
                      $locationQuery->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Holder type filter
        if ($request->has('holder_type') && $request->holder_type) {
            $query->where('holder_type', $request->holder_type);
        }

        // Location filter
        if ($request->has('location_id') && $request->location_id) {
            $query->whereHas('key', function($keyQuery) use ($request) {
                $keyQuery->where('location_id', $request->location_id);
            });
        }

        $currentHolders = $query->latest()->paginate(20);
        
        // Get locations for the filter dropdown
        $locations = Location::active()->get();
        
        // Count overdue keys
        $overdueCount = KeyLog::where('action', 'checkout')
            ->whereNull('returned_from_log_id')
            ->whereNotNull('expected_return_at')
            ->where('expected_return_at', '<', now())
            ->count();

        return view('reports.current-holders', compact('currentHolders', 'locations', 'overdueCount'));
    }

    public function overdueKeys()
    {
        $overdueKeys = KeyLog::where('action', 'checkout')
            ->whereNull('returned_from_log_id')
            ->whereNotNull('expected_return_at')
            ->where('expected_return_at', '<', now())
            ->with(['key.location', 'holder', 'receiver'])
            ->latest()
            ->paginate(50);

        return view('reports.overdue-keys', compact('overdueKeys'));
    }

    public function staffActivity(Request $request)
    {
        // Set default date range (last 30 days)
        $defaultStartDate = now()->subDays(30)->format('Y-m-d');
        $defaultEndDate = now()->format('Y-m-d');
        
        // Get filters from request or use defaults
        $filters = [
            'start_date' => $request->get('start_date', $defaultStartDate),
            'end_date' => $request->get('end_date', $defaultEndDate),
            'staff_type' => $request->get('staff_type', '')
        ];
        
        // Build query for staff activity (only checkouts)
        $query = KeyLog::where('action', 'checkout')
            ->whereDate('created_at', '>=', $filters['start_date'])
            ->whereDate('created_at', '<=', $filters['end_date']);

        // Apply staff type filter
        if (!empty($filters['staff_type'])) {
            $query->where('holder_type', $filters['staff_type']);
        }

        // Get staff activity with aggregated data
        $staffActivity = $query->select(
                'holder_type',
                'holder_id',
                'holder_name',
                'holder_phone',
                DB::raw('COUNT(*) as total_checkouts'),
                DB::raw('AVG(
                    CASE WHEN returned_from_log_id IS NOT NULL THEN 
                        TIMESTAMPDIFF(MINUTE, created_at, 
                            (SELECT created_at FROM key_logs AS k2 
                             WHERE k2.id = key_logs.returned_from_log_id)
                        )
                    ELSE NULL
                    END
                ) as avg_duration_minutes')
            )
            ->groupBy('holder_type', 'holder_id', 'holder_name', 'holder_phone')
            ->orderBy('total_checkouts', 'desc')
            ->paginate(25);

        return view('reports.staff-activity', compact('staffActivity', 'filters'));
    }

    public function securityPerformance(Request $request)
    {
        // Set default date range (last 30 days)
        $defaultStartDate = now()->subDays(30)->format('Y-m-d');
        $defaultEndDate = now()->format('Y-m-d');
        
        // Get filters from request or use defaults
        $filters = [
            'start_date' => $request->get('start_date', $defaultStartDate),
            'end_date' => $request->get('end_date', $defaultEndDate)
        ];
        
        // Build query for security performance
        $query = User::role('security');

        // Apply date filters to the relationship counts
        $performance = $query->withCount(['keyLogsAsReceiver as total_transactions' => function($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['start_date'])
                      ->whereDate('created_at', '<=', $filters['end_date']);
            }])
            ->withCount(['keyLogsAsReceiver as checkout_count' => function($query) use ($filters) {
                $query->where('action', 'checkout')
                      ->whereDate('created_at', '>=', $filters['start_date'])
                      ->whereDate('created_at', '<=', $filters['end_date']);
            }])
            ->withCount(['keyLogsAsReceiver as checkin_count' => function($query) use ($filters) {
                $query->where('action', 'checkin')
                      ->whereDate('created_at', '>=', $filters['start_date'])
                      ->whereDate('created_at', '<=', $filters['end_date']);
            }])
            ->orderBy('total_transactions', 'desc')
            ->paginate(20);

        return view('reports.security-performance', compact('performance', 'filters'));
    }

    public function analyticsDashboard()
    {
        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');

        // Basic stats
        $stats = [
            'today_checkouts' => KeyLog::whereDate('created_at', $today)
                ->where('action', 'checkout')
                ->count(),
            'week_checkouts' => KeyLog::whereDate('created_at', '>=', $weekAgo)
                ->where('action', 'checkout')
                ->count(),
            'avg_checkout_duration' => KeyLog::where('action', 'checkin')
                ->whereDate('created_at', '>=', $weekAgo)
                ->average(DB::raw('TIMESTAMPDIFF(MINUTE, 
                    (SELECT created_at FROM key_logs AS k2 WHERE k2.id = key_logs.returned_from_log_id),
                    key_logs.created_at)')),
            'busiest_location' => Location::withCount(['keyLogs as recent_checkouts' => function($query) use ($weekAgo) {
                $query->where('action', 'checkout')
                      ->whereDate('key_logs.created_at', '>=', $weekAgo); // Fixed: specify key_logs.created_at
            }])->orderBy('recent_checkouts', 'desc')
               ->first(),
        ];

        // Hourly activity for today
        $hourlyActivity = KeyLog::whereDate('created_at', $today)
            ->where('action', 'checkout')
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        // Top keys this week
        $topKeys = Key::withCount(['keyLogs as recent_checkouts' => function($query) use ($weekAgo) {
                $query->where('action', 'checkout')
                      ->whereDate('key_logs.created_at', '>=', $weekAgo); // Fixed: specify key_logs.created_at
            }])
            ->orderBy('recent_checkouts', 'desc')
            ->limit(10)
            ->get();

        return view('reports.analytics', compact('stats', 'hourlyActivity', 'topKeys'));
    }

    public function exportKeyActivity(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $logs = KeyLog::with(['key.location', 'receiver', 'holder'])
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->latest()
            ->get();

        if ($filters['format'] === 'csv') {
            return $this->exportToCsv($logs);
        } elseif ($filters['format'] === 'excel') {
            return $this->exportToExcel($logs);
        } else {
            return $this->exportToPdf($logs, $filters);
        }
    }

    private function exportToCsv($logs)
    {
        $fileName = 'key-activity-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Date', 'Time', 'Action', 'Key Code', 'Key Label', 'Location',
                'Holder Name', 'Holder Phone', 'Holder Type', 'Security Officer',
                'Expected Return', 'Verified', 'Discrepancy'
            ]);

            // Data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d'),
                    $log->created_at->format('H:i:s'),
                    $log->action,
                    $log->key->code ?? 'N/A',
                    $log->key->label ?? 'N/A',
                    $log->key->location->name ?? 'N/A',
                    $log->holder_name,
                    $log->holder_phone,
                    $log->holder_type,
                    $log->receiver_name,
                    $log->expected_return_at ? $log->expected_return_at->format('Y-m-d H:i') : 'N/A',
                    $log->verified ? 'Yes' : 'No',
                    $log->discrepancy ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($logs)
    {
        // Implementation for Excel export
        // This would use Maatwebsite/Excel package
        return response()->json(['message' => 'Excel export to be implemented']);
    }

    private function exportToPdf($logs, $filters)
    {
        // Implementation for PDF export
        // This would use DomPDF or similar
        return response()->json(['message' => 'PDF export to be implemented']);
    }

    // ADDITIONAL HELPER METHODS

    /**
     * Get key usage statistics by location
     */
    public function locationUsage(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $usageStats = Location::withCount(['keyLogs as total_checkouts' => function($query) use ($filters) {
                $query->where('action', 'checkout')
                      ->whereBetween('key_logs.created_at', [$filters['start_date'], $filters['end_date']]); // Fixed
            }])
            ->withCount(['keyLogs as total_checkins' => function($query) use ($filters) {
                $query->where('action', 'checkin')
                      ->whereBetween('key_logs.created_at', [$filters['start_date'], $filters['end_date']]); // Fixed
            }])
            ->withCount(['keys as total_keys'])
            ->having('total_checkouts', '>', 0)
            ->orderBy('total_checkouts', 'desc')
            ->paginate(20);

        return view('reports.location-usage', compact('usageStats', 'filters'));
    }

    /**
     * Get key holder statistics
     */
    public function holderStatistics(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'holder_type' => 'nullable|in:student,staff,visitor,contractor',
        ]);

        $query = KeyLog::where('action', 'checkout')
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);

        if (!empty($filters['holder_type'])) {
            $query->where('holder_type', $filters['holder_type']);
        }

        $holderStats = $query->select(
                'holder_type',
                DB::raw('COUNT(*) as total_checkouts'),
                DB::raw('COUNT(DISTINCT holder_id) as unique_holders'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, 
                    (SELECT created_at FROM key_logs AS k2 
                     WHERE k2.returned_from_log_id = key_logs.id)
                )) as avg_holding_hours')
            )
            ->groupBy('holder_type')
            ->orderBy('total_checkouts', 'desc')
            ->get();

        return view('reports.holder-statistics', compact('holderStats', 'filters'));
    }

    /**
     * Get system health and performance metrics
     */
    public function systemMetrics()
    {
        $metrics = [
            'total_keys' => Key::count(),
            'total_locations' => Location::count(),
            'total_users' => User::count(),
            'active_checkouts' => KeyLog::where('action', 'checkout')
                ->whereNull('returned_from_log_id')
                ->count(),
            'pending_discrepancies' => KeyLog::where('discrepancy', true)
                ->where('verified', false)
                ->count(),
            'today_transactions' => KeyLog::whereDate('created_at', today())->count(),
            'week_transactions' => KeyLog::whereDate('created_at', '>=', now()->subDays(7))->count(),
            'month_transactions' => KeyLog::whereDate('created_at', '>=', now()->subDays(30))->count(),
        ];

        return view('reports.system-metrics', compact('metrics'));
    }

    // NEW METHODS ADDED

    /**
     * Get key audit trail with detailed changes
     */
    public function keyAuditTrail(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'key_id' => 'nullable|exists:keys,id',
            'action' => 'nullable|in:checkout,checkin,transfer,update',
        ]);

        $query = KeyLog::with(['key.location', 'receiver', 'holder'])
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);

        if (!empty($filters['key_id'])) {
            $query->where('key_id', $filters['key_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $auditTrail = $query->orderBy('created_at', 'desc')
            ->paginate(50);

        $keys = Key::all();

        return view('reports.audit-trail', compact('auditTrail', 'filters', 'keys'));
    }

    /**
     * Get peak usage hours report
     */
    public function peakUsage(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $query = KeyLog::where('action', 'checkout')
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);

        if (!empty($filters['location_id'])) {
            $query->whereHas('key', function ($q) use ($filters) {
                $q->where('location_id', $filters['location_id']);
            });
        }

        $peakUsage = $query->select(
                DB::raw('DAYNAME(created_at) as day_name'),
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('day_name', 'day_of_week', 'hour')
            ->orderBy('day_of_week')
            ->orderBy('hour')
            ->get();

        $locations = Location::all();

        return view('reports.peak-usage', compact('peakUsage', 'filters', 'locations'));
    }

    /**
     * Get key utilization report
     */
    public function keyUtilization(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $utilization = Key::withCount(['keyLogs as checkouts_count' => function($query) use ($filters) {
                $query->where('action', 'checkout')
                      ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            }])
            ->with(['location'])
            ->orderBy('checkouts_count', 'desc')
            ->paginate(20);

        return view('reports.key-utilization', compact('utilization', 'filters'));
    }

    /**
     * Get discrepancy report
     */
    public function discrepancyReport(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'resolved' => 'nullable|boolean',
        ]);

        $query = KeyLog::where('discrepancy', true)
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->with(['key.location', 'receiver', 'holder']);

        if (isset($filters['resolved'])) {
            $query->where('verified', $filters['resolved']);
        }

        $discrepancies = $query->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('reports.discrepancies', compact('discrepancies', 'filters'));
    }

    /**
     * Get key return timeliness report
     */
    public function returnTimeliness(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $timeliness = KeyLog::where('action', 'checkin')
            ->whereNotNull('returned_from_log_id')
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->with(['key.location', 'holder'])
            ->select('*',
                DB::raw('TIMESTAMPDIFF(HOUR, 
                    (SELECT created_at FROM key_logs AS k2 WHERE k2.id = key_logs.returned_from_log_id),
                    key_logs.created_at) as actual_duration_hours'),
                DB::raw('TIMESTAMPDIFF(HOUR, 
                    (SELECT created_at FROM key_logs AS k2 WHERE k2.id = key_logs.returned_from_log_id),
                    (SELECT expected_return_at FROM key_logs AS k3 WHERE k3.id = key_logs.returned_from_log_id)
                ) as expected_duration_hours')
            )
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('reports.return-timeliness', compact('timeliness', 'filters'));
    }

    /**
     * Export multiple reports in bulk
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'reports' => 'required|array',
            'reports.*' => 'in:key_activity,current_holders,overdue_keys,staff_activity,security_performance',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,excel',
        ]);

        $exports = [];

        foreach ($request->reports as $report) {
            switch ($report) {
                case 'key_activity':
                    $exports['key_activity'] = KeyLog::with(['key.location', 'receiver', 'holder'])
                        ->whereBetween('created_at', [$request->start_date, $request->end_date])
                        ->latest()
                        ->get();
                    break;

                case 'current_holders':
                    $exports['current_holders'] = KeyLog::where('action', 'checkout')
                        ->whereNull('returned_from_log_id')
                        ->with(['key.location', 'receiver'])
                        ->latest()
                        ->get();
                    break;

                case 'overdue_keys':
                    $exports['overdue_keys'] = KeyLog::where('action', 'checkout')
                        ->whereNull('returned_from_log_id')
                        ->whereNotNull('expected_return_at')
                        ->where('expected_return_at', '<', now())
                        ->with(['key.location', 'holder', 'receiver'])
                        ->latest()
                        ->get();
                    break;

                case 'staff_activity':
                    $exports['staff_activity'] = KeyLog::with(['key.location', 'receiver'])
                        ->whereBetween('created_at', [$request->start_date, $request->end_date])
                        ->where('action', 'checkout')
                        ->select(
                            'holder_type',
                            'holder_id',
                            'holder_name',
                            'holder_phone',
                            DB::raw('COUNT(*) as total_checkouts')
                        )
                        ->groupBy('holder_type', 'holder_id', 'holder_name', 'holder_phone')
                        ->orderBy('total_checkouts', 'desc')
                        ->get();
                    break;

                case 'security_performance':
                    $exports['security_performance'] = User::role('security')
                        ->withCount(['keyLogsAsReceiver as total_transactions' => function($query) use ($request) {
                            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
                        }])
                        ->withCount(['keyLogsAsReceiver as checkout_count' => function($query) use ($request) {
                            $query->where('action', 'checkout')
                                  ->whereBetween('created_at', [$request->start_date, $request->end_date]);
                        }])
                        ->withCount(['keyLogsAsReceiver as checkin_count' => function($query) use ($request) {
                            $query->where('action', 'checkin')
                                  ->whereBetween('created_at', [$request->start_date, $request->end_date]);
                        }])
                        ->having('total_transactions', '>', 0)
                        ->orderBy('total_transactions', 'desc')
                        ->get();
                    break;
            }
        }

        if ($request->format === 'csv') {
            return $this->exportBulkToCsv($exports, $request->start_date, $request->end_date);
        }

        return response()->json(['message' => 'Bulk export completed']);
    }

    private function exportBulkToCsv($exports, $startDate, $endDate)
    {
        $fileName = 'bulk-export-' . now()->format('Y-m-d') . '.zip';
        // Implementation for bulk CSV export would go here
        // This would create multiple CSV files and zip them

        return response()->json(['message' => 'Bulk CSV export to be implemented']);
    }
}