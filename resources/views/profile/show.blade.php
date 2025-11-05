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
