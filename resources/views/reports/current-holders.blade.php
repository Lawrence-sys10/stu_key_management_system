@extends('layouts.app')

@section('title', 'Current Key Holders Report')

@section('subtitle', 'View all currently collected keys and their holders')

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Filters -->
    <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Holder name, key, or location...">
            </div>
            <div>
                <label for="holder_type" class="block text-sm font-medium text-gray-700">Holder Type</label>
                <select name="holder_type" id="holder_type" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Types</option>
                    <option value="student" {{ request('holder_type') == 'student' ? 'selected' : '' }}>Student</option>
                    <option value="staff" {{ request('holder_type') == 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="visitor" {{ request('holder_type') == 'visitor' ? 'selected' : '' }}>Visitor</option>
                    <option value="contractor" {{ request('holder_type') == 'contractor' ? 'selected' : '' }}>Contractor</option>
                </select>
            </div>
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <select name="location_id" id="location" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="{{ route('reports.current-holders') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-refresh mr-2"></i> Reset
                </a>
                @if($currentHolders->count() > 0)
                    <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Current Holders Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Key Information
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Holder Information
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Checkout Details
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Expected Return
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Issued By
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($currentHolders as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-key text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $log->key->label }}</div>
                                    <div class="text-sm text-gray-500">{{ $log->key->code }}</div>
                                    <div class="text-xs text-gray-400">{{ $log->key->location->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $log->holder_name }}</div>
                            <div class="text-sm text-gray-500">{{ $log->holder_phone ?? 'No phone' }}</div>
                            <div class="text-xs text-gray-400 capitalize">{{ $log->holder_type }}</div>
                            @if($log->holder_id)
                                <div class="text-xs text-gray-400">ID: {{ $log->holder_id }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $log->created_at->format('M j, Y g:i A') }}</div>
                            <div class="text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->expected_return_at)
                                @php
                                    $isOverdue = $log->expected_return_at->isPast();
                                @endphp
                                <div class="text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                    {{ $log->expected_return_at->format('M j, Y g:i A') }}
                                </div>
                                <div class="text-sm {{ $isOverdue ? 'text-red-500' : 'text-gray-500' }}">
                                    {{ $isOverdue ? 'Overdue' : $log->expected_return_at->diffForHumans() }}
                                </div>
                                @if($isOverdue)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Overdue
                                    </span>
                                @endif
                            @else
                                <span class="text-sm text-gray-500">Not specified</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $log->receiver_name }}</div>
                            <div class="text-sm text-gray-500">{{ $log->created_at->format('M j, Y') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            <i class="fas fa-key text-3xl text-gray-300 mb-2 block"></i>
                            No keys are currently collected.
                            <p class="text-sm text-gray-400 mt-1">All keys are available in the system.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($currentHolders->hasPages())
        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
            {{ $currentHolders->links() }}
        </div>
    @endif
</div>

<!-- Summary Stats -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-key text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Collected</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $currentHolders->total() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-friends text-2xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Staff Holders</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $currentHolders->where('holder_type', 'staff')->count() }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-graduate text-2xl text-orange-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Student Holders</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $currentHolders->where('holder_type', 'student')->count() }}
                        </dd>
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
                        <dt class="text-sm font-medium text-gray-500 truncate">Overdue Keys</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $overdueCount }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .bg-gray-50 { background-color: #f9fafb !important; }
    .shadow { box-shadow: none !important; }
    .rounded-lg { border-radius: 0 !important; }
    .hidden { display: none !important; }
    .flex { display: block !important; }
    .space-x-2 > * { display: inline-block; margin-right: 0.5rem; }
    .px-6 { padding-left: 1.5rem !important; padding-right: 1.5rem !important; }
    .py-4 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
}
</style>
@endsection