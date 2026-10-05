<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Organisasi Mahardika - Sistem Akuntansi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(90deg,rgb(233, 14, 189) 0%, #7c3aed 100%);
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(90deg,rgb(178, 8, 178) 0%, #7c3aed 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .hero-section {
            background: linear-gradient(90deg,rgb(239, 83, 221) 0%, #7c3aed 100%);
            color: white;
            padding: 120px 0;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-30px, -30px) rotate(5deg); }
        }
        .feature-box {
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            background-color: white;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .footer {
            background: linear-gradient(90deg,rgb(181, 68, 178) 0%,rgb(79, 20, 118) 100%);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        .btn-light {
            background-color: white;
            color: #7c3aed;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-light:hover {
            background-color: #f3f4f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .nav-link {
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        h1.display-4 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 1s ease;
        }
        .lead {
            animation: fadeInUp 1s ease 0.2s backwards;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Organisasi Mahardika</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Beranda</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang Kami</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontak</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-light btn-sm px-3" href="{{ route('login') }}">Login</a>
                    </li>
                
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center">
        <div class="container">
            @if (session('error'))
            <div class="alert alert-danger mb-4">{{ session('error') }}</div>
            @endif
            <h1 class="display-4 fw-bold">Selamat Datang di Organisasi Mahardika</h1>
            <p class="lead">Sistem Akuntansi Terpadu untuk Pengelolaan Keuangan yang Transparan dan Akurat</p>
            
        </div>
    </section>

    <section class="container mb-5" id="about">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-box">
                    <h3 class="h5 mb-3">Tentang Kami</h3>
                    <p>Sistem Akuntansi Organisasi Perempuan Mahardika membantu pencatatan, pengelolaan, dan pelaporan keuangan secara sederhana dan akurat.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box">
                    <h3 class="h5 mb-3">Lokasi</h3>
                    <p>Alamat kantor: Rawamangun, Kedondong II no39, Jakarta Timur.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-box">
                    <h3 class="h5 mb-3">Kontak</h3>
                    <p id="contact" class="mb-1"><i class="bi bi-envelope me-2"></i>mahardhika.org</p>
                    <p class="mb-1"><i class="bi bi-telephone me-2"></i>+62 813-8872-5150</p>
                    <a href="https://wa.me/081319155058" target="_blank" class="btn btn-success btn-sm mt-2"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2025 Organisasi Mahardika. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
