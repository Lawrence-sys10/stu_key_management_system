<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile - Key Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ADD THIS LINE -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-key"></i> Key Management
            </a>
            <a href="{{ route('profile.show') }}" class="text-white">Back to Profile</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Edit Profile - TEST PAGE</h1>
        
        <!-- Debug info -->
        <div class="alert alert-info">
            <h4>Debug Information:</h4>
            <p>User ID: {{ $user->id ?? 'NO USER' }}</p>
            <p>User Name: {{ $user->name ?? 'NO NAME' }}</p>
            <p>Route: {{ request()->url() }}</p>
        </div>

        @if($user)
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="{{ route('profile.show') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
        @else
        <div class="alert alert-danger">
            <h4>Error: No user data available!</h4>
            <p>This means the controller is not passing the $user variable to the view.</p>
        </div>
        @endif
    </div>

    <!-- Add Bootstrap JS for better functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>