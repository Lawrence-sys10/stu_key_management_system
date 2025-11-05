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
