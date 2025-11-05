@extends('layouts.guest')

@section('title', 'Shift History')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock me-2"></i>Shift History
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('profile.show') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Profile
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($shifts) && $shifts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Duration</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shifts as $shift)
                                        <tr>
                                            <td>{{ $shift->start_at->format('M j, Y g:i A') }}</td>
                                            <td>{{ $shift->end_at ? $shift->end_at->format('M j, Y g:i A') : 'Active' }}</td>
                                            <td>
                                                @if($shift->end_at)
                                                    {{ $shift->start_at->diff($shift->end_at)->format('%h hours %i minutes') }}
                                                @else
                                                    In Progress
                                                @endif
                                            </td>
                                            <td>{{ Str::limit($shift->notes, 50) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $shifts->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Shift History</h4>
                            <p class="text-muted">You haven't worked any shifts yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection