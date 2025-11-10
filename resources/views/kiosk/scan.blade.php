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
