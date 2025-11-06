@extends('layouts.app')

@section('title', 'Shift History')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stats Cards -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Shifts</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $shifts->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-hourglass-half text-2xl text-orange-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Shift</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $shifts->whereNull('end_at')->count() > 0 ? 'Yes' : 'No' }}
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
                    <i class="fas fa-calendar-alt text-2xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">This Month</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $shifts->whereBetween('start_at', [now()->startOfMonth(), now()->endOfMonth()])->count() }}
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
                    <i class="fas fa-stopwatch text-2xl text-purple-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Avg. Duration</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            @php
                                $completedShifts = $shifts->whereNotNull('end_at');
                                if($completedShifts->count() > 0) {
                                    $totalMinutes = $completedShifts->sum(function($shift) {
                                        return $shift->start_at->diffInMinutes($shift->end_at);
                                    });
                                    $avgMinutes = $totalMinutes / $completedShifts->count();
                                    echo floor($avgMinutes / 60) . 'h ' . ($avgMinutes % 60) . 'm';
                                } else {
                                    echo 'N/A';
                                }
                            @endphp
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                <i class="fas fa-clock mr-2"></i>Shift History
            </h3>
            <a href="{{ route('profile.show') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i>Back to Profile
            </a>
        </div>
    </div>
    
    @if($shifts->count() > 0)
    <div class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($shifts as $shift)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $shift->start_at->format('M j, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $shift->start_at->format('g:i A') }} - 
                                        {{ $shift->end_at ? $shift->end_at->format('g:i A') : 'Present' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if($shift->end_at)
                                    {{ $shift->start_at->diff($shift->end_at)->format('%h hours %i minutes') }}
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="flex h-2 w-2 rounded-full bg-green-500 mr-1.5"></span>
                                        In Progress
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($shift->end_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Completed
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                {{ Str::limit($shift->notes, 50) }}
                                @if(strlen($shift->notes) > 50)
                                    <button class="text-blue-600 hover:text-blue-800 text-xs ml-1" onclick="toggleNotes({{ $shift->id }})">Show more</button>
                                    <div id="full-notes-{{ $shift->id }}" class="hidden mt-2 p-3 bg-gray-50 rounded-lg">
                                        <p class="text-sm text-gray-700">{{ $shift->notes }}</p>
                                        <button class="text-blue-600 hover:text-blue-800 text-xs mt-2" onclick="toggleNotes({{ $shift->id }})">Show less</button>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex justify-between sm:hidden">
                @if($shifts->previousPageUrl())
                    <a href="{{ $shifts->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                @endif
                @if($shifts->nextPageUrl())
                    <a href="{{ $shifts->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                @endif
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium">{{ $shifts->firstItem() }}</span>
                        to
                        <span class="font-medium">{{ $shifts->lastItem() }}</span>
                        of
                        <span class="font-medium">{{ $shifts->total() }}</span>
                        results
                    </p>
                </div>
                <div>
                    {{ $shifts->links() }}
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="px-4 py-12 sm:px-6">
        <div class="text-center">
            <i class="fas fa-clock fa-4x text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Shift History</h3>
            <p class="text-gray-500 max-w-md mx-auto mb-6">You haven't worked any shifts yet. Your shift history will appear here once you start working.</p>
            <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-play mr-2"></i>Start a Shift
            </a>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    function toggleNotes(shiftId) {
        const fullNotes = document.getElementById(`full-notes-${shiftId}`);
        if (fullNotes.classList.contains('hidden')) {
            fullNotes.classList.remove('hidden');
        } else {
            fullNotes.classList.add('hidden');
        }
    }
</script>
@endpush
@endsection