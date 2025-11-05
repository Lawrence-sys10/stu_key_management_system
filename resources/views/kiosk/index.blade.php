@extends('layouts.app')

@section('title', 'Kiosk Dashboard')

@section('subtitle', 'Key handover station')

@section('actions')
@if(!auth()->user()->isOnShift())
<a href="{{ route('profile.start-shift') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
    <i class="fas fa-play mr-2"></i> Start Shift
</a>
@else
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
    <i class="fas fa-circle animate-pulse mr-1"></i> On Shift
</span>
@endif
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Quick Scan -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-qrcode text-3xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <h3 class="text-lg font-medium text-gray-900">Scan Key QR</h3>
                    <p class="mt-1 text-sm text-gray-500">Scan a key QR code to begin checkout or checkin</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('kiosk.scan') }}" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-camera mr-2"></i> Start Scanning
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Checkout -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-arrow-right text-3xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <h3 class="text-lg font-medium text-gray-900">Quick Checkout</h3>
                    <p class="mt-1 text-sm text-gray-500">Check out a key to a staff member</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('keys.index') }}?status=available" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-key mr-2"></i> Browse Available Keys
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Checkin -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-arrow-left text-3xl text-orange-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <h3 class="text-lg font-medium text-gray-900">Quick Checkin</h3>
                    <p class="mt-1 text-sm text-gray-500">Check in a currently held key</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('keys.index') }}?status=checked_out" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                    <i class="fas fa-undo mr-2"></i> Browse Checked Out Keys
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="mt-8 bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Your Recent Activity
        </h3>
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
                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                            <div>
                                <p class="text-sm text-gray-500">
                                    Key <span class="font-medium text-gray-900">{{ $activity->key->label }}</span>
                                    was {{ $activity->action === 'checkout' ? 'checked out' : 'checked in' }}
                                    by <span class="font-medium">{{ $activity->holder_name }}</span>
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $activity->verified ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $activity->verified ? 'Verified' : 'Discrepancy' }}
                                </span>
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
</div>

<!-- Shift Information -->
@if(auth()->user()->isOnShift())
<div class="mt-8 bg-green-50 border border-green-200 rounded-lg p-6">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="fas fa-clock text-2xl text-green-600"></i>
        </div>
        <div class="ml-4">
            <h3 class="text-lg font-medium text-green-800">Active Shift</h3>
            <p class="text-green-700">
                Started: {{ auth()->user()->current_shift->start_at->format('M j, Y g:i A') }}
                ({{ auth()->user()->current_shift->start_at->diffForHumans() }})
            </p>
            <p class="text-sm text-green-600 mt-1">
                Duration: {{ auth()->user()->current_shift->getDurationInMinutes() }} minutes
            </p>
        </div>
        <div class="ml-auto">
            <form action="{{ route('profile.end-shift') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    <i class="fas fa-stop mr-2"></i> End Shift
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    // Auto-refresh every 30 seconds if on kiosk page
    setTimeout(function() {
        window.location.reload();
    }, 30000);
</script>
@endpush
