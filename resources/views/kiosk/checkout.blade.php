@extends('layouts.app')

@section('title', 'Check Out Key')

@section('subtitle', 'Assign key to staff member')

@section('actions')
<a href="{{ route('kiosk.scan') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
    <i class="fas fa-arrow-left mr-2"></i> Back to Scan
</a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <!-- Key Information -->
            <div class="mb-8 p-6 bg-blue-50 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-key text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-blue-900">{{ $key->label }}</h3>
                        <p class="text-blue-700">
                            {{ $key->code }} • {{ $key->location->full_address }}
                        </p>
                        <p class="text-sm text-blue-600">
                            Type: {{ ucfirst($key->key_type) }}
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('kiosk.process-checkout', $key) }}" method="POST" id="checkout-form">
                @csrf
                
                <!-- Staff Search -->
                <div class="mb-6">
                    <label for="staff-search" class="block text-sm font-medium text-gray-700 mb-2">
                        Search Staff Member *
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="staff-search"
                               class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Search by name, staff ID, or phone number"
                               autocomplete="off">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Search Results -->
                    <div id="search-results" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"></div>
                    
                    <!-- Selected Staff Display -->
                    <div id="selected-staff" class="hidden mt-3 p-4 bg-green-50 border border-green-200 rounded-md">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-medium text-green-900" id="selected-name"></h4>
                                <p class="text-sm text-green-700" id="selected-details"></p>
                            </div>
                            <button type="button" onclick="clearSelection()" class="text-green-600 hover:text-green-800">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <input type="hidden" name="holder_type" id="holder-type">
                        <input type="hidden" name="holder_id" id="holder-id">
                        <input type="hidden" name="holder_name" id="holder-name">
                        <input type="hidden" name="holder_phone" id="holder-phone">
                    </div>
                </div>

                <!-- Expected Return -->
                <div class="mb-6">
                    <label for="expected-return" class="block text-sm font-medium text-gray-700 mb-2">
                        Expected Return Time (Optional)
                    </label>
                    <input type="datetime-local" 
                           id="expected-return"
                           name="expected_return_at"
                           class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           min="{{ now()->format('Y-m-d\TH:i') }}">
                    <p class="mt-1 text-sm text-gray-500">Leave blank if return time is not specified</p>
                </div>

                <!-- Signature Capture -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Signature Capture
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-md p-4">
                        <canvas id="signature-pad" width="600" height="200" 
                                class="w-full bg-white border border-gray-300 rounded"></canvas>
                        <div class="mt-2 flex justify-between">
                            <button type="button" onclick="clearSignature()" 
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-eraser mr-1"></i> Clear
                            </button>
                            <span class="text-sm text-gray-500">Sign in the box above</span>
                        </div>
                        <input type="hidden" name="signature" id="signature-data">
                    </div>
                </div>

                <!-- Photo Upload -->
                <div class="mb-6">
                    <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">
                        Photo Capture (Optional)
                    </label>
                    <div class="flex items-center space-x-4">
                        <div id="camera-preview" class="hidden">
                            <video id="camera-view" class="w-32 h-32 object-cover rounded border border-gray-300" autoplay muted></video>
                        </div>
                        <div id="photo-preview" class="hidden">
                            <img id="photo-preview-img" class="w-32 h-32 object-cover rounded border border-gray-300">
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" onclick="startCamera()" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-camera mr-1"></i> Take Photo
                            </button>
                            <button type="button" onclick="capturePhoto()" id="capture-btn" 
                                    class="hidden inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-camera-retro mr-1"></i> Capture
                            </button>
                            <input type="file" id="photo-upload" name="photo" accept="image/*" capture="environment" class="hidden">
                            <button type="button" onclick="document.getElementById('photo-upload').click()" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Additional Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                              class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Any additional information..."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('kiosk.scan') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            id="submit-btn"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            disabled>
                        <i class="fas fa-check-circle mr-2"></i> Complete Checkout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature-pad.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
<script>
let signaturePad;
let cameraStream;

