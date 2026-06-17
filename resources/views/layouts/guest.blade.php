<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Novelio Technologies LLC')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: radial-gradient(ellipse at 60% 40%, rgba(123,47,247,0.22) 0%, transparent 55%), radial-gradient(ellipse at 20% 80%, rgba(0,212,255,0.15) 0%, transparent 50%), #0a0b1a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { background: rgba(255,255,255,0.06); backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; }
        .brand-logo img { max-width: 190px; height: auto; filter: drop-shadow(0 2px 8px rgba(0,212,255,0.25)); }
        .brand-tagline { font-size: 0.78rem; color: rgba(255,255,255,0.45); margin-top: 4px; letter-spacing: 0.05em; }
        .btn-primary { background: linear-gradient(135deg, #7b2ff7, #00d4ff); border: none; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .form-control, .form-select { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #fff; }
        .form-control:focus, .form-select:focus { background: rgba(255,255,255,0.1); border-color: #7b2ff7; color: #fff; box-shadow: 0 0 0 0.2rem rgba(123,47,247,0.25); }
        .form-control::placeholder { color: rgba(255,255,255,0.4); }
        label { color: rgba(255,255,255,0.8); }
        a { color: #00d4ff; }
        a:hover { color: #fff; }
        .text-muted { color: rgba(255,255,255,0.5) !important; }
        .alert-danger { background: rgba(220,53,69,0.2); border-color: rgba(220,53,69,0.4); color: #ff8a9a; }
        .alert-success { background: rgba(25,135,84,0.2); border-color: rgba(25,135,84,0.4); color: #6feaaa; }
        .invalid-feedback { color: #ff8a9a; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="brand-logo text-decoration-none d-inline-block">
                        <img src="{{ asset('images/novelio-logo.webp') }}" alt="Novelio Technologies LLC">
                    </a>
                    <div class="brand-tagline">Email Validator Pro</div>
                </div>
                <div class="auth-card p-4 p-md-5">
                    @yield('content')
                </div>
                @yield('below-card')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
