@extends('layouts.app')

@section('title', 'Print QR Tags - ' . $key->label)

@section('subtitle', 'Print QR code tags for key tracking')

@section('actions')
    <a href="{{ route('keys.show', $key) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
        <i class="fas fa-arrow-left mr-2"></i> Back to Key
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <!-- Print Header -->
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Print QR Tags</h3>
                    <p class="text-sm text-gray-500">Key: {{ $key->label }} ({{ $key->code }})</p>
                </div>
                <button onclick="window.print()" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
            </div>

            <!-- Tags Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 print:grid-cols-4">
                @foreach($tags as $tag)
                    <div class="border border-gray-200 rounded-lg p-4 text-center print:break-inside-avoid">
                        <!-- QR Code Placeholder -->
                       {{-- Replace the QR code placeholder section with: --}}
<div class="bg-white p-2 rounded mb-2 mx-auto" style="width: 120px; height: 120px;">
    {!! QrCode::size(100)->generate(route('keys.scan', $tag->uuid)) !!}
</div>
                        
                        <!-- Key Information -->
                        <div class="text-xs space-y-1">
                            <div class="font-semibold">{{ $key->code }}</div>
                            <div class="text-gray-600">{{ Str::limit($key->label, 20) }}</div>
                            <div class="text-gray-500">{{ $tag->uuid }}</div>
                        </div>
                        
                        <!-- Scan Instructions -->
                        <div class="mt-2 text-xs text-gray-400">
                            Scan to track key
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Print Instructions -->
            <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-md print:hidden">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Printing Instructions</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Click the "Print" button above to print these tags</li>
                                <li>Use sticker paper for best results</li>
                                <li>Cut along the borders and attach to key rings</li>
                                <li>Ensure QR codes are clear and unobstructed</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .print\:hidden {
            display: none !important;
        }
        
        .print\:grid-cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        
        .break-inside-avoid {
            break-inside: avoid;
        }
        
        body {
            background: white !important;
        }
        
        .border {
            border-color: #000 !important;
        }
    }
</style>
@endpush