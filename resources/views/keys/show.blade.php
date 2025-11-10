@extends('layouts.app')

@section('title', 'Key Details - ' . $key->label)

@section('subtitle', 'Key details and history')

@section('actions')
    <div class="flex space-x-2">
        <a href="{{ route('keys.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to Keys
        </a>
        @can('manage keys')
            <a href="{{ route('keys.edit', $key) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                <i class="fas fa-edit mr-2"></i> Edit Key
            </a>
        @endcan
        @can('access kiosk')
            @if($key->isAvailable())
                <a href="{{ route('kiosk.checkout', $key) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-arrow-right mr-2"></i> Check Out
                </a>
            @elseif($key->isCheckedOut())
                <a href="{{ route('kiosk.checkin', $key) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
                    <i class="fas fa-arrow-left mr-2"></i> Check In
                </a>
            @endif
        @endcan
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Key Information -->
    <div class="lg:col-span-1">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Key Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Key Code</label>
                        <p class="mt-1 text-sm text-gray-900 font-mono">{{ $key->code }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Label</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $key->label }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Description</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $key->description ?? 'No description' }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Type</label>
                        <p class="mt-1 text-sm text-gray-900 capitalize">{{ $key->key_type }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Location</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $key->location->name }}</p>
                        @if($key->location->campus)
                            <p class="text-sm text-gray-500">{{ $key->location->campus }}</p>
                        @endif
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        @php
                            $statusClasses = [
                                'available' => 'bg-green-100 text-green-800',
                                'checked_out' => 'bg-orange-100 text-orange-800',
                                'lost' => 'bg-red-100 text-red-800',
                                'maintenance' => 'bg-yellow-100 text-yellow-800'
                            ];
                            $statusClass = $statusClasses[$key->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }} mt-1">
                            {{ ucfirst(str_replace('_', ' ', $key->status)) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Holder Information -->
        @if($key->isCheckedOut() && $currentLog)
        <div class="bg-white shadow rounded-lg mt-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Current Holder</h3>
                
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Name</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $currentLog->holder_name }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Phone</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $currentLog->holder_phone ?? 'Not provided' }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Type</label>
                        <p class="mt-1 text-sm text-gray-900 capitalize">{{ $currentLog->holder_type }}</p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Checked Out</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $currentLog->created_at->format('M j, Y g:i A') }}</p>
                        <p class="text-sm text-gray-500">({{ $currentLog->created_at->diffForHumans() }})</p>
                    </div>
                    
                    @if($currentLog->expected_return_at)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Expected Return</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $currentLog->expected_return_at->format('M j, Y g:i A') }}</p>
                        <p class="text-sm text-gray-500">({{ $currentLog->expected_return_at->diffForHumans() }})</p>
                    </div>
                    @endif
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Issued By</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $currentLog->receiver_name }}</p>
                    </div>
                </div>
                
                @can('manage keys')
                    @if($key->isCheckedOut())
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <form action="{{ route('keys.mark-lost', $key) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700"
                                    onclick="return confirm('Are you sure you want to mark this key as lost? This action cannot be undone.')">
                                <i class="fas fa-exclamation-triangle mr-2"></i> Mark as Lost
                            </button>
                        </form>
                    </div>
                    @endif
                @endcan
            </div>
        </div>
        @endif

        <!-- QR Tags -->
        @if($key->keyTags->count() > 0)
        <div class="bg-white shadow rounded-lg mt-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">QR Tags</h3>
                
                <div class="space-y-2">
                    @foreach($key->keyTags->where('is_active', true) as $tag)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Tag #{{ $loop->iteration }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $tag->uuid }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                    </div>
                    @endforeach
                </div>
                
                @can('manage keys')
                <div class="mt-4">
                    <a href="{{ route('keys.print-tags', $key) }}" 
                       class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-print mr-2"></i> Print QR Tags
                    </a>
                </div>
                @endcan
            </div>
        </div>
        @endif
    </div>

    <!-- Key History -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Key History</h3>
                
                @if($history->count() > 0)
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($history as $log)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            @if($log->action === 'checkout')
                                                <span class="h-8 w-8 rounded-full bg-orange-500 flex items-center justify-center ring-8 ring-white">
                                                    <i class="fas fa-arrow-right text-white text-xs"></i>
                                                </span>
                                            @elseif($log->action === 'checkin')
                                                <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                    <i class="fas fa-arrow-left text-white text-xs"></i>
                                                </span>
                                            @elseif($log->action === 'lost')
                                                <span class="h-8 w-8 rounded-full bg-red-500 flex items-center justify-center ring-8 ring-white">
                                                    <i class="fas fa-exclamation-triangle text-white text-xs"></i>
                                                </span>
                                            @else
                                                <span class="h-8 w-8 rounded-full bg-gray-500 flex items-center justify-center ring-8 ring-white">
                                                    <i class="fas fa-key text-white text-xs"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-gray-700">
                                                    @if($log->action === 'checkout')
                                                        Key checked out to 
                                                        <span class="font-medium">{{ $log->holder_name }}</span>
                                                    @elseif($log->action === 'checkin')
                                                        Key checked in from 
                                                        <span class="font-medium">{{ $log->holder_name }}</span>
                                                    @elseif($log->action === 'lost')
                                                        Key marked as lost from 
                                                        <span class="font-medium">{{ $log->holder_name }}</span>
                                                    @else
                                                        {{ ucfirst($log->action) }} - {{ $log->holder_name }}
                                                    @endif
                                                </p>
                                                @if($log->notes)
                                                    <p class="text-sm text-gray-500 mt-1">{{ $log->notes }}</p>
                                                @endif
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Processed by {{ $log->receiver_name }}
                                                </p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                <time datetime="{{ $log->created_at->toISOString() }}">
                                                    {{ $log->created_at->diffForHumans() }}
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Pagination -->
                    @if($history->hasPages())
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            {{ $history->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-history text-3xl text-gray-300 mb-2"></i>
                        <p class="text-sm text-gray-500">No history available for this key.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection