@extends('layouts.app')

@section('title', 'Add New Location')

@section('subtitle', 'Create a new building or room location')

@section('actions')
    <a href="{{ route('locations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Locations</span>
    </a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Create New Location</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Add a new building or room location to the system</p>
        </div>

        <!-- Form -->
        <form action="{{ route('locations.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-6">
                <!-- Location Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Location Name *
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name"
                           value="{{ old('name') }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Main Office, Science Lab, Conference Room">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Campus and Building -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Campus -->
                    <div>
                        <label for="campus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Campus *
                        </label>
                        <select name="campus" 
                                id="campus"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Campus</option>
                            @foreach($campuses as $key => $value)
                                <option value="{{ $key }}" {{ old('campus') == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('campus')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Building -->
                    <div>
                        <label for="building" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Building *
                        </label>
                        <input type="text" 
                               name="building" 
                               id="building"
                               value="{{ old('building') }}"
                               required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., Science Building, Admin Block">
                        @error('building')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Room -->
                <div>
                    <label for="room" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Room Number
                    </label>
                    <input type="text" 
                           name="room" 
                           id="room"
                           value="{{ old('room') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., 101, A-12, Ground Floor">
                    @error('room')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Optional description of the location...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                <a href="{{ route('locations.index') }}" 
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center space-x-2 transition-colors duration-200">
                    <i class="fas fa-plus"></i>
                    <span>Create Location</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Help Card -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 mt-1"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Location Guidelines</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Use clear, descriptive names for locations</li>
                        <li>Include room numbers when applicable</li>
                        <li>Group related locations under the same building</li>
                        <li>Use consistent naming conventions across campuses</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection@extends('layouts.app')

@section('title', 'Add New Location')

@section('subtitle', 'Create a new building or room location')

@section('actions')
    <a href="{{ route('locations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Locations</span>
    </a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Create New Location</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Add a new building or room location to the system</p>
        </div>

        <!-- Form -->
        <form action="{{ route('locations.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-6">
                <!-- Location Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Location Name *
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name"
                           value="{{ old('name') }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Main Office, Science Lab, Conference Room">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Campus and Building -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Campus -->
                    <div>
                        <label for="campus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Campus *
                        </label>
                        <select name="campus" 
                                id="campus"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Campus</option>
                            @foreach($campuses as $key => $value)
                                <option value="{{ $key }}" {{ old('campus') == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('campus')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Building -->
                    <div>
                        <label for="building" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Building *
                        </label>
                        <input type="text" 
                               name="building" 
                               id="building"
                               value="{{ old('building') }}"
                               required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., Science Building, Admin Block">
                        @error('building')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Room -->
                <div>
                    <label for="room" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Room Number
                    </label>
                    <input type="text" 
                           name="room" 
                           id="room"
                           value="{{ old('room') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., 101, A-12, Ground Floor">
                    @error('room')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Optional description of the location...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                <a href="{{ route('locations.index') }}" 
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center space-x-2 transition-colors duration-200">
                    <i class="fas fa-plus"></i>
                    <span>Create Location</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Help Card -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 mt-1"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Location Guidelines</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Use clear, descriptive names for locations</li>
                        <li>Include room numbers when applicable</li>
                        <li>Group related locations under the same building</li>
                        <li>Use consistent naming conventions across campuses</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection