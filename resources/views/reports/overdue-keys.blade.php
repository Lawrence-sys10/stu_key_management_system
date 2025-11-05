@extends('layouts.app')

@section('title', 'Overdue Keys Report')

@section('subtitle', 'View all keys that are past their expected return date')

@section('actions')
    <div class="flex space-x-2">
        <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-print mr-2"></i> Print Report
        </button>
        <a href="{{ route('reports.current-holders') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-list mr-2"></i> View Current Holders
        </a>
    </div>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Summary Header -->
    <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Overdue Keys Report</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Keys that are past their expected return date as of {{ now()->format('M j, Y g:i A') }}
                </p>
            </div>
            <div class="flex items-center justify-end md:col-span-2">
                <div class="bg-red-50 border border-red-200 rounded-md p-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                <span class="font-bold">{{ $overdueKeys->total() }}</span> overdue keys found
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Keys Table -->
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
                        Overdue Details
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Security Officer
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($overdueKeys as $log)
                    @php
                        $daysOverdue = $log->expected_return_at->diffInDays(now());
                        $overdueSeverity = $daysOverdue > 7 ? 'high' : ($daysOverdue > 3 ? 'medium' : 'low');
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-key text-red-600"></i>
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
                            <div class="text-xs text-gray-400">Duration: {{ $log->created_at->diffInDays(now()) }} days</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-red-600">
                                {{ $log->expected_return_at->format('M j, Y g:i A') }}
                            </div>
                            <div class="text-sm text-red-500">
                                {{ $daysOverdue }} days overdue
                            </div>
                            <div class="mt-1">
                                @if($overdueSeverity === 'high')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i> High Priority
                                    </span>
                                @elseif($overdueSeverity === 'medium')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Medium Priority
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i> Low Priority
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $log->receiver_name }}</div>
                            <div class="text-sm text-gray-500">{{ $log->created_at->format('M j, Y') }}</div>
                            @if($log->receiver)
                                <div class="text-xs text-gray-400">{{ $log->receiver->email }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            <i class="fas fa-check-circle text-3xl text-green-300 mb-2 block"></i>
                            No overdue keys found!
                            <p class="text-sm text-gray-400 mt-1">All keys have been returned on time.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($overdueKeys->hasPages())
        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
            {{ $overdueKeys->links() }}
        </div>
    @endif
</div>

<!-- Priority Summary -->
@if($overdueKeys->count() > 0)
<div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    @php
        $highPriority = $overdueKeys->filter(function($log) {
            return $log->expected_return_at->diffInDays(now()) > 7;
        })->count();
        
        $mediumPriority = $overdueKeys->filter(function($log) {
            $days = $log->expected_return_at->diffInDays(now());
            return $days > 3 && $days <= 7;
        })->count();
        
        $lowPriority = $overdueKeys->filter(function($log) {
            return $log->expected_return_at->diffInDays(now()) <= 3;
        })->count();
        
        $oldestOverdue = $overdueKeys->sortBy('expected_return_at')->first();
    @endphp

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">High Priority</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $highPriority }}</dd>
                        <dt class="text-sm text-gray-400">7+ days overdue</dt>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-2xl text-orange-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Medium Priority</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $mediumPriority }}</dd>
                        <dt class="text-sm text-gray-400">4-7 days overdue</dt>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Low Priority</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $lowPriority }}</dd>
                        <dt class="text-sm text-gray-400">1-3 days overdue</dt>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-times text-2xl text-purple-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Oldest Overdue</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            @if($oldestOverdue)
                                {{ $oldestOverdue->expected_return_at->diffInDays(now()) }} days
                            @else
                                N/A
                            @endif
                        </dd>
                        <dt class="text-sm text-gray-400">Longest overdue</dt>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Recommendations -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-lightbulb text-blue-400"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Recommended Actions</h3>
            <div class="mt-2 text-sm text-blue-700">
                <ul class="list-disc list-inside space-y-1">
                    <li>Contact holders of high-priority overdue keys immediately</li>
                    <li>Send reminder emails to medium-priority holders</li>
                    <li>Update security team about overdue keys during shift changes</li>
                    <li>Consider temporary access restrictions for repeat offenders</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endif

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
    .bg-red-50 { background-color: #fef2f2 !important; }
    .bg-blue-50 { background-color: #eff6ff !important; }
}
</style>
@endsection