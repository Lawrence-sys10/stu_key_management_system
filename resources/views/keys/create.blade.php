@extends('layouts.app')

@section('title', 'Add New Key')

@section('subtitle', 'Create a new key in the system')

@section('actions')
    <a href="{{ route('keys.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
        <i class="fas fa-arrow-left mr-2"></i> Back to Keys
    </a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form action="{{ route('keys.store') }}" method="POST">
                @csrf
                
                <div class="space-y-6">
                    <!-- Key Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Key Code *</label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-500 @enderror"
                               placeholder="e.g., ADM001, LAB205"
                               required>
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Label -->
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700">Label *</label>
                        <input type="text" name="label" id="label" value="{{ old('label') }}" 
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('label') border-red-500 @enderror"
                               placeholder="e.g., Main Office Key, Science Lab Key"
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
                                  placeholder="Optional key description">{{ old('description') }}</textarea>
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
                                <option value="physical" {{ old('key_type') == 'physical' ? 'selected' : '' }}>Physical</option>
                                <option value="electronic" {{ old('key_type') == 'electronic' ? 'selected' : '' }}>Electronic</option>
                                <option value="master" {{ old('key_type') == 'master' ? 'selected' : '' }}>Master</option>
                                <option value="duplicate" {{ old('key_type') == 'duplicate' ? 'selected' : '' }}>Duplicate</option>
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
                                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- QR Code Generation -->
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="generate_qr" id="generate_qr" value="1" 
                                       {{ old('generate_qr') ? 'checked' : '' }}
                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="generate_qr" class="font-medium text-gray-700">Generate QR Tags</label>
                                <p class="text-gray-500">Generate QR code tags for this key to enable quick scanning.</p>
                                
                                <!-- QR Count (only show if generate_qr is checked) -->
                                <div id="qr-count-section" class="mt-3 {{ old('generate_qr') ? '' : 'hidden' }}">
                                    <label for="qr_count" class="block text-sm font-medium text-gray-700">Number of QR Tags</label>
                                    <select name="qr_count" id="qr_count" 
                                            class="mt-1 block w-32 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" {{ old('qr_count', 1) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Generate multiple tags for backup purposes.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('keys.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Create Key
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Toggle QR count section
    document.getElementById('generate_qr').addEventListener('change', function(e) {
        document.getElementById('qr-count-section').classList.toggle('hidden', !e.target.checked);
    });
</script>
@endpush