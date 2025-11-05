<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'STU Key Management System')</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6" id="theme-color-meta">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <!-- Styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            --header-height: 64px;
        }

        .sidebar-transition {
            transition: all 0.3s ease;
        }

        .content-transition {
            transition: margin-left 0.3s ease;
        }

        /* Custom scrollbar for sidebar */
        .sidebar-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 4px;
        }

        .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.7);
        }

        /* Dark mode styles */
        .dark .sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.5);
        }

        .dark .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(75, 85, 99, 0.7);
        }

        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Theme Toggle Script -->
    <script>
        // Check for saved theme preference or default to 'light'
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.add(currentTheme);
        
        // Update theme color meta tag
        const themeColorMeta = document.getElementById('theme-color-meta');
        if (currentTheme === 'dark') {
            themeColorMeta.setAttribute('content', '#1f2937');
        }
    </script>

    <!-- Main Layout Container -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div x-data="{ sidebarOpen: false, sidebarCollapsed: false }" 
             class="flex flex-col sidebar-transition bg-white dark:bg-gray-800 shadow-lg z-40 fixed lg:relative"
             :class="sidebarCollapsed ? 'w-[var(--sidebar-collapsed-width)]' : 'w-[var(--sidebar-width)]'">
            
            <!-- Logo and Toggle -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                    <i class="fas fa-key text-xl"></i>
                    <span x-show="!sidebarCollapsed" class="font-bold text-xl transition-opacity duration-200">STU Keys</span>
                </a>
                
                <!-- Collapse Toggle -->
                <button @click="sidebarCollapsed = !sidebarCollapsed" 
                        class="p-1 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hidden lg:block">
                    <i class="fas text-sm" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            <div class="flex-1 overflow-y-auto sidebar-scrollbar py-4">
                <nav class="space-y-1 px-3">
                    @can('access dashboard')
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 
                              {{ request()->routeIs('dashboard') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-tachometer-alt w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Dashboard</span>
                    </a>
                    @endcan

                    @can('access kiosk')
                    <a href="{{ route('kiosk.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 
                              {{ request()->routeIs('kiosk.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-tablet-alt w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Kiosk</span>
                    </a>
                    @endcan

                    @canany(['view keys', 'manage keys'])
                    <a href="{{ route('keys.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 
                              {{ request()->routeIs('keys.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-key w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Keys</span>
                    </a>
                    @endcanany

                    @can('manage locations')
                    <a href="{{ route('locations.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 
                              {{ request()->routeIs('locations.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-map-marker-alt w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Locations</span>
                    </a>
                    @endcan

                    @canany(['view hr', 'manage hr'])
                    <a href="{{ route('hr.dashboard') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 
                              {{ request()->routeIs('hr.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-users w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">HR</span>
                    </a>
                    @endcanany

                    @canany(['view reports', 'view analytics'])
                    <a href="{{ route('reports.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 
                              {{ request()->routeIs('reports.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-chart-bar w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Reports</span>
                    </a>
                    @endcanany

                    <!-- Admin Section -->
                    @can('role:admin')
                    <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                        <p x-show="!sidebarCollapsed" class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-opacity duration-200">Administration</p>
                        <a href="{{ route('admin.users') }}" 
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 
                                  {{ request()->routeIs('admin.*') 
                                     ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                     : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-cog w-5 text-center mr-3"></i>
                            <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Admin</span>
                        </a>
                    </div>
                    @endcan
                </nav>
            </div>

            <!-- User Profile & Settings -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="flex items-center w-full p-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium mr-3 flex-shrink-0">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 1)) }}
                        </div>
                        <div x-show="!sidebarCollapsed" class="text-left flex-1 transition-opacity duration-200">
                            <div class="font-medium truncate">{{ auth()->user()?->name ?? 'Guest' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()?->email ?? '' }}</div>
                        </div>
                        <i class="fas fa-chevron-down text-xs ml-auto transition-opacity duration-200" x-show="!sidebarCollapsed"></i>
                    </button>

                    <!-- User Dropdown Menu -->
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute bottom-full left-0 mb-2 w-full rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                        <div class="py-1">
                            <a href="{{ route('profile.show') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-user mr-3 w-5 text-center"></i>
                                <span>Profile</span>
                            </a>
                            <a href="{{ route('profile.edit') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-edit mr-3 w-5 text-center"></i>
                                <span>Edit Profile</span>
                            </a>
                            <a href="{{ route('profile.password.edit') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-lock mr-3 w-5 text-center"></i>
                                <span>Change Password</span>
                            </a>
                            <a href="{{ route('profile.activity') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-history mr-3 w-5 text-center"></i>
                                <span>Activity Log</span>
                            </a>
                            @can('access kiosk')
                            <a href="{{ route('profile.shift-history') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-clock mr-3 w-5 text-center"></i>
                                <span>Shift History</span>
                            </a>
                            @endcan
                            <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden content-transition min-w-0"
             :class="sidebarCollapsed ? 'lg:ml-[var(--sidebar-collapsed-width)]' : 'lg:ml-[var(--sidebar-width)]'">
            
            <!-- Top Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm z-30 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between h-[var(--header-height)] px-4 lg:px-6">
                    <!-- Mobile Menu Button -->
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="flex-1 lg:flex-none ml-2 lg:ml-0">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">@yield('title')</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">@yield('subtitle', '')</p>
                    </div>

                    <!-- Header Actions -->
                    <div class="flex items-center space-x-3">
                        <!-- Theme Toggle -->
                        <button id="theme-toggle" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:block"></i>
                        </button>

                        <!-- Notifications -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 relative">
                                <i class="fas fa-bell"></i>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                            </button>
                            
                            <!-- Notifications Dropdown -->
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-80 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Notifications</h3>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <!-- Notification items would go here -->
                                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                        No new notifications
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Page Actions -->
                        <div class="flex space-x-2">
                            @yield('actions')
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-gray-50 dark:bg-gray-900">
                <!-- Flash Messages -->
                @if (session('success'))
                <div class="mb-6">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                @if (session('error'))
                <div class="mb-6">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
                @endif

                @if (session('warning'))
                <div class="mb-6">
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg flex items-center" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 px-4 lg:px-6">
                <div class="flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ date('Y') }} STU Key Management System. All rights reserved.
                    </p>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">v1.0.0</span>
                        @if (auth()->user()?->isOnShift())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <i class="fas fa-circle animate-pulse mr-1"></i> On Shift
                        </span>
                        @endif
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" class="fixed inset-0 flex z-50 lg:hidden" x-cloak>
        <!-- Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" 
             class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>
        
        <!-- Mobile Sidebar -->
        <div x-show="sidebarOpen" 
             class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-gray-800"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <i class="fas fa-times text-white text-xl"></i>
                </button>
            </div>
            
            <!-- Mobile sidebar content -->
            <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto sidebar-scrollbar">
                <div class="flex-shrink-0 flex items-center px-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                        <i class="fas fa-key text-xl"></i>
                        <span class="font-bold text-xl">STU Keys</span>
                    </a>
                </div>
                <nav class="mt-5 px-2 space-y-1">
                    @can('access dashboard')
                    <a href="{{ route('dashboard') }}" 
                       class="group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200 
                              {{ request()->routeIs('dashboard') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-tachometer-alt mr-4 text-lg"></i>
                        Dashboard
                    </a>
                    @endcan

                    @can('access kiosk')
                    <a href="{{ route('kiosk.index') }}" 
                       class="group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200 
                              {{ request()->routeIs('kiosk.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-tablet-alt mr-4 text-lg"></i>
                        Kiosk
                    </a>
                    @endcan

                    @canany(['view keys', 'manage keys'])
                    <a href="{{ route('keys.index') }}" 
                       class="group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200 
                              {{ request()->routeIs('keys.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-key mr-4 text-lg"></i>
                        Keys
                    </a>
                    @endcanany

                    @can('manage locations')
                    <a href="{{ route('locations.index') }}" 
                       class="group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200 
                              {{ request()->routeIs('locations.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-map-marker-alt mr-4 text-lg"></i>
                        Locations
                    </a>
                    @endcan

                    @canany(['view hr', 'manage hr'])
                    <a href="{{ route('hr.dashboard') }}" 
                       class="group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200 
                              {{ request()->routeIs('hr.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-users mr-4 text-lg"></i>
                        HR
                    </a>
                    @endcanany

                    @canany(['view reports', 'view analytics'])
                    <a href="{{ route('reports.index') }}" 
                       class="group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200 
                              {{ request()->routeIs('reports.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-chart-bar mr-4 text-lg"></i>
                        Reports
                    </a>
                    @endcanany

                    <!-- Admin Section -->
                    @can('role:admin')
                    <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Administration</p>
                        <a href="{{ route('admin.users') }}" 
                           class="group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200 
                                  {{ request()->routeIs('admin.*') 
                                     ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                     : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-cog mr-4 text-lg"></i>
                            Admin
                        </a>
                    </div>
                    @endcan
                </nav>
            </div>

            <!-- Mobile User Profile -->
            <div class="flex-shrink-0 flex border-t border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center w-full">
                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 1)) }}
                    </div>
                    <div class="ml-3">
                        <p class="text-base font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()?->name ?? 'Guest' }}</p>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ auth()->user()?->email ?? '' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Theme toggle functionality
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const html = document.documentElement;
            const themeColorMeta = document.getElementById('theme-color-meta');
            
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                html.classList.add('light');
                themeColorMeta.setAttribute('content', '#3b82f6');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.remove('light');
                html.classList.add('dark');
                themeColorMeta.setAttribute('content', '#1f2937');
                localStorage.setItem('theme', 'dark');
            }
        });

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