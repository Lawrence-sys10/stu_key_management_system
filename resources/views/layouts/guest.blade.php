<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Student Key Management') }}</title>

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                font-family: 'Figtree', sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center; 
            }
            .auth-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            .auth-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 20px 20px 0 0;
                padding: 2rem;
                text-align: center;
                margin: -2rem -2rem 2rem -2rem;
            }
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                padding: 12px 30px;
                font-weight: 600;
                border-radius: 10px;
                transition: all 0.3s ease;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            }
            .form-control {
                border-radius: 10px;
                border: 2px solid #e2e8f0;
                padding: 12px 15px;
                transition: all 0.3s ease;
            }
            .form-control:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            .input-group-text {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px 0 0 10px;
            }
            .logo-container {
                background: white;
                border-radius: 50%;
                padding: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                margin-bottom: 1rem;
            }
            .auth-card {
                border: none;
                border-radius: 20px;
            }
            .text-gradient {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="logo-container text-center">
                        <a href="/">
                            <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                        </a>
                    </div>

                    <div class="auth-container">
                        <div class="auth-header">
                            <h2 class="text-2xl font-bold mb-2">
                                <i class="fas fa-key me-2"></i>Student Key Management
                            </h2>
                            <p class="opacity-90 mb-0">@yield('auth-title', 'Welcome')</p>
                        </div>
                        
                        <div class="p-4">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Additional Scripts -->
        <script>
            // Add smooth animations
            document.addEventListener('DOMContentLoaded', function() {
                const inputs = document.querySelectorAll('.form-control');
                inputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        this.parentElement.classList.add('focused');
                    });
                    input.addEventListener('blur', function() {
                        if (!this.value) {
                            this.parentElement.classList.remove('focused');
                        }
                    });
                });
            });
        </script>
    </body>
</html>