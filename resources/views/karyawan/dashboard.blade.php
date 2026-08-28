<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Beranda') | Absenly</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer" />

    {{-- CSS Layout Absensi --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- CSS dropdown avatar (Profil & Keluar) --}}
    <style>
        .tb-avatar-wrap { position: relative; }
        .tb-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: rgba(255,255,255,.25); color: #fff;
            border: none; font-family: 'Poppins', sans-serif;
            font-weight: 700; font-size: 13px; cursor: pointer;
        }
        .tb-avatar-menu {
            display: none;
            position: absolute; right: 0; top: calc(100% + 10px);
            background: #fff; border-radius: 12px;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,.25);
            min-width: 190px; overflow: hidden; z-index: 50;
            font-family: 'Poppins', sans-serif;
        }
        .tb-avatar-menu.open { display: block; }
        .tb-avatar-menu-header {
            padding: 12px 14px; border-bottom: 1px solid #EDEEF0;
        }
        .tb-avatar-menu-name { font-size: 13px; font-weight: 700; color: #22262B; }
        .tb-avatar-menu-role { font-size: 11px; color: #9AA0A8; text-transform: capitalize; margin-top: 2px; }
        .tb-avatar-menu-item {
            display: flex; align-items: center; gap: 8px;
            width: 100%; text-align: left; background: none; border: none;
            padding: 10px 14px; font-size: 12.5px; color: #22262B;
            text-decoration: none; cursor: pointer; font-family: 'Poppins', sans-serif;
        }
        .tb-avatar-menu-item:hover { background: #F7F8FA; }
        .tb-avatar-menu-danger { color: #D34D3C; }
    </style>

    {{-- CSS Tambahan dari halaman --}}
    @stack('styles')
</head>

<body data-role="karyawan">

<div class="app" id="absensiApp">

    {{-- =====================================================
        SIDEBAR (KARYAWAN)
    ====================================================== --}}
    <aside class="sidebar">

        {{-- BRAND --}}
        <div class="brand">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Haoyou" class="logo">
        </div>

        {{-- NAVIGATION --}}
        <nav class="navlist">

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house nav-ico"></i>
            Beranda
        </a>

            {{-- ===== KEHADIRAN (SUBMENU) ===== --}}
            <div class="nav-group {{ request()->routeIs(['presensi', 'kunjungan-klien-saya']) ? 'open' : '' }}" id="navKehadiran">
                <div class="nav-item" onclick="toggleGroup()">
                    <i class="fa-solid fa-clock nav-ico"></i>
                    Kehadiran
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>

                <div class="nav-sub">
                    <a href="{{ route('presensi') }}" class="nav-sub-item {{ request()->routeIs('presensi') ? 'active' : '' }}">
                        <span class="nav-sub-ico"><i class="fa-solid fa-clipboard-check"></i></span>
                        <span class="nav-sub-label">Presensi</span>
                    <a href="{{ route('kunjungan-klien-saya') }}" class="nav-sub-item {{ request()->routeIs('kunjungan-klien-saya') ? 'active' : '' }}">
                    </a>
                    <a href="{{ route('leave-requests.index') }}" class="nav-sub-item {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                    <span class="nav-sub-ico"><i class="fa-solid fa-plane-departure"></i></span>
                    <span class="nav-sub-label">Izin &amp; Cuti</span>
                    </a>

                        <span class="nav-sub-ico"><i class="fa-solid fa-map-location-dot"></i></span>
                        <span class="nav-sub-label">Kunjungan Klien</span>
                    </a>
                </div>
            </div>
            
            <a href="{{ route('payslips.index') }}" class="nav-item {{ request()->routeIs('payslips.*') ? 'active' : '' }}">
            <i class="fa-solid fa-sack-dollar nav-ico"></i>
            Gaji Saya
            </a>

            <a href="#" class="nav-item" data-page="faq">
                <i class="fa-solid fa-circle-question nav-ico"></i>
                FAQ
            </a>

        </nav>

        {{-- SIDEBAR FOOTER --}}
        <div class="sidebar-foot">
            <div class="role-note">
                <i class="fa-solid fa-circle-info role-note-ico"></i>
                <span><b class="mono">Mode Karyawan</b> &mdash; akses presensi, izin/cuti, kunjungan klien, dan gaji milik Anda sendiri.</span>
            </div>
        </div>

    </aside>

    {{-- =====================================================
        MAIN
    ====================================================== --}}
    <main class="main">

        {{-- TOPBAR (sama seperti Owner) --}}
        <header class="topbar">
            <div class="org-select">
                Haoyou Educator
                <i class="fa-solid fa-chevron-down"></i>
            </div>

            <div class="topbar-right">
                <div class="tb-pill">ID <i class="fa-solid fa-chevron-down"></i></div>

                <div class="tb-icon" title="Notifikasi">
                    <i class="fa-solid fa-bell"></i>
                </div>

                <div class="tb-icon" title="Pengaturan cepat">
                    <i class="fa-solid fa-gear"></i>
                </div>

                <div class="tb-avatar-wrap">
                    <button type="button" class="tb-avatar" id="avatarToggle">
                        {{ auth()->user()->employee->initials() ?? 'U' }}
                    </button>

                    <div class="tb-avatar-menu" id="avatarMenu">
                        <div class="tb-avatar-menu-header">
                            <div class="tb-avatar-menu-name">{{ auth()->user()->employee->full_name }}</div>
                            <div class="tb-avatar-menu-role">{{ auth()->user()->role }}</div>
                        </div>
                        <a href="{{ route('profil.edit') }}" class="tb-avatar-menu-item">
                            <i class="fa-solid fa-user"></i> Profil Saya
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="tb-avatar-menu-item tb-avatar-menu-danger">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- PAGE CONTENT --}}
        <div class="content">
            @yield('content')
        </div>

    </main>

</div>

{{-- Toggle submenu Kehadiran + aktif-kan menu yang diklik + dropdown avatar --}}
<script>
    function toggleGroup() {
        document.getElementById('navKehadiran').classList.toggle('open');
    }

    document.querySelectorAll('[data-page]').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('[data-page]').forEach(function (n) {
                n.classList.remove('active');
            });
            item.classList.add('active');
        });
    });

    // Dropdown avatar (Profil & Keluar)
    const avatarToggle = document.getElementById('avatarToggle');
    const avatarMenu = document.getElementById('avatarMenu');

    avatarToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        avatarMenu.classList.toggle('open');
    });

    document.addEventListener('click', function () {
        avatarMenu.classList.remove('open');
    });
</script>

{{-- Global JavaScript (Opsional) --}}
@if(file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}"></script>
@endif

{{-- JavaScript tambahan dari halaman --}}
@stack('scripts')

</body>

</html>