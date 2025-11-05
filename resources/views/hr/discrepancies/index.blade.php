@extends('layouts.app')

@section('title', 'Key Discrepancies')

@section('subtitle', 'Manage and resolve key transaction discrepancies')

@section('actions')
    <div class="flex space-x-2">
        <form action="{{ route('hr.discrepancies.bulk-resolve') }}" method="POST" id="bulk-resolve-form" class="hidden">
            @csrf
            <input type="hidden" name="log_ids" id="bulk-log-ids">
            <input type="hidden" name="action" id="bulk-action">
        </form>
        
        <button type="button" 
                id="bulk-verify-btn" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 hidden">
            <i class="fas fa-check-circle mr-2"></i> Bulk Verify
        </button>
        
        <button type="button" 
                id="bulk-reject-btn" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 hidden">
            <i class="fas fa-times-circle mr-2"></i> Bulk Reject
        </button>
        
        <button type="button" 
                id="clear-selection-btn" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 hidden">
            <i class="fas fa-times mr-2"></i> Clear Selection
        </button>
    </div>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg">
    <!-- Filters and Bulk Actions Info -->
    <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Pending Discrepancies</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Review and resolve key transaction discrepancies that require HR verification.
                </p>
            </div>
            <div class="flex items-center justify-end">
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <span class="font-medium">{{ $discrepancies->total() }}</span> discrepancies pending review
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Discrepancies Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Key & Location
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Transaction
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Holder Information
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Discrepancy Details
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date & Time
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($discrepancies as $log)
                    <tr class="hover:bg-gray-50 discrepancy-row" data-log-id="{{ $log->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" 
                                   class="log-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                   value="{{ $log->id }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-key text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $log->key->label ?? 'N/A' }}</div>
                                    <div class="text-sm text-gray-500">{{ $log->key->code ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-400">{{ $log->key->location->name ?? 'No location' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 capitalize">{{ $log->action }}</div>
                            <div class="text-sm text-gray-500">By {{ $log->receiver->name ?? 'Unknown' }}</div>
                            @if($log->expected_return_at)
                                <div class="text-xs {{ $log->expected_return_at->isPast() ? 'text-red-500' : 'text-green-500' }}">
                                    Due: {{ $log->expected_return_at->format('M j, Y') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $log->holder_name }}</div>
                            <div class="text-sm text-gray-500">{{ $log->holder_phone ?? 'No phone' }}</div>
                            <div class="text-xs text-gray-400 capitalize">{{ $log->holder_type }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                @if($log->discrepancy_reason)
                                    {{ $log->discrepancy_reason }}
                                @else
                                    <span class="text-yellow-600">Requires verification</span>
                                @endif
                            </div>
                            @if($log->notes)
                                <div class="text-xs text-gray-500 mt-1">{{ Str::limit($log->notes, 100) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->format('M j, Y') }}<br>
                            <span class="text-gray-400">{{ $log->created_at->format('g:i A') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <form action="{{ route('hr.discrepancies.resolve', $log) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="verify">
                                    <button type="submit" 
                                            class="text-green-600 hover:text-green-900"
                                            title="Verify and resolve"
                                            onclick="return confirm('Verify this transaction as correct?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <button type="button" 
                                        class="text-red-600 hover:text-red-900 reject-btn"
                                        title="Reject discrepancy"
                                        data-log-id="{{ $log->id }}"
                                        data-holder-name="{{ $log->holder_name }}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            <i class="fas fa-check-circle text-3xl text-green-300 mb-2 block"></i>
                            No pending discrepancies found.
                            <p class="text-sm text-gray-400 mt-1">All transactions have been verified.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($discrepancies->hasPages())
        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
            {{ $discrepancies->links() }}
        </div>
    @endif
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900">Reject Discrepancy</h3>
            <form id="reject-form" method="POST">
                @csrf
                <input type="hidden" name="action" value="reject">
                <input type="hidden" id="reject-log-id" name="log_id">
                
                <div class="mt-4">
                    <label for="reject-notes" class="block text-sm font-medium text-gray-700">Reason for rejection</label>
                    <textarea name="notes" id="reject-notes" rows="3" 
                              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Explain why this transaction is being rejected..."
                              required></textarea>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" 
                            id="cancel-reject" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-times mr-2"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.log-checkbox');
    const bulkVerifyBtn = document.getElementById('bulk-verify-btn');
    const bulkRejectBtn = document.getElementById('bulk-reject-btn');
    const clearSelectionBtn = document.getElementById('clear-selection-btn');
    const bulkResolveForm = document.getElementById('bulk-resolve-form');
    const bulkLogIds = document.getElementById('bulk-log-ids');
    const bulkAction = document.getElementById('bulk-action');
    
    const rejectModal = document.getElementById('reject-modal');
    const rejectForm = document.getElementById('reject-form');
    const rejectLogId = document.getElementById('reject-log-id');
    const rejectNotes = document.getElementById('reject-notes');
    const cancelReject = document.getElementById('cancel-reject');
    
    // Select All functionality
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
        updateBulkActions();
    });
    
    // Individual checkbox functionality
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    // Update bulk action buttons
    function updateBulkActions() {
        const selectedCount = document.querySelectorAll('.log-checkbox:checked').length;
        
        if (selectedCount > 0) {
            bulkVerifyBtn.classList.remove('hidden');
            bulkRejectBtn.classList.remove('hidden');
            clearSelectionBtn.classList.remove('hidden');
            bulkVerifyBtn.innerHTML = `<i class="fas fa-check-circle mr-2"></i> Verify ${selectedCount}`;
            bulkRejectBtn.innerHTML = `<i class="fas fa-times-circle mr-2"></i> Reject ${selectedCount}`;
        } else {
            bulkVerifyBtn.classList.add('hidden');
            bulkRejectBtn.classList.add('hidden');
            clearSelectionBtn.classList.add('hidden');
        }
        
        // Update select all checkbox
        selectAll.checked = selectedCount === checkboxes.length && checkboxes.length > 0;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
    }
    
    // Bulk verify
    bulkVerifyBtn.addEventListener('click', function() {
        if (confirm(`Verify ${getSelectedCount()} selected discrepancies?`)) {
            bulkLogIds.value = getSelectedIds();
            bulkAction.value = 'verify';
            bulkResolveForm.submit();
        }
    });
    
    // Bulk reject
    bulkRejectBtn.addEventListener('click', function() {
        if (confirm(`Reject ${getSelectedCount()} selected discrepancies? They will be marked as rejected without specific reasons.`)) {
            bulkLogIds.value = getSelectedIds();
            bulkAction.value = 'reject';
            bulkResolveForm.submit();
        }
    });
    
    // Clear selection
    clearSelectionBtn.addEventListener('click', function() {
        checkboxes.forEach(checkbox => checkbox.checked = false);
        selectAll.checked = false;
        updateBulkActions();
    });
    
    // Individual reject buttons
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const logId = this.getAttribute('data-log-id');
            const holderName = this.getAttribute('data-holder-name');
            
            rejectLogId.value = logId;
            rejectForm.action = `/hr/discrepancies/${logId}/resolve`;
            rejectNotes.placeholder = `Reason for rejecting transaction with ${holderName}...`;
            rejectModal.classList.remove('hidden');
        });
    });
    
    // Cancel reject
    cancelReject.addEventListener('click', function() {
        rejectModal.classList.add('hidden');
        rejectNotes.value = '';
    });
    
    // Close modal on outside click
    window.addEventListener('click', function(event) {
        if (event.target === rejectModal) {
            rejectModal.classList.add('hidden');
            rejectNotes.value = '';
        }
    });
    
    // Helper functions
    function getSelectedCount() {
        return document.querySelectorAll('.log-checkbox:checked').length;
    }
    
    function getSelectedIds() {
        const selected = [];
        document.querySelectorAll('.log-checkbox:checked').forEach(checkbox => {
            selected.push(checkbox.value);
        });
        return selected.join(',');
    }
});
</script>
@endpush