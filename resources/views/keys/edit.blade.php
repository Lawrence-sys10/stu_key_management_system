@extends('layouts.app')

@section('title', 'Edit Key - ' . $key->label)

@section('subtitle', 'Update key information')

@section('actions')
    <div class="flex space-x-2">
        <a href="{{ route('keys.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to Keys
        </a>
        <a href="{{ route('keys.show', $key) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-eye mr-2"></i> View Details
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form action="{{ route('keys.update', $key) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    <!-- Key Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Key Code *</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $key->code) }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-500 @enderror"
                               required>
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Label -->
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700">Label *</label>
                        <input type="text" name="label" id="label" value="{{ old('label', $key->label) }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('label') border-red-500 @enderror"
                               required>
                        @error('label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Optional key description">{{ old('description', $key->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Key Type -->
                        <div>
                            <label for="key_type" class="block text-sm font-medium text-gray-700">Key Type *</label>
                            <select name="key_type" id="key_type" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('key_type') border-red-500 @enderror"
                                    required>
                                <option value="">Select Key Type</option>
                                <option value="physical" {{ old('key_type', $key->key_type) == 'physical' ? 'selected' : '' }}>Physical</option>
                                <option value="electronic" {{ old('key_type', $key->key_type) == 'electronic' ? 'selected' : '' }}>Electronic</option>
                                <option value="master" {{ old('key_type', $key->key_type) == 'master' ? 'selected' : '' }}>Master</option>
                                <option value="duplicate" {{ old('key_type', $key->key_type) == 'duplicate' ? 'selected' : '' }}>Duplicate</option>
                            </select>
                            @error('key_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div>
                            <label for="location_id" class="block text-sm font-medium text-gray-700">Location *</label>
                            <select name="location_id" id="location_id" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('location_id') border-red-500 @enderror"
                                    required>
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id', $key->location_id) == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                            <select name="status" id="status" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-500 @enderror"
                                    required>
                                <option value="">Select Status</option>
                                <option value="available" {{ old('status', $key->status) == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="checked_out" {{ old('status', $key->status) == 'checked_out' ? 'selected' : '' }}>Collected</option>
                                <option value="lost" {{ old('status', $key->status) == 'lost' ? 'selected' : '' }}>Lost</option>
                                <option value="maintenance" {{ old('status', $key->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Current Status Info -->
                    @if($key->isCheckedOut())
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Key is Currently Collected</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>This key is currently Collected. Changing the status to "Available" will not automatically check it in.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('keys.show', $key) }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i> Update Key
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    @can('manage keys')
    <div class="bg-white shadow rounded-lg mt-6 border border-red-200">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-red-800">Danger Zone</h3>
            <div class="mt-2 max-w-xl text-sm text-red-600">
                <p>Once you delete a key, there is no going back. Please be certain.</p>
            </div>
            <div class="mt-5">
                @if($key->isCheckedOut())
                    <button type="button" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white opacity-50 cursor-not-allowed"
                            disabled
                            title="Cannot delete a key that is collected">
                        <i class="fas fa-trash mr-2"></i> Delete Key
                    </button>
                    <p class="mt-1 text-sm text-gray-500">Cannot delete a key that is currently Collected.</p>
                @else
                    <form action="{{ route('keys.destroy', $key) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this key? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash mr-2"></i> Delete Key
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection

@push('scripts')
<script>
    // Add confirmation for status change to lost
    document.getElementById('status').addEventListener('change', function(e) {
        if (e.target.value === 'lost') {
            if (!confirm('Are you sure you want to mark this key as lost? This will log it as lost in the system.')) {
                e.target.value = '{{ $key->status }}';
            }
        }
    });
</script>
@endpush