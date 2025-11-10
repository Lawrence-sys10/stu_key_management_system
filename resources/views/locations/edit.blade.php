@extends('layouts.app')

@section('title', 'Edit ' . $location->name)

@section('subtitle', 'Update location information')

@section('actions')
    <div class="flex space-x-2">
        <a href="{{ route('locations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Locations</span>
        </a>
        <a href="{{ route('locations.show', $location) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
            <i class="fas fa-eye"></i>
            <span>View Details</span>
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Location</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Update location information for {{ $location->name }}</p>
        </div>

        <!-- Form -->
        <form action="{{ route('locations.update', $location) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-6">
                <!-- Location Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Location Name *
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name"
                           value="{{ old('name', $location->name) }}"
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
                                <option value="{{ $key }}" {{ old('campus', $location->campus) == $key ? 'selected' : '' }}>{{ $value }}</option>
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
                               value="{{ old('building', $location->building) }}"
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
                           value="{{ old('room', $location->room) }}"
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
                              placeholder="Optional description of the location...">{{ old('description', $location->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', $location->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active Location</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Inactive locations won't be available for new key assignments.</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                @if($location->keys->count() === 0)
                <form action="{{ route('locations.destroy', $location) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this location? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="px-4 py-2 border border-red-300 dark:border-red-600 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-900 transition-colors duration-200">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Location
                    </button>
                </form>
                @else
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Cannot delete location with assigned keys
                </div>
                @endif
                
                <div class="flex space-x-3">
                    <a href="{{ route('locations.show', $location) }}" 
                       class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center space-x-2 transition-colors duration-200">
                        <i class="fas fa-save"></i>
                        <span>Update Location</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Key Assignment Warning -->
    @if($location->keys->count() > 0)
    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Location has assigned keys</h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                    <p>This location has {{ $location->keys->count() }} key(s) assigned to it. Changing location details may affect key tracking and assignments.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection