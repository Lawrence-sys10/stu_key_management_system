@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Edit Profile</h1>
        <p class="text-gray-600 mt-2">Update your personal information and preferences</p>
    </div>

    <!-- Debug info - Remove in production -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-yellow-400 mt-1"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Debug Information</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p><strong>User ID:</strong> {{ $user->id ?? 'NO USER' }}</p>
                    <p><strong>User Name:</strong> {{ $user->name ?? 'NO NAME' }}</p>
                    <p><strong>Route:</strong> {{ request()->url() }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($user)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-user-edit mr-2"></i>Personal Information
                    </h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- Name Field -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-gray-400"></i>Full Name
                            </label>
                            <input type="text" 
                                   id="name"
                                   name="name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror"
                                   value="{{ old('name', $user->name) }}"
                                   placeholder="Enter your full name">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Email Field -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-gray-400"></i>Email Address
                            </label>
                            <input type="email" 
                                   id="email"
                                   name="email" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-300 @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   placeholder="Enter your email address">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Phone Field -->
                        <div class="mb-6">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2 text-gray-400"></i>Phone Number
                            </label>
                            <input type="text" 
                                   id="phone"
                                   name="phone" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-300 @enderror"
                                   value="{{ old('phone', $user->phone ?? '') }}"
                                   placeholder="Enter your phone number">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                            <a href="{{ route('profile.show') }}" 
                               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar - Additional Information -->
        <div class="space-y-6">
            <!-- Profile Status -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-info-circle mr-2"></i>Profile Status
                    </h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Email Verified</span>
                            @if($user->email_verified_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Verified
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Pending
                                </span>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Member Since</span>
                            <span class="text-sm text-gray-900">{{ $user->created_at->format('M j, Y') }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Last Updated</span>
                            <span class="text-sm text-gray-900">{{ $user->updated_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-3">
                        <a href="{{ route('profile.show') }}" 
                           class="flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-user mr-2"></i>View Profile
                        </a>
                        <a href="{{ route('profile.password.edit') }}" 
                           class="flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-lock mr-2"></i>Change Password
                        </a>
                        <a href="{{ route('profile.shift-history') }}" 
                           class="flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-clock mr-2"></i>Shift History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Error State -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-red-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No User Data Available</h3>
                <p class="text-gray-500 mb-4">The controller is not passing the user variable to the view.</p>
                <a href="{{ route('profile.show') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Profile
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Add any client-side validation or enhancements here
    document.addEventListener('DOMContentLoaded', function() {
        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = value.match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                    e.target.value = !value[2] ? value[1] : '(' + value[1] + ') ' + value[2] + (value[3] ? '-' + value[3] : '');
                }
            });
        }
    });
</script>
@endpush
@endsection