document.addEventListener('DOMContent
# Continue with Step 8 Views...

# 7. Create Kiosk Checkout View (continued)
@'
<script>
let signaturePad;
let cameraStream;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize signature pad
    const canvas = document.getElementById('signature-pad');
    signaturePad = new SignaturePad(canvas);
    
    // Staff search functionality
    const searchInput = document.getElementById('staff-search');
    const searchResults = document.getElementById('search-results');
    const selectedStaff = document.getElementById('selected-staff');
    const submitBtn = document.getElementById('submit-btn');
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }
        
        fetch(`{{ route('kiosk.search-holder') }}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    });
    
    function displaySearchResults(results) {
        if (results.length === 0) {
            searchResults.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    No staff members found. 
                    <button type="button" onclick="showAddStaffOptions()" class="text-blue-600 hover:text-blue-800 ml-1">
                        Add new staff?
                    </button>
                </div>
            `;
        } else {
            searchResults.innerHTML = results.map(staff => `
                <div class="p-3 border-b border-gray-200 hover:bg-gray-50 cursor-pointer" 
                     onclick="selectStaff(${JSON.stringify(staff).replace(/"/g, '&quot;')})">
                    <div class="font-medium text-gray-900">${staff.name}</div>
                    <div class="text-sm text-gray-600">${staff.phone} • ${staff.type_label}</div>
                    ${staff.dept ? `<div class="text-sm text-gray-500">${staff.dept}</div>` : ''}
                </div>
            `).join('');
        }
        searchResults.classList.remove('hidden');
    }
    
    // Handle file upload preview
    document.getElementById('photo-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photo-preview-img').src = e.target.result;
                document.getElementById('photo-preview').classList.remove('hidden');
                document.getElementById('camera-preview').classList.add('hidden');
                stopCamera();
            };
            reader.readAsDataURL(file);
        }
    });
});

function selectStaff(staff) {
    document.getElementById('holder-type').value = staff.type;
    document.getElementById('holder-id').value = staff.id;
    document.getElementById('holder-name').value = staff.name;
    document.getElementById('holder-phone').value = staff.phone;
    
    document.getElementById('selected-name').textContent = staff.name;
    document.getElementById('selected-details').textContent = `${staff.phone} • ${staff.type_label}`;
    
    document.getElementById('staff-search').value = '';
    document.getElementById('search-results').classList.add('hidden');
    document.getElementById('selected-staff').classList.remove('hidden');
    document.getElementById('submit-btn').disabled = false;
}

function clearSelection() {
    document.getElementById('selected-staff').classList.add('hidden');
    document.getElementById('submit-btn').disabled = true;
    document.getElementById('holder-type').value = '';
    document.getElementById('holder-id').value = '';
    document.getElementById('holder-name').value = '';
    document.getElementById('holder-phone').value = '';
}

function clearSignature() {
    signaturePad.clear();
    document.getElementById('signature-data').value = '';
}

function startCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                cameraStream = stream;
                const video = document.getElementById('camera-view');
                video.srcObject = stream;
                document.getElementById('camera-preview').classList.remove('hidden');
                document.getElementById('capture-btn').classList.remove('hidden');
                document.getElementById('photo-preview').classList.add('hidden');
            })
            .catch(function(error) {
                console.error('Camera error:', error);
                alert('Unable to access camera. Please use file upload instead.');
            });
    } else {
        alert('Camera not supported on this device. Please use file upload.');
    }
}

function capturePhoto() {
    const video = document.getElementById('camera-view');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0);
    
    document.getElementById('photo-preview-img').src = canvas.toDataURL('image/png');
    document.getElementById('photo-preview').classList.remove('hidden');
    document.getElementById('camera-preview').classList.add('hidden');
    document.getElementById('capture-btn').classList.add('hidden');
    
    stopCamera();
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}

function showAddStaffOptions() {
    // Implementation for adding new staff would go here
    alert('Staff addition feature would be implemented here');
}

// Handle form submission
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    if (!signaturePad.isEmpty()) {
        document.getElementById('signature-data').value = signaturePad.toDataURL();
    }
    
    // Basic validation
    const holderType = document.getElementById('holder-type').value;
    if (!holderType) {
        e.preventDefault();
        alert('Please select a staff member');
        return;
    }
});
</script>
@endpush
