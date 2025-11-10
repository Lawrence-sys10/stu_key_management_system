<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - STU Key Management</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .offline-container {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
        }
        .icon {
            font-size: 4rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="icon">📶</div>
        <h1>You're Offline</h1>
        <p>The STU Key Management system requires an internet connection. Please check your connection and try again.</p>
        <button class="btn" onclick="window.location.reload()">Try Again</button>
    </div>
</body>
</html>
