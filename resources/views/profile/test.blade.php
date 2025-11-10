<!DOCTYPE html>
<html>
<head>
    <title>Test Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Test Profile Page</h1>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">User Information</h2>
            <p><strong>Name:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>ID:</strong> {{ $user->id }}</p>
            
            <div class="mt-4">
                <a href="{{ route('profile.edit') }}" class="bg-blue-500 text-white px-4 py-2 rounded">
                    Edit Profile
                </a>
            </div>
        </div>
    </div>
</body>
</html>