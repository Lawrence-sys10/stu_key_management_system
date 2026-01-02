@extends('layouts.app')

@section('title', 'Staff Activity Report')

@section('subtitle', 'Key usage and activity by staff members')

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Filters -->
    <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('reports.staff-activity') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}" 
                       class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>
            <div>
                <label for="staff_type" class="block text-sm font-medium text-gray-700">Staff Type</label>
                <select name="staff_type" id="staff_type" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Staff Types</option>
                    <option value="hr" {{ ($filters['staff_type'] ?? '') == 'hr' ? 'selected' : '' }}>HR Staff</option>
                    <option value="perm_manual" {{ ($filters['staff_type'] ?? '') == 'perm_manual' ? 'selected' : '' }}>Permanent Manual</option>
                    <option value="temp" {{ ($filters['staff_type'] ?? '') == 'temp' ? 'selected' : '' }}>Temporary Staff</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="{{ route('reports.staff-activity') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-refresh mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="px-4 py-4 border-b border-gray-200 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $staffActivity->total() }}</div>
                <div class="text-sm text-gray-500">Total Staff</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $staffActivity->sum('total_checkouts') }}</div>
                <div class="text-sm text-gray-500">Total Checkouts</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                    {{ $staffActivity->avg('avg_duration_minutes') ? round($staffActivity->avg('avg_duration_minutes') / 60, 1) : 0 }}
                </div>
                <div class="text-sm text-gray-500">Avg Hours per Checkout</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">
                    {{ $staffActivity->count() > 0 ? round($staffActivity->sum('total_checkouts') / $staffActivity->count(), 1) : 0 }}
                </div>
                <div class="text-sm text-gray-500">Avg Checkouts per Staff</div>
            </div>
        </div>
    </div>

    <!-- Staff Activity Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Staff Member
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Staff Type
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contact
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total Checkouts
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Avg Duration
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Activity Level
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($staffActivity as $staff)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $staff->holder_name ?? 'Unknown Staff' }}</div>
                            <div class="text-sm text-gray-500">ID: {{ $staff->holder_id ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                @if($staff->holder_type == 'hr') bg-purple-100 text-purple-800
                                @elseif($staff->holder_type == 'perm_manual') bg-blue-100 text-blue-800
                                @elseif($staff->holder_type == 'temp') bg-orange-100 text-orange-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $staff->holder_type_label ?? ucfirst(str_replace('_', ' ', $staff->holder_type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $staff->holder_phone ?? 'No phone' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $staff->total_checkouts }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $staff->total_checkouts > 0 ? round(($staff->total_checkouts / $staffActivity->sum('total_checkouts')) * 100, 1) : 0 }}% of total
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if($staff->avg_duration_minutes)
                                    {{ round($staff->avg_duration_minutes / 60, 1) }} hours
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">
                                @if($staff->avg_duration_minutes)
                                    {{ round($staff->avg_duration_minutes, 0) }} minutes
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $activityLevel = 'Low';
                                $activityColor = 'gray';
                                if ($staff->total_checkouts >= 20) {
                                    $activityLevel = 'Very High';
                                    $activityColor = 'red';
                                } elseif ($staff->total_checkouts >= 15) {
                                    $activityLevel = 'High';
                                    $activityColor = 'orange';
                                } elseif ($staff->total_checkouts >= 10) {
                                    $activityLevel = 'Medium';
                                    $activityColor = 'yellow';
                                } elseif ($staff->total_checkouts >= 5) {
                                    $activityLevel = 'Low';
                                    $activityColor = 'green';
                                } else {
                                    $activityLevel = 'Very Low';
                                    $activityColor = 'gray';
                                }
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                bg-{{ $activityColor }}-100 text-{{ $activityColor }}-800">
                                <i class="fas 
                                    @if($activityLevel == 'Very High') fa-fire 
                                    @elseif($activityLevel == 'High') fa-chart-line 
                                    @elseif($activityLevel == 'Medium') fa-chart-bar 
                                    @elseif($activityLevel == 'Low') fa-chart-area 
                                    @else fa-minus @endif mr-1"></i>
                                {{ $activityLevel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                <p class="text-sm text-gray-500 font-medium">No staff activity found for the selected filters.</p>
                                <p class="text-xs text-gray-400 mt-1">Try adjusting your date range or filters</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($staffActivity->hasPages())
        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
            {{ $staffActivity->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Activity Distribution Chart -->
@if($staffActivity->count() > 0)
<div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Checkouts by Staff Type -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Checkouts by Staff Type
            </h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-4">
                @php
                    $typeStats = [];
                    foreach ($staffActivity as $staff) {
                        $type = $staff->holder_type ?? 'unknown';
                        if (!isset($typeStats[$type])) {
                            $typeStats[$type] = 0;
                        }
                        $typeStats[$type] += $staff->total_checkouts;
                    }
                    $totalByType = array_sum($typeStats);
                @endphp
                
                @foreach($typeStats as $type => $count)
                    @php
                        $percentage = $totalByType > 0 ? round(($count / $totalByType) * 100, 1) : 0;
                        $color = match($type) {
                            'hr' => 'purple',
                            'perm_manual' => 'blue',
                            'temp' => 'orange',
                            default => 'gray'
                        };
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span class="capitalize">{{ str_replace('_', ' ', $type) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-{{ $color }}-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Top 5 Active Staff
            </h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-4">
                @foreach($staffActivity->take(5) as $index => $staff)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-blue-800">{{ $index + 1 }}</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ $staff->holder_name ?? 'Unknown Staff' }}</p>
                                <p class="text-xs text-gray-500">{{ $staff->total_checkouts }} checkouts</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-900">
                                @if($staff->avg_duration_minutes)
                                    {{ round($staff->avg_duration_minutes / 60, 1) }}h avg
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<!-- JavaScript for form validation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    // Set default dates if not set
    if (!startDate.value) {
        const oneWeekAgo = new Date();
        oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
        startDate.value = oneWeekAgo.toISOString().split('T')[0];
    }
    
    if (!endDate.value) {
        const today = new Date();
        endDate.value = today.toISOString().split('T')[0];
    }
    
    // Form validation
    form.addEventListener('submit', function(e) {
        if (!startDate.value || !endDate.value) {
            e.preventDefault();
            alert('Please select both start and end dates.');
            return false;
        }
        
        if (new Date(startDate.value) > new Date(endDate.value)) {
            e.preventDefault();
            alert('Start date cannot be after end date.');
            return false;
        }
    });
    
    // Set max date for end date based on start date
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
    });
    
    // Set min date for start date based on end date
    endDate.addEventListener('change', function() {
        startDate.max = this.value;
    });
    
    // Initialize date constraints
    if (startDate.value) {
        endDate.min = startDate.value;
    }
    if (endDate.value) {
        startDate.max = endDate.value;
    }
});
</script>
@endsection