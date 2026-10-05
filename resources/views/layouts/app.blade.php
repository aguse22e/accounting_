<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Akuntansi - Organisasi Perempuan Mahardika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            /* purple-pink-blue gradient theme */
            --primary-color:rgb(139, 92, 246);
            --secondary-color: #5d6a7a;
            --gradient-start:#8B5CF6; /* bright purple */
            --gradient-middle:#EC4899; /* pink */
            --gradient-end:rgb(246, 59, 190);   /* bright blue */
            --bg-soft:rgb(199, 11, 171);        /* very soft white */
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(160deg, rgba(139,92,246,.08), rgba(236,72,153,.08), rgba(59,130,246,.08)), var(--bg-soft);
        }
        
        .sidebar {
            background: linear-gradient(160deg, var(--gradient-start) 0%, var(--gradient-middle) 50%, var(--gradient-end) 100%);
            min-height: 100vh;
            height: 100vh;
            color: white;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            font-weight: 500;
            font-size: 0.9rem;
            border-radius: 0.5rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            font-weight: 700;
            background-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border-left: 4px solid white;
        }
        
        .sidebar-heading {
            padding: 1rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .content-wrapper {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            padding: 0;
        }
        
        .h2, h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .card-header {
            background: linear-gradient(90deg, rgba(139,92,246,.08), rgba(236,72,153,.08), rgba(59,130,246,.08));
            border-bottom: 1px solid #e3e6f0;
        }
        
        /* Buttons - responsive and precise spacing */
        .btn {
            border-radius: .35rem;
            padding: .5rem .85rem;
            font-weight: 600;
            letter-spacing: .2px;
            font-size: 0.875rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-middle), var(--gradient-end));
            border: 0;
            box-shadow: 0 8px 20px rgba(139,92,246,.35);
        }
        .btn-outline-primary {
            color: var(--gradient-start);
            border-color: var(--gradient-start);
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-middle), var(--gradient-end));
            color: #fff;
            border-color: transparent;
        }

        /* Chip buttons for top navigasi*/
        .chip-btn {
            background: #fff;
            border: 1px solid rgba(30,60,114,.2);
            color: #122;
            border-radius: .75rem;
            padding: .45rem .9rem;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: transform .05s ease-in-out, box-shadow .2s;
            box-shadow: 0 2px 6px rgba(30,60,114,.08);
            white-space: nowrap;
        }
        .chip-btn:hover { box-shadow: 0 6px 18px rgba(139,92,246,.25); transform: translateY(-1px); }
        .chip-btn.active { background: linear-gradient(135deg, rgba(139,92,246,.12), rgba(236,72,153,.12)); border-color: rgba(139,92,246,.35); }
        .chip-group { display:flex; flex-wrap:wrap; gap:.75rem; }
        
        .table th {
            background-color: #f8f9fc;
            color: #1a1a1a;         
            font-weight: 700;       
        }
    

        /* Styling for auto-generated image placeholders */
        img.img-fallback { 
            background: #f1f3f6; 
            border-radius: 6px; 
            object-fit: contain; 
        }
        
        ::-webkit-scrollbar {
            width: 12px;                        
        }
        ::-webkit-scrollbar-track {
            background: #f1f3f6;                 
            border-radius: 10px;                 
        }
        ::-webkit-scrollbar-thumb {
            background-color: #9aa0a6;           
            border-radius: 10px;                  
            border: 2px solid #f1f3f6;          
        }
        ::-webkit-scrollbar-thumb:hover {
            background-color: #7e858d;           
        }
        /* Optional arrow buttons at top and bottom */
        ::-webkit-scrollbar-button {
            height: 16px;                        /* button height */
            background-color: transparent;       
        }
        /* Up arrow */
        ::-webkit-scrollbar-button:single-button:vertical:decrement {
            background: center/8px no-repeat url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path fill="%239aa0a6" d="M5 3 L8 7 H2 Z"/></svg>');
        }
        /* Down arrow */
        ::-webkit-scrollbar-button:single-button:vertical:increment {
            background: center/8px no-repeat url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><path fill="%239aa0a6" d="M2 3 H8 L5 7 Z"/></svg>');
        }
    </style>
    @stack('styles')
