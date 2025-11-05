# Step 7: Generate All Views (Part 1)
Write-Host "Creating STU Key Management Views - Part 1..." -ForegroundColor Green

# Create view directories structure
$viewsDirs = @(
    "layouts",
    "auth",
    "admin",
    "kiosk", 
    "keys",
    "locations",
    "hr",
    "reports",
    "profile",
    "components"
)

foreach ($dir in $viewsDirs) {
    $fullPath = ".\resources\views\$dir"
    if (!(Test-Path $fullPath)) {
        New-Item -ItemType Directory -Path $fullPath -Force
    }
}

# 1. Create Main Layout
@'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'STU Key Management System')</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    
    @stack('styles')
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-key text-xl mr-2"></i>
                        <span class="font-bold text-xl">STU Keys</span>
                    </a>
                    
                    <!-- Primary Navigation -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-4">
                        @can('access dashboard')
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-500 transition {{ request()->routeIs('dashboard') ? 'bg-blue-700' : '' }}">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                        @endcan
                        
                        @can('access kiosk')
                        <a href="{{ route('kiosk.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-500 transition {{ request()->routeIs('kiosk.*') ? 'bg-blue-700' : '' }}">
                            <i class="fas fa-tablet-alt mr-1"></i> Kiosk
                        </a>
                        @endcan
                        
                        @canany(['view keys', 'manage keys'])
                        <a href="{{ route('keys.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-500 transition {{ request()->routeIs('keys.*') ? 'bg-blue-700' : '' }}">
                            <i class="fas fa-key mr-1"></i> Keys
                        </a>
                        @endcanany
                        
                        @can('manage locations')
                        <a href="{{ route('locations.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-500 transition {{ request()->routeIs('locations.*') ? 'bg-blue-700' : '' }}">
                            <i class="fas fa-map-marker-alt mr-1"></i> Locations
                        </a>
                        @endcan
                        
                        @canany(['view hr', 'manage hr'])
                        <a href="{{ route('hr.dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-500 transition {{ request()->routeIs('hr.*') ? 'bg-blue-700' : '' }}">
                            <i class="fas fa-users mr-1"></i> HR
                        </a>
                        @endcanany
                        
                        @canany(['view reports', 'view analytics'])
                        <a href="{{ route('reports.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-500 transition {{ request()->routeIs('reports.*') ? 'bg-blue-700' : '' }}">
                            <i class="fas fa-chart-bar mr-1"></i> Reports
                        </a>
                        @endcanany
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center">
                    <div x-data="{ open: false }" class="ml-3 relative">
                        <button @click="open = !open" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">Open user menu</span>
                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-white font-medium">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            </div>
                            <span class="ml-2 hidden sm:block">{{ auth()->user()->name }}</span>
                            <i class="fas fa-chevron-down ml-1 text-sm"></i>
                        </button>

                        <div x-show="open" @click.away="open = false" 
                             class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                            <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a href="{{ route('profile.activity') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-history mr-2"></i> Activity Log
                            </a>
                            @can('access kiosk')
                            <a href="{{ route('profile.shift-history') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-clock mr-2"></i> Shift History
                            </a>
                            @endcan
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <div class="sm:hidden bg-blue-600 text-white">
        <div class="px-2 pt-2 pb-3 space-y-1">
            @can('access dashboard')
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 {{ request()->routeIs('dashboard') ? 'bg-blue-700' : '' }}">
                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
            </a>
            @endcan
            @can('access kiosk')
            <a href="{{ route('kiosk.index') }}" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500 {{ request()->routeIs('kiosk.*') ? 'bg-blue-700' : '' }}">
                <i class="fas fa-tablet-alt mr-2"></i> Kiosk
            </a>
            @endcan
        </div>
    </div>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-4 sm:px-0">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">@yield('title')</h1>
                    <p class="mt-1 text-sm text-gray-600">@yield('subtitle', '')</p>
                </div>
                <div class="flex space-x-2">
                    @yield('actions')
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="mb-4 px-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 px-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="mb-4 px-4">
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('warning') }}</span>
            </div>
        </div>
        @endif

        <!-- Page Content -->
        <div class="px-4 py-4 sm:px-0">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-500">
                    &copy; {{ date('Y') }} STU Key Management System. All rights reserved.
                </p>
                <div class="flex space-x-4">
                    <span class="text-sm text-gray-500">v1.0.0</span>
                    @if(auth()->user()->isOnShift())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-circle animate-pulse mr-1"></i> On Shift
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }

        // Offline detection
        window.addEventListener('online', function() {
            document.documentElement.classList.remove('offline');
            // Trigger sync if needed
            if (typeof window.triggerSync === 'function') {
                window.triggerSync();
            }
        });

        window.addEventListener('offline', function() {
            document.documentElement.classList.add('offline');
        });
    </script>

    @stack('scripts')
