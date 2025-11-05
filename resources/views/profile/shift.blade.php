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
                    <!-- Add shift history content here -->
                    <div class="text-center py-5">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Shift History</h4>
                        <p class="text-muted">Your shift history will appear here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection