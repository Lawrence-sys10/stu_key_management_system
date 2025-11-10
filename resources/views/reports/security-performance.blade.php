@extends('layouts.app')

@section('title', 'Security Performance Report')

@section('subtitle', 'Security officer activity and performance metrics')

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Filters -->
    <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('reports.security-performance') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <div class="flex items-end space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="{{ route('reports.security-performance') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-refresh mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="px-4 py-4 border-b border-gray-200 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $performance->total() }}</div>
                <div class="text-sm text-gray-500">Active Officers</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $performance->sum('total_transactions') }}</div>
                <div class="text-sm text-gray-500">Total Transactions</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $performance->sum('checkout_count') }}</div>
                <div class="text-sm text-gray-500">Total Checkouts</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ $performance->sum('checkin_count') }}</div>
                <div class="text-sm text-gray-500">Total Checkins</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">
                    {{ $performance->count() > 0 ? round($performance->sum('total_transactions') / $performance->count(), 1) : 0 }}
                </div>
                <div class="text-sm text-gray-500">Avg per Officer</div>
            </div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Security Officer
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contact
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total Transactions
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Checkouts
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Checkins
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Checkout/Checkin Ratio
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Performance Level
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($performance as $officer)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-orange-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-shield-alt text-orange-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $officer->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $officer->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $officer->phone ?? 'No phone' }}</div>
                            <div class="text-xs text-gray-500">
                                @if($officer->last_login_at)
                                    Last login: {{ $officer->last_login_at->diffForHumans() }}
                                @else
                                    Never logged in
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $officer->total_transactions }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $performance->sum('total_transactions') > 0 ? round(($officer->total_transactions / $performance->sum('total_transactions')) * 100, 1) : 0 }}% of total
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $officer->checkout_count }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $officer->total_transactions > 0 ? round(($officer->checkout_count / $officer->total_transactions) * 100, 1) : 0 }}% of transactions
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $officer->checkin_count }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $officer->total_transactions > 0 ? round(($officer->checkin_count / $officer->total_transactions) * 100, 1) : 0 }}% of transactions
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $ratio = $officer->checkin_count > 0 ? $officer->checkout_count / $officer->checkin_count : $officer->checkout_count;
                                $ratioColor = 'green';
                                $ratioText = 'Balanced';
                                
                                if ($ratio > 1.5) {
                                    $ratioColor = 'orange';
                                    $ratioText = 'More Checkouts';
                                } elseif ($ratio < 0.7) {
                                    $ratioColor = 'blue';
                                    $ratioText = 'More Checkins';
                                } elseif ($ratio == 0) {
                                    $ratioColor = 'gray';
                                    $ratioText = 'No Activity';
                                }
                            @endphp
                            <div class="text-sm font-medium text-{{ $ratioColor }}-600">{{ number_format($ratio, 2) }}:1</div>
                            <div class="text-xs text-{{ $ratioColor }}-500">{{ $ratioText }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $performanceLevel = 'Low';
                                $performanceColor = 'gray';
                                
                                if ($officer->total_transactions >= 100) {
                                    $performanceLevel = 'Excellent';
                                    $performanceColor = 'green';
                                } elseif ($officer->total_transactions >= 50) {
                                    $performanceLevel = 'Good';
                                    $performanceColor = 'blue';
                                } elseif ($officer->total_transactions >= 20) {
                                    $performanceLevel = 'Average';
                                    $performanceColor = 'yellow';
                                } elseif ($officer->total_transactions > 0) {
                                    $performanceLevel = 'Low';
                                    $performanceColor = 'orange';
                                } else {
                                    $performanceLevel = 'No Activity';
                                    $performanceColor = 'gray';
                                }
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $performanceColor }}-100 text-{{ $performanceColor }}-800">
                                <i class="fas 
                                    @if($performanceLevel == 'Excellent') fa-trophy 
                                    @elseif($performanceLevel == 'Good') fa-star 
                                    @elseif($performanceLevel == 'Average') fa-chart-line 
                                    @elseif($performanceLevel == 'Low') fa-chart-bar 
                                    @else fa-minus @endif mr-1"></i>
                                {{ $performanceLevel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-shield-alt text-4xl text-gray-300 mb-3"></i>
                                <p class="text-sm text-gray-500 font-medium">No security performance data found for the selected filters.</p>
                                <p class="text-xs text-gray-400 mt-1">Try adjusting your date range</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($performance->hasPages())
        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
            {{ $performance->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Performance Analytics -->
@if($performance->count() > 0)
<div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Performance Distribution -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Performance Distribution
            </h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-4">
                @php
                    $performanceStats = [
                        'Excellent' => 0,
                        'Good' => 0,
                        'Average' => 0,
                        'Low' => 0,
                        'No Activity' => 0
                    ];
                    
                    foreach ($performance as $officer) {
                        if ($officer->total_transactions >= 100) {
                            $performanceStats['Excellent']++;
                        } elseif ($officer->total_transactions >= 50) {
                            $performanceStats['Good']++;
                        } elseif ($officer->total_transactions >= 20) {
                            $performanceStats['Average']++;
                        } elseif ($officer->total_transactions > 0) {
                            $performanceStats['Low']++;
                        } else {
                            $performanceStats['No Activity']++;
                        }
                    }
                    
                    $totalOfficers = $performance->count();
                @endphp
                
                @foreach($performanceStats as $level => $count)
                    @if($count > 0)
                        @php
                            $percentage = $totalOfficers > 0 ? round(($count / $totalOfficers) * 100, 1) : 0;
                            $color = match($level) {
                                'Excellent' => 'green',
                                'Good' => 'blue',
                                'Average' => 'yellow',
                                'Low' => 'orange',
                                default => 'gray'
                            };
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span class="flex items-center">
                                    <i class="fas 
                                        @if($level == 'Excellent') fa-trophy 
                                        @elseif($level == 'Good') fa-star 
                                        @elseif($level == 'Average') fa-chart-line 
                                        @elseif($level == 'Low') fa-chart-bar 
                                        @else fa-minus @endif mr-2 text-{{ $color }}-500"></i>
                                    {{ $level }}
                                </span>
                                <span>{{ $count }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-{{ $color }}-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Top 5 Performing Officers
            </h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-4">
                @foreach($performance->take(5) as $index => $officer)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-orange-800">{{ $index + 1 }}</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ $officer->name }}</p>
                                <p class="text-xs text-gray-500">{{ $officer->total_transactions }} transactions</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-900">
                                {{ $officer->checkout_count }} checkouts
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $officer->checkin_count }} checkins
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Transaction Trends -->
<div class="mt-8 bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Transaction Summary
        </h3>
    </div>
    <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-lg font-semibold text-gray-900 mb-2">Most Active Officer</div>
                @php $mostActive = $performance->first(); @endphp
                @if($mostActive)
                    <div class="text-2xl font-bold text-blue-600">{{ $mostActive->name }}</div>
                    <div class="text-sm text-gray-500">{{ $mostActive->total_transactions }} transactions</div>
                @else
                    <div class="text-lg text-gray-500">No data</div>
                @endif
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold text-gray-900 mb-2">Highest Checkout Rate</div>
                @php
                    $highestCheckout = $performance->sortByDesc('checkout_count')->first();
                @endphp
                @if($highestCheckout)
                    <div class="text-2xl font-bold text-green-600">{{ $highestCheckout->name }}</div>
                    <div class="text-sm text-gray-500">{{ $highestCheckout->checkout_count }} checkouts</div>
                @else
                    <div class="text-lg text-gray-500">No data</div>
                @endif
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold text-gray-900 mb-2">Most Balanced</div>
                @php
                    $mostBalanced = $performance->filter(function($officer) {
                        if ($officer->checkin_count == 0) return false;
                        $ratio = $officer->checkout_count / $officer->checkin_count;
                        return $ratio >= 0.8 && $ratio <= 1.2;
                    })->sortByDesc('total_transactions')->first();
                @endphp
                @if($mostBalanced)
                    <div class="text-2xl font-bold text-purple-600">{{ $mostBalanced->name }}</div>
                    <div class="text-sm text-gray-500">{{ number_format($mostBalanced->checkout_count / $mostBalanced->checkin_count, 2) }} ratio</div>
                @else
                    <div class="text-lg text-gray-500">No data</div>
                @endif
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
        const oneMonthAgo = new Date();
        oneMonthAgo.setDate(oneMonthAgo.getDate() - 30);
        startDate.value = oneMonthAgo.toISOString().split('T')[0];
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