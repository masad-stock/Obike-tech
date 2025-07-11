<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Obike Tech System'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    @feature('mobile-optimized-ui')
    <!-- Mobile-optimized UI styles -->
    <style>
        @media (max-width: 768px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .btn-mobile-full {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .mobile-stack {
                flex-direction: column;
            }
            
            .mobile-stack > * {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .mobile-compact-form .form-group {
                margin-bottom: 10px;
            }
            
            .mobile-compact-form label {
                font-size: 0.9rem;
                margin-bottom: 2px;
            }
            
            .mobile-nav-large {
                font-size: 1.2rem;
                padding: 12px 0;
            }
            
            .mobile-search {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1030;
                background: white;
                padding: 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                transform: translateY(-100%);
                transition: transform 0.3s ease;
            }
            
            .mobile-search.active {
                transform: translateY(0);
            }
            
            .mobile-bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                display: flex;
                justify-content: space-around;
                padding: 10px 0;
                box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
                z-index: 1020;
            }
            
            .mobile-bottom-nav a {
                text-align: center;
                color: #6c757d;
                text-decoration: none;
                font-size: 0.8rem;
            }
            
            .mobile-bottom-nav a.active {
                color: #0d6efd;
            }
            
            .mobile-bottom-nav i {
                display: block;
                font-size: 1.2rem;
                margin-bottom: 2px;
            }
            
            body.has-mobile-nav {
                padding-bottom: 60px;
            }
        }
    </style>
    @endfeature

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="font-sans antialiased @feature('mobile-optimized-ui') has-mobile-nav @endfeature">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        <header class="bg-white shadow">
            <div class="container py-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    @yield('header')
                </h2>
            </div>
        </header>

        <!-- Page Content -->
        <main class="py-4">
            <div class="container">
                @include('layouts.alerts')
                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white shadow mt-auto py-3">
            <div class="container">
                <div class="text-center text-muted">
                    &copy; {{ date('Y') }} Obike Tech System. All rights reserved.
                </div>
            </div>
        </footer>
        
        @feature('mobile-optimized-ui')
        <!-- Mobile Bottom Navigation -->
        <nav class="mobile-bottom-nav d-md-none">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('rentals.index') }}" class="{{ request()->routeIs('rentals.*') ? 'active' : '' }}">
                <i class="fas fa-truck"></i>
                <span>Rentals</span>
            </a>
            <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
            <a href="{{ route('procurement.index') }}" class="{{ request()->routeIs('procurement.*') ? 'active' : '' }}">
                <i class="fas fa-shopping-cart"></i>
                <span>Procurement</span>
            </a>
            <a href="#" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
                <span>More</span>
            </a>
        </nav>
        
        <!-- Mobile Menu Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mobileMenuToggle = document.getElementById('mobileMenuToggle');
                const navbarCollapse = document.getElementById('navbarSupportedContent');
                
                if (mobileMenuToggle && navbarCollapse) {
                    mobileMenuToggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.toggle();
                    });
                }
            });
        </script>
        @endfeature
    </div>
    
    @stack('scripts')
</body>
</html>
