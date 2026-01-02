<!DOCTYPE html>
<html lang="en">

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
    
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>

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

        /* Custom scrollbar */
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

        /* Dark mode */
        .dark .sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.5);
        }

        .dark .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(75, 85, 99, 0.7);
        }

        [x-cloak] { display: none !important; }

        /* Mobile improvements */
        @media (max-width: 768px) {
            .mobile-touch-target {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    <!-- Theme initialization -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const themeColorMeta = document.getElementById('theme-color-meta');
            
            if (savedTheme === 'dark') {
                html.classList.add('dark');
                themeColorMeta.setAttribute('content', '#1f2937');
            } else {
                html.classList.remove('dark');
                themeColorMeta.setAttribute('content', '#3b82f6');
            }
        })();
    </script>

    <!-- Main Layout Container -->
    <div class="flex h-screen overflow-hidden" x-data="{
        sidebarOpen: false,
        sidebarCollapsed: window.innerWidth >= 1024 ? localStorage.getItem('sidebarCollapsed') === 'true' : false,
        notificationsOpen: false,
        userMenuOpen: false,
        init() {
            // Initialize sidebar state
            if (window.innerWidth >= 1024) {
                this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            }
            
            this.$watch('sidebarCollapsed', (value) => {
                localStorage.setItem('sidebarCollapsed', value);
            });
        }
    }">
        
        <!-- Sidebar - FIXED: Removed transform and added proper show/hide -->
        <div class="flex flex-col sidebar-transition bg-white shadow-lg z-40 fixed lg:relative dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700"
             :class="[
                 sidebarCollapsed ? 'w-[70px]' : 'w-[260px]',
                 sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
             ]"
             style="height: 100vh;">
            
            <!-- Logo and Toggle -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                    <img src="{{ asset('stu_logo.png') }}" alt="STU Logo" class="h-8 w-8">
                    <span x-show="!sidebarCollapsed" class="font-bold text-xl transition-opacity duration-200">STU Keys</span>
                </a>
                
                <!-- Collapse Toggle -->
                <button @click="sidebarCollapsed = !sidebarCollapsed" 
                        class="p-1 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hidden lg:block mobile-touch-target">
                    <i class="fas text-sm transition-transform duration-200" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            <div class="flex-1 overflow-y-auto sidebar-scrollbar py-4">
                <nav class="space-y-1 px-3">
                    @can('access dashboard')
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                              {{ request()->routeIs('dashboard') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-tachometer-alt w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Dashboard</span>
                    </a>
                    @endcan

                    @can('access kiosk')
                    <a href="{{ route('kiosk.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                              {{ request()->routeIs('kiosk.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-tablet-alt w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Kiosk</span>
                    </a>
                    @endcan

                    @canany(['view keys', 'manage keys'])
                    <a href="{{ route('keys.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                              {{ request()->routeIs('keys.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-key w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Keys</span>
                    </a>
                    @endcanany

                    @can('manage locations')
                    <a href="{{ route('locations.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                              {{ request()->routeIs('locations.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-map-marker-alt w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">Locations</span>
                    </a>
                    @endcan

                    @canany(['view hr', 'manage hr'])
                    <a href="{{ route('hr.dashboard') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                              {{ request()->routeIs('hr.*') 
                                 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                 : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-users w-5 text-center mr-3"></i>
                        <span x-show="!sidebarCollapsed" class="transition-opacity duration-200">HR</span>
                    </a>
                    @endcanany

                    @canany(['view reports', 'view analytics'])
                    <a href="{{ route('reports.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 mobile-touch-target
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
                        <p x-show="!sidebarCollapsed" class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 transition-opacity duration-200">Administration</p>
                        <a href="{{ route('admin.users') }}" 
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 mobile-touch-target
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
                <div class="relative">
                    <button @click="userMenuOpen = !userMenuOpen" 
                            class="flex items-center w-full p-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 mobile-touch-target">
                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium mr-3 flex-shrink-0">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 1)) }}
                        </div>
                        <div x-show="!sidebarCollapsed" class="text-left flex-1 transition-opacity duration-200">
                            <div class="font-medium truncate text-gray-900 dark:text-white">{{ auth()->user()?->name ?? 'Guest' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()?->email ?? '' }}</div>
                        </div>
                        <i class="fas fa-chevron-down text-xs ml-auto transition-opacity duration-200" x-show="!sidebarCollapsed"></i>
                    </button>

                    <!-- User Dropdown Menu -->
                    <div x-show="userMenuOpen" @click.away="userMenuOpen = false" x-cloak
                         class="absolute bottom-full left-0 mb-2 w-full rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none z-50">
                        <div class="py-1">
                            <a href="{{ route('profile.show') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-user mr-3 w-5 text-center"></i>
                                <span>Profile</span>
                            </a>
                            <a href="{{ route('profile.edit') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-edit mr-3 w-5 text-center"></i>
                                <span>Edit Profile</span>
                            </a>
                            <a href="{{ route('profile.password.edit') }}" 
                               class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-lock mr-3 w-5 text-center"></i>
                                <span>Change Password</span>
                            </a>
                            <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <button type="submit" 
                                        class="flex items-center w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
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
             :class="sidebarCollapsed ? 'lg:ml-[70px]' : 'lg:ml-[260px]'">
            
            <!-- Top Header -->
            <header class="bg-white shadow-sm z-30 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between h-16 px-4 lg:px-6">
                    <!-- Mobile Menu Button -->
                    <button @click="sidebarOpen = true" 
                            class="lg:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 mobile-touch-target">
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
                        <button id="theme-toggle" 
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 mobile-touch-target">
                            <i class="fas" id="theme-icon"></i>
                        </button>

                        <!-- Notifications -->
                        <div class="relative">
                            <button @click="notificationsOpen = !notificationsOpen" 
                                    class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 mobile-touch-target relative">
                                <i class="fas fa-bell"></i>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                            </button>
                            
                            <!-- Notifications Dropdown -->
                            <div x-show="notificationsOpen" @click.away="notificationsOpen = false" x-cloak
                                 class="absolute right-0 mt-2 w-80 rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none z-50">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Notifications</h3>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
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
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center dark:bg-green-900 dark:border-green-700 dark:text-green-300" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                @if (session('error'))
                <div class="mb-6">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center dark:bg-red-900 dark:border-red-700 dark:text-red-300" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
                @endif

                @if (session('warning'))
                <div class="mb-6">
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg flex items-center dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-300" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 dark:bg-gray-800 dark:border-gray-700 py-4 px-4 lg:px-6">
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

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" class="fixed inset-0 flex z-50 lg:hidden" x-cloak>
            <!-- Overlay -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false" 
                 class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity duration-300">
            </div>
            
            <!-- Mobile Sidebar -->
            <div x-show="sidebarOpen" 
                 class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-gray-800 shadow-xl"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full">
                
                <div class="absolute top-0 right-0 -mr-12 pt-4">
                    <button @click="sidebarOpen = false" 
                            class="ml-1 flex items-center justify-center h-10 w-10 rounded-full bg-black/20 text-white hover:bg-black/30 transition-colors duration-200 mobile-touch-target">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Mobile sidebar content -->
                <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto sidebar-scrollbar">
                    <div class="flex-shrink-0 flex items-center px-4 mb-6">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 text-blue-600 dark:text-blue-400">
                            <img src="{{ asset('stu_logo.png') }}" alt="STU Logo" class="h-10 w-10">
                            <span class="font-bold text-xl">STU Keys</span>
                        </a>
                    </div>
                    <nav class="mt-5 px-2 space-y-1">
                        <!-- Same navigation items as desktop sidebar -->
                        @can('access dashboard')
                        <a href="{{ route('dashboard') }}" 
                           class="group flex items-center px-3 py-2 text-base font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                                  {{ request()->routeIs('dashboard') 
                                     ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                     : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-tachometer-alt mr-4 text-lg"></i>
                            Dashboard
                        </a>
                        @endcan

                        @can('access kiosk')
                        <a href="{{ route('kiosk.index') }}" 
                           class="group flex items-center px-3 py-2 text-base font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                                  {{ request()->routeIs('kiosk.*') 
                                     ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                     : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-tablet-alt mr-4 text-lg"></i>
                            Kiosk
                        </a>
                        @endcan

                        @canany(['view keys', 'manage keys'])
                        <a href="{{ route('keys.index') }}" 
                           class="group flex items-center px-3 py-2 text-base font-medium rounded-lg transition-colors duration-200 mobile-touch-target
                                  {{ request()->routeIs('keys.*') 
                                     ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                     : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-key mr-4 text-lg"></i>
                            Keys
                        </a>
                        @endcanany
                        <!-- Add other navigation items as needed -->
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');
            const themeColorMeta = document.getElementById('theme-color-meta');
            const html = document.documentElement;

            function updateThemeIcon() {
                if (html.classList.contains('dark')) {
                    themeIcon.className = 'fas fa-sun';
                } else {
                    themeIcon.className = 'fas fa-moon';
                }
            }

            themeToggle.addEventListener('click', function() {
                const isDark = html.classList.contains('dark');
                
                if (isDark) {
                    html.classList.remove('dark');
                    themeColorMeta.setAttribute('content', '#3b82f6');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    themeColorMeta.setAttribute('content', '#1f2937');
                    localStorage.setItem('theme', 'dark');
                }
                
                updateThemeIcon();
            });

            updateThemeIcon();
        });

        // Close mobile sidebar when clicking on a link
        document.addEventListener('DOMContentLoaded', function() {
            const mobileLinks = document.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        Alpine.store('sidebarOpen', false);
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>