</body>
</html>
'@ | Out-File -FilePath .\resources\views\layouts\app.blade.php -Encoding UTF8

# 2. Create Auth Layout
@'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - STU Key Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center">
                <i class="fas fa-key text-white text-2xl"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                STU Key Management
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Secure key handover system
            </p>
        </div>

        @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('status') }}
        </div>
        @endif

        <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
            @yield('content')
        </div>

        <div class="text-center text-sm text-gray-600">
            <p>STU University &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
'@ | Out-File -FilePath .\resources\views\layouts\auth.blade.php -Encoding UTF8

# 3. Create Login View
@'
@extends('layouts.auth')

@section('content')
<form class="space-y-6" action="{{ route('login') }}" method="POST">
    @csrf
    
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
            Email Address
        </label>
        <div class="mt-1">
            <input id="email" name="email" type="email" autocomplete="email" required 
                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                   value="{{ old('email') }}"
                   placeholder="Enter your email">
        </div>
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
            Password
        </label>
        <div class="mt-1">
            <input id="password" name="password" type="password" autocomplete="current-password" required 
                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                   placeholder="Enter your password">
        </div>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <input id="remember_me" name="remember" type="checkbox" 
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                Remember me
            </label>
        </div>

        @if (Route::has('password.request'))
        <div class="text-sm">
            <a href="{{ route('password.request') }}" class="font-medium text-blue-600 hover:text-blue-500">
                Forgot your password?
            </a>
        </div>
        @endif
    </div>

    <div>
        <button type="submit" 
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                <i class="fas fa-sign-in-alt text-blue-500 group-hover:text-blue-400"></i>
            </span>
            Sign in
        </button>
    </div>
</form>

<div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">
                System Access
            </span>
        </div>
    </div>
    <div class="mt-4 text-center">
        <p class="text-sm text-gray-600">
            Contact administrator for account access
        </p>
    </div>
</div>
@endsection
'@ | Out-File -FilePath .\resources\views\auth\login.blade.php -Encoding UTF8

# 4. Create Dashboard View
@'
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stats Cards -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-key text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Keys</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['total_keys'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Available Keys</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['available_keys'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-check text-2xl text-orange-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Checked Out</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['checked_out_keys'] }}</dd>
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
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['overdue_keys'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Activity -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Recent Activity
            </h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
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
                                    {{ $activity->receiver->name }}
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Busiest Locations -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Busiest Locations (Last 7 Days)
            </h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="flow-root">
                <ul class="-mb-8">
                    @foreach($busiestLocations as $location)
                    <li class="relative pb-6">
                        <div class="relative flex space-x-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $location->name }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $location->campus }} - {{ $location->building }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $location->recent_checkouts }} checkouts
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
@can('access kiosk')
<div class="mt-8 bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Quick Actions
        </h3>
    </div>
    <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('kiosk.scan') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-qrcode mr-2"></i> Scan Key
            </a>
            <a href="{{ route('keys.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-key mr-2"></i> View Keys
            </a>
            <a href="{{ route('reports.current-holders') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-list mr-2"></i> Current Holders
            </a>
            <a href="{{ route('reports.overdue-keys') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-exclamation-triangle mr-2"></i> Overdue Keys
            </a>
        </div>
    </div>
</div>
@endcan
@endsection
'@ | Out-File -FilePath .\resources\views\admin\dashboard.blade.php -Encoding UTF8

# 5. Create Kiosk Index View
@'
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
'@ | Out-File -FilePath .\resources\views\kiosk\index.blade.php -Encoding UTF8

Write-Host "✅ First 5 views created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in resources/views/" -ForegroundColor Cyan
Write-Host "➡️ Views: layouts (app, auth), auth/login, admin/dashboard, kiosk/index" -ForegroundColor Yellow