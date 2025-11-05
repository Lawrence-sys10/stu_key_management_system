@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history me-2"></i>Activity Log
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('profile.show') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Profile
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($activity) && $activity->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Key</th>
                                        <th>Action</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activity as $log)
                                        <tr>
                                            <td>
                                                <span class="fw-bold">{{ $log->created_at->format('M j, Y') }}</span>
                                                <br>
                                                <small class="text-muted">{{ $log->created_at->format('g:i A') }}</small>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ $log->key->key_code ?? 'N/A' }}</span>
                                                <br>
                                                <small class="text-muted">{{ $log->key->description ?? '' }}</small>
                                            </td>
                                            <td>
                                                @if($log->checkout_time && !$log->checkin_time)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-sign-out-alt me-1"></i>Checked Out
                                                    </span>
                                                @elseif($log->checkin_time)
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-sign-in-alt me-1"></i>Checked In
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $log->key->location->name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                @if(isset($log->is_overdue) && $log->is_overdue)
                                                    <span class="badge bg-danger">Overdue</span>
                                                @elseif($log->checkin_time)
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-warning">Active</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $activity->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Activity Found</h4>
                            <p class="text-muted">You haven't performed any key transactions yet.</p>
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                <i class="fas fa-key me-1"></i>Go to Dashboard
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection