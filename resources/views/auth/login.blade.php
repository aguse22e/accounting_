<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3c72 0%, #7b2cbf 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            width: 100%;
            max-width: 460px;
            border-radius: .75rem;
            overflow: hidden;
            box-shadow: 0 1rem 2.5rem rgba(0,0,0,.15);
        }
        .auth-header {
            background: linear-gradient(90deg, rgba(30,60,114,.06), rgba(42,157,143,.06), rgba(123,44,191,.06));
        }
        .brand-title {
            font-weight: 800;
            letter-spacing: .2px;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #1e3c72, #7b2cbf);
            border: 0;
        }
        .btn-gradient:hover { filter: brightness(1.05); }
    </style>
    <script>
        // Prevent accidental navigation to other pages from this standalone screen
        document.addEventListener('click', function(e){
            const el = e.target.closest('a');
            if (!el) return;
            const href = el.getAttribute('href') || '';
            if (href && href !== '#' && !href.startsWith('http')) {
                if (!el.classList.contains('allow-nav')) {
                    e.preventDefault();
                }
            }
        });
    </script>
    <script>
        //  avoid SSL errors
        (function(){
            try {
                if (location.protocol === 'https:') {
                    var httpUrl = 'http://' + location.host + location.pathname + location.search + location.hash;
                    location.replace(httpUrl);
                }
            } catch(_) {}
        })();
    </script>
    <script>
        // Allow submit even if email input has minor typo (browser validation)
        function relaxEmailValidation(){
            var emailInput = document.getElementById('email');
            if (emailInput) { emailInput.setAttribute('inputmode', 'email'); emailInput.removeAttribute('pattern'); }
        }
        document.addEventListener('DOMContentLoaded', relaxEmailValidation);
    </script>
</head>
<body>
    <div class="auth-card bg-white">
        <div class="auth-header p-4 border-bottom text-center">
            <p class="mb-2 fs-4 fw-semibold">Organisasi Perempuan Mahardhika</p>
            <h4 class="m-0 brand-title">Masuk ke Sistem</h4>
        </div>
        <div class="p-4">
            @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Klik ikon untuk melihat/menutup password.</div>
                </div>
                <button type="submit" class="btn btn-gradient btn-primary w-100">Masuk</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            var btn = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            if (btn && input) {
                btn.addEventListener('click', function(){
                    var isText = input.getAttribute('type') === 'text';
                    input.setAttribute('type', isText ? 'password' : 'text');
                    var icon = this.querySelector('i');
                    if (icon) { icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash'; }
                });
            }
        })();
    </script>
</body>
</html>
