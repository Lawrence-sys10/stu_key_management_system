# Step 8: Generate All Views (Part 2 - Key & Kiosk Operations)
Write-Host "Creating STU Key Management Views - Part 2..." -ForegroundColor Green

# 6. Create Kiosk Scan View
@'
@extends('layouts.app')

@section('title', 'Scan Key QR Code')

@section('subtitle', 'Point camera at key QR code to scan')

@section('actions')
<a href="{{ route('kiosk.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
    <i class="fas fa-arrow-left mr-2"></i> Back to Kiosk
</a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Scanner Interface -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div id="scanner-container" class="text-center">
                <!-- Camera Feed -->
                <div class="mb-4">
                    <div id="camera-feed" class="mx-auto max-w-md">
                        <video id="scanner-video" class="w-full h-64 bg-gray-200 rounded-lg" playsinline></video>
                    </div>
                </div>

                <!-- Manual Input Fallback -->
                <div class="mt-6 border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Manual Entry</h3>
                    <form id="manual-scan-form" class="flex gap-2">
                        @csrf
                        <input type="text" id="manual-uuid" name="uuid" 
                               class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter QR code UUID manually" required>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Scanning Instructions</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Ensure good lighting for better scanning</li>
                                    <li>Hold the QR code steady in front of the camera</li>
                                    <li>Allow camera permissions if prompted</li>
                                    <li>Use manual entry if scanning fails</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loading-state" class="hidden text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                <p class="text-gray-600">Processing key information...</p>
            </div>

            <!-- Scan Result -->
            <div id="scan-result" class="hidden"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scannerContainer = document.getElementById('scanner-container');
    const loadingState = document.getElementById('loading-state');
    const scanResult = document.getElementById('scan-result');
    const manualForm = document.getElementById('manual-scan-form');
    const manualInput = document.getElementById('manual-uuid');

    // Initialize camera scanner
    function initializeScanner() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showManualOnly();
            return;
        }

        const video = document.getElementById('scanner-video');
        
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } 
        })
        .then(function(stream) {
            video.srcObject = stream;
            video.play();
            
            // Initialize QR code scanning
            initializeQRScanner(video);
        })
        .catch(function(error) {
            console.error('Camera error:', error);
            showManualOnly();
        });
    }

    function initializeQRScanner(video) {
        // Simple QR code detection using canvas
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        
        function scanFrame() {
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Here you would integrate with a proper QR code library
                // For now, we'll rely on manual input
            }
            requestAnimationFrame(scanFrame);
        }
        
        video.addEventListener('play', function() {
            requestAnimationFrame(scanFrame);
        });
    }

    function showManualOnly() {
        const cameraFeed = document.getElementById('camera-feed');
        cameraFeed.innerHTML = `
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                <i class="fas fa-camera-slash text-yellow-400 text-3xl mb-2"></i>
                <p class="text-yellow-700">Camera not available. Please use manual entry.</p>
            </div>
        `;
    }

    // Handle manual form submission
    manualForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const uuid = manualInput.value.trim();
        
        if (uuid) {
            processScan(uuid);
        }
    });

    // Process scan result
    function processScan(uuid) {
        scannerContainer.classList.add('hidden');
        loadingState.classList.remove('hidden');
        
        fetch('{{ route("kiosk.process-scan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ uuid: uuid })
        })
        .then(response => response.json())
        .then(data => {
            loadingState.classList.add('hidden');
            displayScanResult(data);
        })
        .catch(error => {
            console.error('Scan error:', error);
            loadingState.classList.add('hidden');
            showError('Failed to process scan. Please try again.');
        });
    }

    function displayScanResult(data) {
        if (data.error) {
            showError(data.error);
            return;
        }

        const key = data.key;
        const currentStatus = data.current_status;
        
        scanResult.innerHTML = `
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-center mb-6">
                    <i class="fas fa-key text-4xl text-blue-600 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900">${key.label}</h2>
                    <p class="text-gray-600">${key.code} • ${key.location.name}</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 mb-2">Key Information</h3>
                        <p><strong>Type:</strong> ${key.key_type}</p>
                        <p><strong>Location:</strong> ${key.location.building}, ${key.location.campus}</p>
                        <p><strong>Room:</strong> ${key.room || 'N/A'}</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 mb-2">Current Status</h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            ${currentStatus === 'available' ? 'bg-green-100 text-green-800' : 
                              currentStatus === 'checked_out' ? 'bg-orange-100 text-orange-800' : 
                              'bg-red-100 text-red-800'}">
                            ${currentStatus.replace('_', ' ').toUpperCase()}
                        </span>
                        ${data.current_holder ? `
                        <p class="mt-2"><strong>Current Holder:</strong> ${data.current_holder.name}</p>
                        <p><strong>Since:</strong> ${new Date(data.current_holder.checked_out_at).toLocaleString()}</p>
                        ` : ''}
                    </div>
                </div>
                
                <div class="flex justify-center space-x-4">
                    ${currentStatus === 'available' ? `
                    <a href="/kiosk/checkout/${key.id}" 
                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-arrow-right mr-2"></i> Check Out
                    </a>
                    ` : ''}
                    
                    ${currentStatus === 'checked_out' ? `
                    <a href="/kiosk/checkin/${key.id}" 
                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i> Check In
                    </a>
                    ` : ''}
                    
                    <button onclick="resetScanner()" 
                            class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-redo mr-2"></i> Scan Another
                    </button>
                </div>
            </div>
        `;
        
        scanResult.classList.remove('hidden');
    }

    function showError(message) {
        scanResult.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-triangle text-red-400 text-3xl mb-4"></i>
                <h3 class="text-lg font-medium text-red-800 mb-2">Scan Failed</h3>
                <p class="text-red-700">${message}</p>
                <button onclick="resetScanner()" 
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    Try Again
                </button>
            </div>
        `;
        scanResult.classList.remove('hidden');
    }

    window.resetScanner = function() {
        scanResult.classList.add('hidden');
        scannerContainer.classList.remove('hidden');
        manualInput.value = '';
    };

    // Initialize scanner when page loads
    initializeScanner();
});
</script>
@endpush
'@ | Out-File -FilePath .\resources\views\kiosk\scan.blade.php -Encoding UTF8

# 7. Create Kiosk Checkout View
@'
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
'@ | Out-File -FilePath .\resources\views\kiosk\checkout.blade.php -Encoding UTF8

# 8. Create Kiosk Checkin View
@'
@extends('layouts.app')

@section('title', 'Check In Key')

@section('subtitle', 'Return key to inventory')

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
                    </div>
                </div>
            </div>

            <!-- Current Holder Information -->
            <div class="mb-8 p-6 bg-orange-50 rounded-lg">
                <h3 class="text-lg font-medium text-orange-900 mb-4">Current Holder</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="font-medium text-orange-800">Name</p>
                        <p class="text-orange-700">{{ $currentCheckout->holder_name }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-orange-800">Phone</p>
                        <p class="text-orange-700">{{ $currentCheckout->holder_phone }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-orange-800">Checked Out</p>
                        <p class="text-orange-700">{{ $currentCheckout->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-orange-800">Expected Return</p>
                        <p class="text-orange-700">
                            {{ $currentCheckout->expected_return_at ? $currentCheckout->expected_return_at->format('M j, Y g:i A') : 'Not specified' }}
                        </p>
                    </div>
                </div>
                
                @if($currentCheckout->isOverdue())
                <div class="mt-4 p-3 bg-red-100 border border-red-200 rounded-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                        <span class="text-red-800 font-medium">This key is overdue for return</span>
                    </div>
                </div>
                @endif
            </div>

            <form action="{{ route('kiosk.process-checkin', $key) }}" method="POST" id="checkin-form">
                @csrf
                
                <!-- Signature Capture -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Return Signature (Optional)
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-md p-4">
                        <canvas id="signature-pad" width="600" height="200" 
                                class="w-full bg-white border border-gray-300 rounded"></canvas>
                        <div class="mt-2 flex justify-between">
                            <button type="button" onclick="clearSignature()" 
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-eraser mr-1"></i> Clear
                            </button>
                            <span class="text-sm text-gray-500">Sign to confirm return</span>
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
                        Return Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                              class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Any notes about the return condition..."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('kiosk.scan') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-check-circle mr-2"></i> Complete Checkin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
<script>
let signaturePad;
let cameraStream;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize signature pad
    const canvas = document.getElementById('signature-pad');
    signaturePad = new SignaturePad(canvas);
    
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

// Handle form submission
document.getElementById('checkin-form').addEventListener('submit', function(e) {
    if (!signaturePad.isEmpty()) {
        document.getElementById('signature-data').value = signaturePad.toDataURL();
    }
});
</script>
@endpush
'@ | Out-File -FilePath .\resources\views\kiosk\checkin.blade.php -Encoding UTF8

# 9. Create Keys Index View
@'
@extends('layouts.app')

@section('title', 'Key Management')

@section('subtitle', 'Manage all keys in the system')

@section('actions')
@can('manage keys')
<a href="{{ route('keys.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
    <i class="fas fa-plus mr-2"></i> Add New Key
</a>
@endcan
@endsection

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Filters -->
    <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Key code or label...">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Statuses</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                    <option value="lost" {{ request('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                    <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </div>
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <select name="location_id" id="location" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                    <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                        {{ $location->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="{{ route('keys.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-refresh mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Keys Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Key
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Location
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Current Holder
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Last Activity
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($keys as $key)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-key text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $key->label }}</div>
                                <div class="text-sm text-gray-500">{{ $key->code }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $key->location->name }}</div>
                        <div class="text-sm text-gray-500">{{ $key->location->campus }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $key->status === 'available' ? 'bg-green-100 text-green-800' : 
                               $key->status === 'checked_out' ? 'bg-orange-100 text-orange-800' : 
                               $key->status === 'lost' ? 'bg-red-100 text-red-800' : 
                               'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $key->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($key->currentHolder)
                        <div class="text-sm text-gray-900">{{ $key->currentHolder->holder_name }}</div>
                        <div class="text-sm text-gray-500">{{ $key->currentHolder->holder_phone }}</div>
                        @else
                        <span class="text-sm text-gray-500">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($key->lastLog)
                        {{ $key->lastLog->created_at->diffForHumans() }}
                        @else
                        Never
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('keys.show', $key) }}" 
                               class="text-blue-600 hover:text-blue-900" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('manage keys')
                            <a href="{{ route('keys.edit', $key) }}" 
                               class="text-green-600 hover:text-green-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($key->isCheckedOut())
                            <form action="{{ route('keys.mark-lost', $key) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="text-red-600 hover:text-red-900" 
                                        title="Mark as Lost"
                                        onclick="return confirm('Are you sure you want to mark this key as lost?')">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </button>
                            </form>
                            @endif
                            @endcan
                            @can('access kiosk')
                            @if($key->isAvailable())
                            <a href="{{ route('kiosk.checkout', $key) }}" 
                               class="text-green-600 hover:text-green-900" title="Check Out">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                            @elseif($key->isCheckedOut())
                            <a href="{{ route('kiosk.checkin', $key) }}" 
                               class="text-orange-600 hover:text-orange-900" title="Check In">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        <i class="fas fa-key text-3xl text-gray-300 mb-2 block"></i>
                        No keys found matching your criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($keys->hasPages())
    <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
        {{ $keys->links() }}
    </div>
    @endif
</div>

<!-- Quick Stats -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-key text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Keys</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $keys->total() }}</dd>
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
                        <dt class="text-sm font-medium text-gray-500 truncate">Available</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $keys->where('status', 'available')->count() }}
                        </dd>
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
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $keys->where('status', 'checked_out')->count() }}
                        </dd>
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
                        <dt class="text-sm font-medium text-gray-500 truncate">Lost/Maintenance</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $keys->whereIn('status', ['lost', 'maintenance'])->count() }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
'@ | Out-File -FilePath .\resources\views\keys\index.blade.php -Encoding UTF8

Write-Host "✅ Step 8 views created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in resources/views/" -ForegroundColor Cyan
Write-Host "➡️ Views: kiosk/scan, kiosk/checkout, kiosk/checkin, keys/index" -ForegroundColor Yellow