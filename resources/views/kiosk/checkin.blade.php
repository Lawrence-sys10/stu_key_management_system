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
                <h3 class="text-lg font-medium text-orange-900 mb-4">Currently Checked Out To</h3>
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
                        <p class="font-medium text-orange-800">Collected</p>
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
                
                <!-- Returner Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Who is returning the key? *</label>
                    
                    <div class="space-y-3">
                        <!-- Same Person -->
                        <div class="flex items-center">
                            <input type="radio" id="same_person" name="returned_by_same_person" value="1" 
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" checked>
                            <label for="same_person" class="ml-3 block text-sm font-medium text-gray-700">
                                <span class="font-semibold">Same Person</span> - {{ $currentCheckout->holder_name }} is returning the key
                            </label>
                        </div>
                        
                        <!-- Different Person -->
                        <div class="flex items-center">
                            <input type="radio" id="different_person" name="returned_by_same_person" value="0" 
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                            <label for="different_person" class="ml-3 block text-sm font-medium text-gray-700">
                                <span class="font-semibold">Different Person</span> - Someone else is returning on their behalf
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Different Person Returner Details -->
                <div id="differentPersonSection" class="hidden space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 mb-6">
                    <h4 class="font-semibold text-gray-900">Returner Information</h4>
                    
                    <!-- Returner Type -->
                    <div>
                        <label for="returned_by_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Returner Type *
                        </label>
                        <select name="returned_by_type" id="returned_by_type" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Type</option>
                            <option value="hr">HR Staff</option>
                            <option value="perm_manual">Permanent Staff</option>
                            <option value="temp">Temporary Staff</option>
                            <option value="visitor">Visitor/Other</option>
                        </select>
                    </div>

                    <!-- Returner Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Search Returner
                        </label>
                        <div class="relative">
                            <input type="text" id="returner_search" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Search by name, phone, or ID...">
                            <div id="returner_results" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                    </div>

                    <!-- Manual Returner Details -->
                    <div id="manualReturnerDetails" class="space-y-3">
                        <div>
                            <label for="returned_by_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Returner Name *
                            </label>
                            <input type="text" name="returned_by_name" id="returned_by_name"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="returned_by_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Returner Phone *
                            </label>
                            <input type="text" name="returned_by_phone" id="returned_by_phone"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Signature Capture -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Returner Signature *
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
    
    // Toggle different person section
    const samePersonRadio = document.getElementById('same_person');
    const differentPersonRadio = document.getElementById('different_person');
    const differentPersonSection = document.getElementById('differentPersonSection');

    function toggleReturnerSection() {
        if (differentPersonRadio.checked) {
            differentPersonSection.classList.remove('hidden');
        } else {
            differentPersonSection.classList.add('hidden');
        }
    }

    samePersonRadio.addEventListener('change', toggleReturnerSection);
    differentPersonRadio.addEventListener('change', toggleReturnerSection);

    // Returner search functionality
    const returnerSearch = document.getElementById('returner_search');
    const returnerResults = document.getElementById('returner_results');
    let searchTimeout;

    returnerSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            returnerResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`{{ route('kiosk.search-holder') }}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    returnerResults.innerHTML = '';
                    
                    if (data.length === 0) {
                        returnerResults.classList.add('hidden');
                        return;
                    }

                    data.forEach(person => {
                        const div = document.createElement('div');
                        div.className = 'p-3 hover:bg-gray-100 cursor-pointer border-b border-gray-200 last:border-b-0';
                        div.innerHTML = `
                            <div class="font-medium text-gray-900">${person.name}</div>
                            <div class="text-sm text-gray-600">${person.phone} • ${person.type_label}</div>
                            ${person.dept ? `<div class="text-xs text-gray-500">${person.dept}</div>` : ''}
                        `;
                        div.addEventListener('click', function() {
                            document.querySelector('select[name="returned_by_type"]').value = person.type;
                            document.querySelector('input[name="returned_by_name"]').value = person.name;
                            document.querySelector('input[name="returned_by_phone"]').value = person.phone;
                            if (person.type !== 'visitor') {
                                document.querySelector('input[name="returned_by_id"]').value = person.id;
                            }
                            returnerResults.classList.add('hidden');
                            returnerSearch.value = person.name;
                        });
                        returnerResults.appendChild(div);
                    });
                    
                    returnerResults.classList.remove('hidden');
                });
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!returnerSearch.contains(e.target) && !returnerResults.contains(e.target)) {
            returnerResults.classList.add('hidden');
        }
    });

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

    // Form validation
    document.getElementById('checkin-form').addEventListener('submit', function(e) {
        if (!signaturePad.isEmpty()) {
            document.getElementById('signature-data').value = signaturePad.toDataURL();
        } else {
            e.preventDefault();
            alert('Please provide a signature to complete the checkin.');
            return;
        }
        
        if (document.getElementById('different_person').checked) {
            const name = document.querySelector('input[name="returned_by_name"]').value.trim();
            const phone = document.querySelector('input[name="returned_by_phone"]').value.trim();
            const type = document.querySelector('select[name="returned_by_type"]').value;
            
            if (!name || !phone || !type) {
                e.preventDefault();
                alert('Please fill in all required returner information.');
                return;
            }
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
</script>
@endpush