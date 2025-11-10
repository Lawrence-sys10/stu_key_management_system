@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('subtitle', 'System reports and performance analytics')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Key Activity Report -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-history text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Key Activity</h3>
                    <p class="text-sm text-gray-500">Detailed key checkout/checkin history</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('reports.key-activity') }}" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-chart-line mr-2"></i> View Report
                </a>
            </div>
        </div>
    </div>

    <!-- Current Holders Report -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Current Holders</h3>
                    <p class="text-sm text-gray-500">Currently checked out keys and holders</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('reports.current-holders') }}" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-list mr-2"></i> View Report
                </a>
            </div>
        </div>
    </div>

    <!-- Overdue Keys Report -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Overdue Keys</h3>
                    <p class="text-sm text-gray-500">Keys past their expected return date</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('reports.overdue-keys') }}" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    <i class="fas fa-clock mr-2"></i> View Report
                </a>
            </div>
        </div>
    </div>

    <!-- Staff Activity Report -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-chart text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Staff Activity</h3>
                    <p class="text-sm text-gray-500">Key usage by staff members</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('reports.staff-activity') }}" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                    <i class="fas fa-chart-bar mr-2"></i> View Report
                </a>
            </div>
        </div>
    </div>

    <!-- Security Performance Report -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-shield-alt text-2xl text-orange-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Security Performance</h3>
                    <p class="text-sm text-gray-500">Security officer activity and metrics</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('reports.security-performance') }}" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
                    <i class="fas fa-tachometer-alt mr-2"></i> View Report
                </a>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-pie text-2xl text-indigo-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Analytics Dashboard</h3>
                    <p class="text-sm text-gray-500">System-wide metrics and trends</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('reports.analytics') }}" 
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-dashboard mr-2"></i> View Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="mt-8 bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Data Export
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            Export system data for external analysis
        </p>
    </div>
    <div class="px-4 py-5 sm:p-6">
        <form action="{{ route('reports.export-key-activity') }}" method="POST" class="flex items-end space-x-4">
            @csrf
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" name="start_date" id="start_date" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           value="{{ now()->subDays(30)->format('Y-m-d') }}">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" name="end_date" id="end_date" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           value="{{ now()->format('Y-m-d') }}">
                </div>
                <div>
                    <label for="format" class="block text-sm font-medium text-gray-700">Format</label>
                    <select name="format" id="format" required
                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
            </div>
            <div>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-download mr-2"></i> Export
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
