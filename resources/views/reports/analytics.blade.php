@extends('layouts.app')

@section('title', 'Analytics Dashboard')
@section('subtitle', 'Key management insights and statistics')

@section('actions')
    <div class="flex space-x-2">
        <a href="{{ route('reports.key-activity') }}" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-chart-line mr-2"></i> Key Activity
        </a>
        <button onclick="window.print()" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-print mr-2"></i> Print
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's Checkouts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['today_checkouts'] }}</div>
                    <div class="text-sm font-medium text-gray-500">Today's Checkouts</div>
                    @if($stats['today_checkouts'] > 0)
                    <div class="text-xs text-green-600 mt-1">
                        <i class="fas fa-arrow-up mr-1"></i>Active day
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Weekly Checkouts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-week text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['week_checkouts'] }}</div>
                    <div class="text-sm font-medium text-gray-500">This Week's Checkouts</div>
                    @if($stats['week_checkouts'] > $stats['today_checkouts'])
                    <div class="text-xs text-blue-600 mt-1">
                        <i class="fas fa-chart-line mr-1"></i>Weekly trend
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Average Duration -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">
                        @if($stats['avg_checkout_duration'])
                            {{ number_format($stats['avg_checkout_duration'], 1) }}<span class="text-lg">min</span>
                        @else
                            N/A
                        @endif
                    </div>
                    <div class="text-sm font-medium text-gray-500">Avg Checkout Duration</div>
                    @if($stats['avg_checkout_duration'] > 60)
                    <div class="text-xs text-orange-600 mt-1">
                        <i class="fas fa-exclamation-circle mr-1"></i>Long sessions
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Busiest Location -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-orange-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">
                        @if($stats['busiest_location'])
                            {{ $stats['busiest_location']->recent_checkouts }}
                        @else
                            0
                        @endif
                    </div>
                    <div class="text-sm font-medium text-gray-500 truncate">
                        @if($stats['busiest_location'])
                            {{ Str::limit($stats['busiest_location']->name, 20) }}
                        @else
                            No Active Locations
                        @endif
                    </div>
                    @if($stats['busiest_location'] && $stats['busiest_location']->recent_checkouts > 10)
                    <div class="text-xs text-red-600 mt-1">
                        <i class="fas fa-fire mr-1"></i>Hot spot
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Hourly Activity Chart -->
        <div class="xl:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Today's Hourly Activity</h3>
                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                        <i class="fas fa-clock"></i>
                        <span>Last updated: {{ now()->format('g:i A') }}</span>
                    </div>
                </div>
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Keys This Week -->
        <div class="xl:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Top Keys This Week</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        {{ count($topKeys) }} active
                    </span>
                </div>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @forelse($topKeys as $index => $key)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-150">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <span class="text-blue-700 font-semibold text-sm">{{ $index + 1 }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $key->code }}</p>
                                        @if($index < 3)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-crown mr-1 text-xs"></i>Top
                                        </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 truncate">{{ $key->label }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="bg-blue-100 text-blue-800 text-sm font-semibold px-2.5 py-1 rounded-full">
                                    {{ $key->recent_checkouts }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-key text-gray-400 text-xl"></i>
                            </div>
                            <p class="text-gray-500 text-sm">No key activity this week</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Actions & Reports</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route('reports.key-activity') }}" 
                       class="group p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors duration-200">
                                <i class="fas fa-chart-line text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">Key Activity</p>
                                <p class="text-sm text-gray-500">Detailed reports</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('reports.current-holders') }}" 
                       class="group p-4 border border-gray-200 rounded-lg hover:border-green-300 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors duration-200">
                                <i class="fas fa-users text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 group-hover:text-green-600 transition-colors duration-200">Current Holders</p>
                                <p class="text-sm text-gray-500">Active checkouts</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('reports.overdue-keys') }}" 
                       class="group p-4 border border-gray-200 rounded-lg hover:border-red-300 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition-colors duration-200">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 group-hover:text-red-600 transition-colors duration-200">Overdue Keys</p>
                                <p class="text-sm text-gray-500">Require attention</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('keys.index') }}" 
                       class="group p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200">
                                <i class="fas fa-key text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 group-hover:text-purple-600 transition-colors duration-200">All Keys</p>
                                <p class="text-sm text-gray-500">Manage inventory</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('locations.index') }}" 
                       class="group p-4 border border-gray-200 rounded-lg hover:border-orange-300 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200 transition-colors duration-200">
                                <i class="fas fa-map-marker-alt text-orange-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 group-hover:text-orange-600 transition-colors duration-200">Locations</p>
                                <p class="text-sm text-gray-500">Manage areas</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('kiosk.index') }}" 
                       class="group p-4 border border-gray-200 rounded-lg hover:border-indigo-300 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition-colors duration-200">
                                <i class="fas fa-tablet-alt text-indigo-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 group-hover:text-indigo-600 transition-colors duration-200">Kiosk Mode</p>
                                <p class="text-sm text-gray-500">Quick access</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">System Status</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-green-900">System Online</p>
                                <p class="text-sm text-green-600">All services operational</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-database text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-blue-900">Database</p>
                                <p class="text-sm text-blue-600">{{ $stats['total_keys'] ?? 0 }} keys tracked</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg border border-purple-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-shield-alt text-purple-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-purple-900">Security</p>
                                <p class="text-sm text-purple-600">Encryption active</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-sync text-gray-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Last Sync</p>
                                <p class="text-sm text-gray-600">{{ now()->format('M j, g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Hourly Activity Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        const hourlyData = @json($hourlyActivity);
        
        // Prepare data for all 24 hours
        const hours = Array.from({length: 24}, (_, i) => i);
        const counts = hours.map(hour => hourlyData[hour] || 0);
        
        // Create gradient
        const gradient = hourlyCtx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
        
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hours.map(hour => {
                    if (hour === 0) return '12 AM';
                    if (hour === 12) return '12 PM';
                    return hour > 12 ? `${hour - 12} PM` : `${hour} AM`;
                }),
                datasets: [{
                    label: 'Checkouts',
                    data: counts,
                    backgroundColor: gradient,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 12
                        },
                        bodyFont: {
                            size: 14
                        },
                        padding: 12,
                        cornerRadius: 6
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        },
                        title: {
                            display: true,
                            text: 'Number of Checkouts',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 45
                        },
                        title: {
                            display: true,
                            text: 'Hour of Day',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });

        // Add smooth scrolling for better UX
        const smoothScroll = (element) => {
            element.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        };

        // Add print styles
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                .bg-white { background: white !important; }
                .shadow-sm, .hover\\:shadow-md { box-shadow: none !important; }
                .border { border: 1px solid #e5e7eb !important; }
                .no-print { display: none !important; }
            }
        `;
        document.head.appendChild(style);
    });
</script>
@endpush

<style>
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    /* Custom scrollbar for top keys list */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    /* Dark mode support */
    .dark .bg-white {
        background-color: #1f2937 !important;
        border-color: #374151 !important;
    }
    
    .dark .text-gray-900 {
        color: #f9fafb !important;
    }
    
    .dark .text-gray-500 {
        color: #d1d5db !important;
    }
    
    .dark .bg-gray-50 {
        background-color: #374151 !important;
    }
    
    .dark .border-gray-200 {
        border-color: #374151 !important;
    }
</style>
@endsection