</head>
<body data-theme="light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-center py-4">
                    <h4 class="m-0">Akuntansi</h4>
                </div>
                <div class="px-3 pb-3 text-center">
                    <button id="themeToggleSidebar" class="btn btn-sm btn-outline-light w-100" type="button"></button>
                </div>
                <hr class="m-0 bg-white">
                
                <ul class="nav flex-column flex-grow-1">
                    @php 
                        $isMasterSection = request()->routeIs('master.*');
                        $isManager = auth()->check() && auth()->user()->role === 'manager';
                    @endphp
                    @if(!$isManager)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}" href="/dashboard">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                
                    
                    <div class="sidebar-heading">Master Akun</div>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('master.akun') ? 'active' : '' }}" href="{{ route('master.akun') }}">
                            <i class="bi bi-list-ul me-2"></i> Master Akun
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('master.project') ? 'active' : '' }}" href="{{ route('master.project') }}">
                            <i class="bi bi-briefcase me-2"></i> Master Project
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('master.budget') ? 'active' : '' }}" href="{{ route('master.budget') }}">
                            <i class="bi bi-cash-stack me-2"></i> Master Budget
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('transaksi.*') ? 'active' : '' }}" href="{{ route('transaksi.index') }}">
                            <i class="bi bi-journal-text me-2"></i> Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('activity-user.*') ? 'active' : '' }}" href="{{ route('activity-user.index') }}">
                            <i class="bi bi-activity me-2"></i> Activity User
                            <span class="badge bg-light text-dark ms-2" id="activity-badge" style="vertical-align: middle;">0</span>
                        </a>
                    </li>
                    @endif
                    <div class="sidebar-heading">Laporan</div>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center justify-content-between {{ request()->is('ledger*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#ledgerMenu" role="button" aria-expanded="{{ request()->is('ledger*') ? 'true' : 'false' }}" aria-controls="ledgerMenu">
                            <span><i class="bi bi-file-earmark-text me-2"></i> Ledger & Reports</span>
                            <i class="bi bi-chevron-down small ms-2"></i>
                        </a>
                        <div class="collapse {{ request()->is('ledger*') ? 'show' : '' }}" id="ledgerMenu">
                            <div class="px-3 py-2">
                                <div class="chip-group">
                                    <a class="chip-btn {{ request()->routeIs('ledger.posisi') ? 'active' : '' }}" href="{{ route('ledger.posisi') }}"><i class="bi bi-diagram-3"></i>Report</a>
                                    <a class="chip-btn {{ request()->routeIs('ledger.trial') ? 'active' : '' }}" href="{{ route('ledger.trial') }}"><i class="bi bi-calculator"></i>Neraca Saldo</a>
                                    <a class="chip-btn {{ request()->routeIs('ledger.gl') ? 'active' : '' }}" href="{{ route('ledger.gl') }}"><i class="bi bi-journals"></i>GL Detail</a>
                                    <a class="chip-btn {{ request()->routeIs('ledger.activity') ? 'active' : '' }}" href="{{ route('ledger.activity') }}"><i class="bi bi-activity"></i>Aktivitas</a>
                                    <a class="chip-btn {{ request()->routeIs('ledger.cash') ? 'active' : '' }}" href="{{ route('ledger.cash') }}"><i class="bi bi-cash-coin"></i>Arus Kas</a>
                                    <a class="chip-btn {{ request()->routeIs('ledger.fixed') ? 'active' : '' }}" href="{{ route('ledger.fixed') }}"><i class="bi bi-building"></i>Aset Tetap</a>
                                    @if(!$isManager)
                                    <a class="chip-btn {{ request()->routeIs('budget-actual.*') ? 'active' : '' }}" href="{{ route('budget-actual.index') }}"><i class="bi bi-graph-up-arrow"></i>Budget vs Actual</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                
                <!-- Social Media & Logout -->
                <div class="mt-auto p-3">
                    <div class="text-center mb-3">
                        <h6 class="text-white-50 mb-3" id="hubungi-kami" style="cursor: pointer;">Hubungi Kami</h6>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="https://wa.me/081319155058" target="_blank" class="btn btn-success btn-sm" title="WhatsApp">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                            <a href="https://instagram.com/agusekurnia" target="_blank" class="btn btn-danger btn-sm" title="Instagram">
                                <i class="bi bi-instagram"></i>
                            </a>
                            <a href="https://tiktok.com/@fatiho" target="_blank" class="btn btn-dark btn-sm" title="TikTok">
                                <i class="bi bi-tiktok"></i>
                            </a>
                        </div>
                        
                        <!-- Hidden Import File Button - tidak untuk manager -->
                        @if(!$isManager)
                        <div id="import-file-section" style="display: none;" class="mt-3">
                            <div class="alert alert-info alert-sm mb-2">
                                <small><i class="bi bi-info-circle me-1"></i>Fitur Import File</small>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="showImportModalBtn" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="bi bi-info-circle me-1"></i> Fitur Import File
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-outline-light w-100">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-2">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-2 border-bottom position-relative">
                    <h1 class="h2 m-0 position-relative">@yield('title')</h1>
                    <div class="d-flex align-items-center gap-2">
                        @yield('header-actions')
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>
                                <span>{{ auth()->user()->User_name ?? 'User' }}</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:280px;">
                                <form method="POST" action="{{ route('settings.update') }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Nama Akun</label>
                                        <input type="text" name="name" value="{{ auth()->user()->User_name ?? '' }}" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Password Baru</label>
                                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Kosongkan jika tidak ganti">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small mb-1">Konfirmasi Password</label>
                                        <input type="password" name="password_confirmation" class="form-control form-control-sm">
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Simpan</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="bootstrap.Dropdown.getOrCreateInstance(this.closest('.dropdown')).hide()"><i class="bi bi-x-lg me-1"></i>Cancel</button>
                                    </div>
                                </form>
                                
                            </div>
                        </div>
                    </div>
                </div>

                @yield('content')
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Light/Dark theme */
        /* Light: silver gradient*/
        :root { --bg:linear-gradient(180deg, #f3f4f6 0%, #e6e7eb 60%, #f7f3e3 100%); --text:#212529; --card:#ffffff; }
        /* Dark mode unchanged */
        [data-theme="dark"] { --bg:#0e0f16; --text:#e9ecef; --card:#15172a; }
        body { background: var(--bg); color: var(--text); }
        .content-wrapper, .card { background: var(--card); }
    </style>
    <script>

        // Theme persistence
        (function() {
            const saved = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', saved);
            const btnSidebar = document.getElementById('themeToggleSidebar');
            const LABEL_DARK = 'Dark';
            const LABEL_LIGHT = 'Light';
            function setLabelSidebar() {
                const isDark = document.body.getAttribute('data-theme') === 'dark';
                btnSidebar.textContent = (isDark ? LABEL_LIGHT : LABEL_DARK) + ' Mode';
            }
            setLabelSidebar();
            btnSidebar.addEventListener('click', function() {
                const current = document.body.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.body.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                setLabelSidebar();
            });
        })();
        // Scroll rail logic and precise 
        document.addEventListener('DOMContentLoaded', function() {
            // image
            const PLACEHOLDER_SVG =
                'data:image/svg+xml;utf8,' +
                encodeURIComponent(
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80">\n' +
                    ' <defs>\n' +
                    '  <linearGradient id="g" x1="0" x2="1">\n' +
                    '    <stop offset="0" stop-color="#4f46e5" stop-opacity="0.18"/>\n' +
                    '    <stop offset="1" stop-color="#7c3aed" stop-opacity="0.18"/>\n' +
                    '  </linearGradient>\n' +
                    ' </defs>\n' +
                    ' <rect width="120" height="80" rx="10" fill="url(#g)"/>\n' +
                    ' <g fill="none" stroke="#6b7280" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">\n' +
                    '  <rect x="18" y="18" width="84" height="44" rx="6"/>\n' +
                    '  <path d="M30 52l14-16 12 12 8-10 16 14H30z"/>\n' +
                    ' </g>\n' +
                    ' <circle cx="44" cy="36" r="6" fill="#9aa0a6"/>\n' +
                    '</svg>'
                );

            function applyFallback(img) {
                // Prevent infinite loop if placeholder also errors
                if (img.dataset.fallbackApplied === '1') return;
                img.dataset.fallbackApplied = '1';
                img.classList.add('img-fallback');
                // Preserve size if possible
                if (!img.getAttribute('width') && img.naturalWidth) img.setAttribute('width', img.naturalWidth);
                if (!img.getAttribute('height') && img.naturalHeight) img.setAttribute('height', img.naturalHeight);
                img.src = PLACEHOLDER_SVG;
                img.alt = img.alt || 'Placeholder';
            }
        
            document.querySelectorAll('img').forEach(function(img){
                img.addEventListener('error', function(){ applyFallback(this); });
                const src = img.getAttribute('src');
                if (!src || src === '#') applyFallback(img);
            });
        });
    </script>
    <script>
        // Update badge aktivitas secara berkala
        document.addEventListener('DOMContentLoaded', function() {
            function updateActivityBadge() {
                fetch('/api/recent-activities')
                    .then(r => r.json())
                    .then(list => {
                        const el = document.getElementById('activity-badge');
                        if (el && Array.isArray(list)) {
                            el.textContent = list.length;
                        }
                    })
                    .catch(()=>{});
            }
            updateActivityBadge();
            setInterval(updateActivityBadge, 15000);
        });
    </script>

    <!-- Import File Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">
                        <i class="bi bi-upload me-2"></i>Import File
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Import Transaksi -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-journal-text text-primary" style="font-size: 2rem;"></i>
                                    <h6 class="card-title mt-2">Import Transaksi</h6>
                                    <p class="card-text small">Import data transaksi dari file Excel/CSV</p>
                                    <form action="{{ route('ledger.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-2">
                                            <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.csv" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="bi bi-upload me-1"></i> Upload
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Import Project -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-briefcase text-info" style="font-size: 2rem;"></i>
                                    <h6 class="card-title mt-2">Import Project</h6>
                                    <p class="card-text small">Import data project dan kode project</p>
                                    <form action="{{ route('project.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-2">
                                            <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.csv" required>
                                        </div>
                                        <button type="submit" class="btn btn-info btn-sm w-100">
                                            <i class="bi bi-upload me-1"></i> Upload
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Import Master Data -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-database text-success" style="font-size: 2rem;"></i>
                                    <h6 class="card-title mt-2">Import Master Data</h6>
                                    <p class="card-text small">Import data master akun dan chart of accounts</p>
                                    <form action="{{ route('master.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-2">
                                            <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.csv" required>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="bi bi-upload me-1"></i> Upload
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Import Budget -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-wallet2 text-warning" style="font-size: 2rem;"></i>
                                    <h6 class="card-title mt-2">Import Budget</h6>
                                    <p class="card-text small">Import data budget dan perencanaan keuangan</p>
                                    <form action="{{ route('budget.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-2">
                                            <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.csv" required>
                                        </div>
                                        <button type="submit" class="btn btn-warning btn-sm w-100">
                                            <i class="bi bi-upload me-1"></i> Upload
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Format File:</strong> Excel (.xlsx) atau CSV (.csv)<br>
                            <strong>Ukuran Maksimal:</strong> 10MB<br>
                            <strong>Template:</strong> Download template dari masing-masing menu
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let clickCount = 0;
            const hubungiKami = document.getElementById('hubungi-kami');
            const importSection = document.getElementById('import-file-section');
            const showImportModalBtn = document.getElementById('showImportModalBtn');
            
            // Reset counter setiap 10 detik
            setInterval(function() {
                clickCount = 0;
            }, 10000);
            
            hubungiKami.addEventListener('click', function() {
                clickCount++;
                console.log('Klik ke:', clickCount);
                
                // Animasi visual feedback
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
                
                if (clickCount >= 5) {
                    // Tampilkan import section dengan animasi
                    importSection.style.display = 'block';
                    importSection.style.opacity = '0';
                    importSection.style.transform = 'translateY(-10px)';
                    
                    setTimeout(() => {
                        importSection.style.transition = 'all 0.3s ease';
                        importSection.style.opacity = '1';
                        importSection.style.transform = 'translateY(0)';
                    }, 100);
                    
                    // Reset counter
                    clickCount = 0;
                    
                    // Tampilkan notifikasi
                    if (typeof bootstrap !== 'undefined') {
                        const toast = document.createElement('div');
                        toast.className = 'toast position-fixed top-0 end-0 m-3';
                        toast.innerHTML = `
                            <div class="toast-header">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <strong class="me-auto">Import File</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                            </div>
                            <div class="toast-body">
                                Features enabled!
                            </div>
                        `;
                        document.body.appendChild(toast);
                        const bsToast = new bootstrap.Toast(toast);
                        bsToast.show();
                        
                        // Hapus toast setelah 3 detik
                        setTimeout(() => {
                            toast.remove();
                        }, 3000);
                    }
                }
            });
            
            // Event listener hidding button setelah diklik
            if (showImportModalBtn) {
                showImportModalBtn.addEventListener('click', function() {
                    // Animasi fade out sebelum menyembunyikan
                    importSection.style.transition = 'all 0.3s ease';
                    importSection.style.opacity = '0';
                    importSection.style.transform = 'translateY(-10px)';
                    
                    // hidding setelah animasi selesai
                    setTimeout(() => {
                        importSection.style.display = 'none';
                        // Reset opacity dan transform untuk animasi
                        importSection.style.opacity = '0';
                        importSection.style.transform = 'translateY(-10px)';
                    }, 300);
                    
                    // Tampilkan notifikasi bahwa button telah disembunyikan
                    if (typeof bootstrap !== 'undefined') {
                        const toast = document.createElement('div');
                        toast.className = 'toast position-fixed top-0 end-0 m-3';
                        toast.innerHTML = `
                            <div class="toast-header">
                                <i class="bi bi-eye-slash text-warning me-2"></i>
                                <strong class="me-auto">Import File</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                            </div>
                            <div class="toast-body">
                                Button Import File telah disembunyikan. Klik 5x "Hubungi Kami" untuk mengaktifkan kembali.
                            </div>
                        `;
                        document.body.appendChild(toast);
                        const bsToast = new bootstrap.Toast(toast);
                        bsToast.show();
                        
                        // Hapus toast setelah 4 detik
                        setTimeout(() => {
                            toast.remove();
                        }, 4000);
                    }
                });
            }
        });
    </script>
    
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>