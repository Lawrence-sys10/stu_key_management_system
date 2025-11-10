# Step 9: Generate All Views (Part 3 - HR, Reports & Profile)
Write-Host "Creating STU Key Management Views - Part 3..." -ForegroundColor Green

# 10. Create HR Dashboard View
@'
@extends('layouts.app')

@section('title', 'HR Dashboard')

@section('subtitle', 'Staff management and discrepancy resolution')

@section('actions')
<a href="{{ route('hr.import.form') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
    <i class="fas fa-upload mr-2"></i> Import Staff
</a>
<a href="{{ route('hr.manual-staff.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
    <i class="fas fa-user-plus mr-2"></i> Add Manual Staff
</a>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stats Cards -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total HR Staff</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['total_hr_staff'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-check text-2xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Staff</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['active_hr_staff'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-edit text-2xl text-orange-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Manual Staff</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['total_manual_staff'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Discrepancies</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['pending_discrepancies'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Discrepancies -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Recent Discrepancies
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Unverified key transactions requiring attention
            </p>
        </div>
        <div class="px-4 py-5 sm:p-6">
            @if($recentDiscrepancies->count() > 0)
            <div class="flow-root">
                <ul class="-mb-8">
                    @foreach($recentDiscrepancies as $discrepancy)
                    <li class="relative pb-8">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white bg-red-500">
                                    <i class="fas fa-exclamation text-white text-sm"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        Key <span class="font-medium text-gray-900">{{ $discrepancy->key->label }}</span>
                                        was {{ $discrepancy->action }} by 
                                        <span class="font-medium">{{ $discrepancy->holder_name }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $discrepancy->created_at->diffForHumans() }} • 
                                        Processed by {{ $discrepancy->receiver->name }}
                                    </p>
                                    @if($discrepancy->discrepancy_reason)
                                    <p class="text-xs text-red-600 mt-1">
                                        Reason: {{ $discrepancy->discrepancy_reason }}
                                    </p>
                                    @endif
                                </div>
                                <div class="mt-2 flex space-x-2">
                                    <a href="{{ route('hr.discrepancies.index') }}" 
                                       class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700">
                                        Resolve
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-check-circle text-4xl text-green-300 mb-4"></i>
                <p class="text-gray-500">No pending discrepancies</p>
            </div>
            @endif
        </div>
        @if($recentDiscrepancies->count() > 0)
        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
            <a href="{{ route('hr.discrepancies.index') }}" 
               class="text-sm font-medium text-blue-600 hover:text-blue-500">
                View all discrepancies
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        @endif
    </div>

    <!-- Recent Manual Additions -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Recent Manual Staff Additions
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Staff added manually through the system
            </p>
        </div>
        <div class="px-4 py-5 sm:p-6">
            @if($recentManualAdditions->count() > 0)
            <div class="flow-root">
                <ul class="-mb-8">
                    @foreach($recentManualAdditions as $staff)
                    <li class="relative pb-6">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white bg-orange-500">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $staff->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $staff->phone }}</p>
                                    @if($staff->staff_id)
                                    <p class="text-xs text-gray-400">ID: {{ $staff->staff_id }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">
                                        Added by {{ $staff->addedBy->name }} • 
                                        {{ $staff->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @else
            <div class="text-center py-8">
                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No manual staff additions</p>
            </div>
            @endif
        </div>
        @if($recentManualAdditions->count() > 0)
        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
            <a href="{{ route('hr.manual-staff.index') }}" 
               class="text-sm font-medium text-blue-600 hover:text-blue-500">
                View all manual staff
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        @endif
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8 bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Quick Actions
        </h3>
    </div>
    <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('hr.staff.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-list mr-2"></i> View All Staff
            </a>
            <a href="{{ route('hr.discrepancies.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                <i class="fas fa-exclamation-triangle mr-2"></i> Resolve Discrepancies
            </a>
            <a href="{{ route('hr.import.form') }}" 
               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-file-csv mr-2"></i> Import CSV
            </a>
            <a href="{{ route('reports.staff-activity') }}" 
               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-chart-bar mr-2"></i> Staff Reports
            </a>
        </div>
    </div>
</div>
@endsection
'@ | Out-File -FilePath .\resources\views\hr\dashboard.blade.php -Encoding UTF8

# 11. Create HR Staff Import View
@'
@extends('layouts.app')

@section('title', 'Import HR Staff')

@section('subtitle', 'Bulk import staff from CSV file')

@section('actions')
<a href="{{ route('hr.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
    <i class="fas fa-arrow-left mr-2"></i> Back to HR
</a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <!-- Import Form -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Upload CSV File</h3>
                
                <form action="{{ route('hr.import.hr-staff') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="space-y-4">
                        <!-- File Upload -->
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                                CSV File *
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-csv text-3xl text-gray-400 mx-auto"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload a file</span>
                                            <input id="csv_file" name="csv_file" type="file" class="sr-only" accept=".csv,.txt" required>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">CSV, TXT up to 10MB</p>
                                </div>
                            </div>
                            @error('csv_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Update Existing -->
                        <div class="flex items-center">
                            <input id="update_existing" name="update_existing" type="checkbox" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                            <label for="update_existing" class="ml-2 block text-sm text-gray-900">
                                Update existing records
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-upload mr-2"></i> Import Staff
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- CSV Format Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-4">CSV Format Requirements</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-blue-800 mb-2">Required Columns</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><code>staff_id</code> - Unique staff identifier</li>
                            <li><code>name</code> - Full name of staff member</li>
                            <li><code>phone</code> - Phone number</li>
                            <li><code>status</code> - active/inactive</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-blue-800 mb-2">Optional Columns</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><code>dept</code> - Department</li>
                            <li><code>email</code> - Email address</li>
                        </ul>
                    </div>
                </div>

                <!-- Sample CSV -->
                <div class="mt-4">
                    <h4 class="font-medium text-blue-800 mb-2">Sample CSV Format</h4>
                    <div class="bg-white border border-blue-200 rounded-md p-3 text-sm">
                        <code class="text-blue-700">
staff_id,name,phone,dept,email,status<br>
STU001,John Doe,0234567890,IT,john.doe@stu.edu.gh,active<br>
STU002,Jane Smith,0234567891,HR,jane.smith@stu.edu.gh,active<br>
STU003,Bob Johnson,0234567892,Finance,,inactive
                        </code>
                    </div>
                </div>
            </div>

            <!-- Import Errors -->
            @if(session('import_errors'))
            <div class="mt-8 bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-red-900 mb-4">Import Errors</h3>
                <div class="text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
'@ | Out-File -FilePath .\resources\views\hr\import\hr-staff.blade.php -Encoding UTF8

# 12. Create Reports Index View
@'
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
'@ | Out-File -FilePath .\resources\views\reports\index.blade.php -Encoding UTF8

# 13. Create Profile Show View
@'
@extends('layouts.app')

@section('title', 'My Profile')

@section('subtitle', 'Manage your account and view activity')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Profile Information -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Profile Information
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Your personal details and account information
                </p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email Address</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->phone ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">User Roles</label>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach($user->getRoleNames() as $role)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($role) }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Account Created</label>
                        <p class="mt-1 text-sm text-gray-900">
                            {{ $user->created_at->format('F j, Y g:i A') }}
                            ({{ $user->created_at->diffForHumans() }})
                        </p>
                    </div>
                </div>
                
                <div class="mt-6 flex space-x-3">
                    <a href="{{ route('profile.edit') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-edit mr-2"></i> Edit Profile
                    </a>
                    <a href="{{ route('profile.update-password') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-lock mr-2"></i> Change Password
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Recent Activity
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Your recent key transactions
                </p>
            </div>
            <div class="px-4 py-5 sm:p-6">
                @if($recentActivity->count() > 0)
                <div class="flow-root">
                    <ul class="-mb-8">
                        @foreach($recentActivity as $activity)
                        <li class="relative pb-8">
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white 
                                        {{ $activity->action === 'checkout' ? 'bg-green-500' : 'bg-blue-500' }}">
                                        <i class="fas fa-{{ $activity->action === 'checkout' ? 'arrow-right' : 'arrow-left' }} text-white text-sm"></i>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            Key <span class="font-medium text-gray-900">{{ $activity->key->label }}</span>
                                            was {{ $activity->action === 'checkout' ? 'checked out' : 'checked in' }}
                                            by <span class="font-medium">{{ $activity->holder_name }}</span>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No recent activity found</p>
                </div>
                @endif
            </div>
            @if($recentActivity->count() > 0)
            <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
                <a href="{{ route('profile.activity') }}" 
                   class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    View full activity history
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Shift Information -->
        @if(auth()->user()->hasRole('security'))
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Shift Information</h3>
                
                @if($currentShift)
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-play-circle text-green-500 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">Active Shift</p>
                            <p class="text-sm text-green-700">
                                Started: {{ $currentShift->start_at->format('g:i A') }}
                            </p>
                            <p class="text-xs text-green-600">
                                Duration: {{ $currentShift->getDurationInMinutes() }} minutes
                            </p>
                        </div>
                    </div>
                </div>
                
                <form action="{{ route('profile.end-shift') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-stop mr-2"></i> End Shift
                    </button>
                </form>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-clock text-3xl text-gray-300 mb-2"></i>
                    <p class="text-sm text-gray-500 mb-4">No active shift</p>
                    <form action="{{ route('profile.start-shift') }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-play mr-2"></i> Start Shift
                        </button>
                    </form>
                </div>
                @endif
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('profile.shift-history') }}" 
                       class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        View shift history
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Stats -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Your Statistics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Total Transactions</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ $user->keyLogsAsReceiver()->count() }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Checkouts Processed</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ $user->keyLogsAsReceiver()->where('action', 'checkout')->count() }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Checkins Processed</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ $user->keyLogsAsReceiver()->where('action', 'checkin')->count() }}
                        </span>
                    </div>
                    @if(auth()->user()->hasRole('security'))
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Total Shifts</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ $user->securityShifts()->count() }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">System Info</h3>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Last Login</span>
                        <span>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Account Age</span>
                        <span>{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Current Time</span>
                        <span>{{ now()->format('g:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
'@ | Out-File -FilePath .\resources\views\profile\show.blade.php -Encoding UTF8

Write-Host "✅ Step 9 views created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in resources/views/" -ForegroundColor Cyan
Write-Host "➡️ Views: hr/dashboard, hr/import/hr-staff, reports/index, profile/show" -ForegroundColor Yellow