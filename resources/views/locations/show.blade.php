@extends('layouts.app')

@section('title', $location->name)

@section('subtitle', 'Location Details')

@section('actions')
    <div class="flex space-x-2">
        <a href="{{ route('locations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Locations</span>
        </a>
        @can('manage locations')
        <a href="{{ route('locations.edit', $location) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
            <i class="fas fa-edit"></i>
            <span>Edit Location</span>
        </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Location Details Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Location Information</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Location Name</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $location->name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                        <p class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $location->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $location->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Campus</h3>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $location->campus }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Building</h3>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $location->building }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Room</h3>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $location->room ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Keys</h3>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $location->keys->count() }}</p>
                    </div>
                </div>
                
                @if($location->description)
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</h3>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $location->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Keys Assigned to Location -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Keys at this Location</h2>
                <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-sm px-3 py-1 rounded-full">
                    {{ $keys->total() }} keys
                </span>
            </div>
            
            @if($keys->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Key Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Current Holder</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($keys as $key)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('keys.show', $key) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    {{ $key->key_code }}
                                </a>
                                @if($key->keyTags->count() > 0)
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($key->keyTags as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $tag->name }}
                                    </span>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $key->status === 'available' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $key->status === 'checked_out' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                    {{ $key->status === 'lost' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                    {{ ucfirst($key->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($key->currentHolder)
                                    {{ $key->currentHolder->name }}
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Not assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $key->updated_at->diffForHumans() }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($keys->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                {{ $keys->links() }}
            </div>
            @endif

            @else
            <div class="p-8 text-center">
                <i class="fas fa-key text-4xl text-gray-400 mb-4"></i>
                <p class="text-lg font-medium text-gray-900 dark:text-white">No keys assigned to this location</p>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Keys will appear here once they are assigned to this location.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Key Statistics</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Keys</span>
                    <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $location->keys->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Available</span>
                    <span class="text-lg font-semibold text-green-600 dark:text-green-400">
                        {{ $location->keys->where('status', 'available')->count() }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Collected</span>
                    <span class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">
                        {{ $location->keys->where('status', 'checked_out')->count() }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Lost</span>
                    <span class="text-lg font-semibold text-red-600 dark:text-red-400">
                        {{ $location->keys->where('status', 'lost')->count() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Location Actions -->
        @can('manage locations')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Location Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('locations.edit', $location) }}" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Location
                </a>
                
                @if($location->keys->count() === 0)
                <form action="{{ route('locations.destroy', $location) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this location? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full flex items-center justify-center px-4 py-2 border border-red-300 dark:border-red-600 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-900 transition-colors duration-200">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Location
                    </button>
                </form>
                @else
                <button disabled
                        class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 rounded-lg bg-gray-50 dark:bg-gray-700 cursor-not-allowed"
                        title="Cannot delete location with assigned keys">
                    <i class="fas fa-trash mr-2"></i>
                    Delete Location
                </button>
                @endif
            </div>
        </div>
        @endcan

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h3>
            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                    Activity logging coming soon...
                </p>
            </div>
        </div>
    </div>
</div>
@endsection