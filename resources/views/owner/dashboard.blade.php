<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Beranda') | Presence</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">

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
        .tb-avatar-menu-header { padding: 12px 14px; border-bottom: 1px solid #EDEEF0; }
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

        /* ===== Ikon Mode Gelap/Terang ===== */
        #themeIconSun { color: #FFD24C; }
        #themeIconMoon { color: #C9B8FF; }

        /* ===== Mode Gelap ===== */
        :root {
            --bg-page: #f7f8fa;
            --card-bg: #ffffff;
            --text-main: #22262B;
            --text-dim: #6B7280;
            --border-c: #EDEEF0;
            --input-bg: #FCFCFC;
            --sidebar-bg: #ffffff;
        }
        body.dark-mode {
            --bg-page: #14161A;
            --card-bg: #1E2126;
            --text-main: #F1F2F4;
            --text-dim: #9AA0A8;
            --border-c: #2C2F35;
            --input-bg: #24272D;
            --sidebar-bg: #1A1C21;
        }
        body { background: var(--bg-page); color: var(--text-main); transition: background .2s, color .2s; }
        body.dark-mode .sidebar { background: var(--sidebar-bg); border-right: 1px solid var(--border-c); }
        body.dark-mode .nav-item { color: var(--text-dim); }
        body.dark-mode .nav-item.active { background: rgba(255,189,8,.12); }
        body.dark-mode .card, body.dark-mode .table-card, body.dark-mode table.tbl {
            background: var(--card-bg); border-color: var(--border-c); color: var(--text-main);
        }
        body.dark-mode table.tbl th { background: #24272D; color: var(--text-dim); }
        body.dark-mode table.tbl td { border-color: var(--border-c); }
        body.dark-mode .field input, body.dark-mode .field select, body.dark-mode .field textarea {
            background: var(--input-bg); border-color: var(--border-c); color: var(--text-main);
        }
    </style>

    @stack('styles')
</head>

<body data-role="{{ auth()->user()->role }}">

<script>
    (function () {
        var theme = localStorage.getItem('absenly_theme') || 'light';
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    })();
</script>

<div class="app" id="absensiApp">

    {{-- Overlay untuk sidebar mobile --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- =====================================================
        SIDEBAR (OWNER & ADMIN)
    ====================================================== --}}
    <aside class="sidebar" id="sidebarEl">

        <div class="brand">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Haoyou" class="logo">

            {{-- ===== Toggle Sidebar — di area logo/brand ===== --}}
            <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <nav class="navlist">

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="{{ __('nav.beranda') }}">
                <i class="fa-solid fa-house nav-ico"></i>
                <span>{{ __('nav.beranda') }}</span>
            </a>

            <a href="{{ route('karyawan.index') }}" class="nav-item {{ request()->routeIs('karyawan.*') ? 'active' : '' }}" title="{{ __('nav.karyawan') }}">
                <i class="fa-solid fa-users nav-ico"></i>
                <span>{{ __('nav.karyawan') }}</span>
            </a>

            <div class="nav-group {{ request()->routeIs(['approval', 'jadwal-kerja', 'presensi', 'kunjungan-klien', 'kunjungan-klien-saya', 'leave-requests.*']) ? 'open' : '' }}" id="navKehadiran">
                <div class="nav-item" onclick="toggleGroup()" title="Kehadiran">
                    <i class="fa-solid fa-clock nav-ico"></i>
                    <span>Kehadiran</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>

                <div class="nav-sub">
                    @if(auth()->user()->role === 'owner')
                        {{-- ===== VERSI OWNER: kelola/approve semua karyawan ===== --}}
                        <a href="{{ route('jadwal-kerja') }}" class="nav-sub-item {{ request()->routeIs('jadwal-kerja') ? 'active' : '' }}">
                            <span class="nav-sub-ico"><i class="fa-solid fa-calendar-days"></i></span>
                            <span class="nav-sub-label">Jadwal Kerja</span>
                        </a>

                        <a href="{{ route('leave-requests.index') }}" class="nav-sub-item {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                            <span class="nav-sub-ico"><i class="fa-solid fa-plane-departure"></i></span>
                            <span class="nav-sub-label">Izin &amp; Cuti</span>
                        </a>

                        <a href="{{ route('kunjungan-klien') }}" class="nav-sub-item {{ request()->routeIs('kunjungan-klien') ? 'active' : '' }}">
                            <span class="nav-sub-ico"><i class="fa-solid fa-map-location-dot"></i></span>
                            <span class="nav-sub-label">Kunjungan Klien</span>
                        </a>
                    @else
                        {{-- ===== VERSI ADMIN: sama seperti karyawan lain, untuk diri sendiri ===== --}}
                        @can('access-presensi')
                            <a href="{{ route('presensi') }}" class="nav-sub-item {{ request()->routeIs('presensi') ? 'active' : '' }}">
                                <span class="nav-sub-ico"><i class="fa-solid fa-clipboard-check"></i></span>
                                <span class="nav-sub-label">Presensi</span>
                            </a>
                        @endcan

                        <a href="{{ route('leave-requests.index') }}" class="nav-sub-item {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                            <span class="nav-sub-ico"><i class="fa-solid fa-plane-departure"></i></span>
                            <span class="nav-sub-label">Izin &amp; Cuti</span>
                        </a>

                        <a href="{{ route('kunjungan-klien-saya') }}" class="nav-sub-item {{ request()->routeIs('kunjungan-klien-saya') ? 'active' : '' }}">
                            <span class="nav-sub-ico"><i class="fa-solid fa-map-location-dot"></i></span>
                            <span class="nav-sub-label">Kunjungan Klien</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- =================================================
                GAJI SAYA — KHUSUS ADMIN (bukan owner)
            ================================================== --}}
            @if(auth()->user()->role !== 'owner')
                <a href="{{ route('payslips.index') }}" class="nav-item {{ request()->routeIs('payslips.*') ? 'active' : '' }}" title="Gaji Saya">
                    <i class="fa-solid fa-sack-dollar nav-ico"></i>
                    <span>Gaji Saya</span>
                </a>
            @endif

            {{-- =================================================
                PAYROLL — KHUSUS OWNER
            ================================================== --}}
            @if(auth()->user()->role === 'owner')
                <a href="{{ route('payroll.index') }}" class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}" title="Payroll">
                    <i class="fa-solid fa-sack-dollar nav-ico"></i>
                    <span>Payroll</span>
                </a>
            @endif

            {{-- =================================================
                PENGATURAN — KHUSUS OWNER
            ================================================== --}}
            @if(auth()->user()->role === 'owner')
                <a href="{{ route('pengaturan.edit') }}" class="nav-item {{ request()->routeIs('pengaturan.*') ? 'active' : '' }}" title="{{ __('nav.pengaturan') }}">
                    <i class="fa-solid fa-gear nav-ico"></i>
                    <span>{{ __('nav.pengaturan') }}</span>
                </a>
            @endif

        </nav>

    </aside>

    {{-- Tombol mengambang untuk buka sidebar saat tertutup (mobile) --}}
    <button type="button" class="mobile-menu-fab" id="mobileMenuFab" title="Buka Menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    {{-- =====================================================
        MAIN
    ====================================================== --}}
    <main class="main">

        <header class="topbar">

            <div class="org-select">
                Haoyou Educator
                <i class="fa-solid fa-chevron-down"></i>
            </div>

            <div class="topbar-right">

                {{-- ===== Notifikasi ===== --}}
                <div style="position:relative;">
                    <button type="button" class="tb-icon" id="notifToggle" title="{{ __('nav.notifikasi') }}" style="border:none; cursor:pointer;">
                        <i class="fa-solid fa-bell"></i>
                        @if($pendingLeaveCount > 0)
                            <div class="tb-badge">{{ $pendingLeaveCount }}</div>
                        @endif
                    </button>
                    <div class="tb-avatar-menu" id="notifMenu" style="min-width: 280px;">
                        <div class="tb-avatar-menu-header">
                            <div class="tb-avatar-menu-name">{{ __('nav.notifikasi') }}</div>
                        </div>
                        @forelse($pendingLeaveList as $leave)
                            <div class="tb-avatar-menu-item" style="cursor:default; display:block;">
                                <b>{{ $leave->employee->full_name ?? '-' }}</b><br>
                                <span style="font-size:11.5px; color:#9AA0A8;">
                                    {{ str_replace('_',' ', ucfirst($leave->leave_type)) }} — {{ __('nav.menunggu_persetujuan') }}
                                </span>
                            </div>
                        @empty
                            <div class="tb-avatar-menu-item" style="cursor:default; color:#9AA0A8;">{{ __('nav.tidak_ada_notifikasi') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- ===== Mode Gelap/Terang ===== --}}
                <button type="button" class="tb-icon" id="themeToggle" title="Mode Gelap/Terang" style="border:none; cursor:pointer;">
                    <i class="fa-solid fa-sun" id="themeIconSun"></i>
                    <i class="fa-solid fa-moon" id="themeIconMoon" style="display:none;"></i>
                </button>

                {{-- ===== Pengaturan (shortcut ikon) — KHUSUS OWNER ===== --}}
                @if(auth()->user()->role === 'owner')
                    <a href="{{ route('pengaturan.edit') }}" class="tb-icon" title="{{ __('nav.pengaturan') }}">
                        <i class="fa-solid fa-gear"></i>
                    </a>
                @endif

                {{-- ===== Avatar (Profil & Keluar) ===== --}}
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
                            <i class="fa-solid fa-user"></i> {{ __('nav.profil_saya') }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="tb-avatar-menu-item tb-avatar-menu-danger">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> {{ __('nav.keluar') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>

    </main>

</div>

<script>
    function toggleGroup() {
        document.getElementById('navKehadiran').classList.toggle('open');
    }

    function setupDropdown(toggleId, menuId) {
        const toggle = document.getElementById(toggleId);
        const menu = document.getElementById(menuId);
        if (!toggle || !menu) return;
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.tb-avatar-menu.open').forEach(function (m) {
                if (m !== menu) m.classList.remove('open');
            });
            menu.classList.toggle('open');
        });
        menu.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    setupDropdown('notifToggle', 'notifMenu');
    setupDropdown('avatarToggle', 'avatarMenu');

    document.addEventListener('click', function () {
        document.querySelectorAll('.tb-avatar-menu.open').forEach(function (m) {
            m.classList.remove('open');
        });
    });

    /* =====================================================
       TOGGLE SIDEBAR (BUKA/TUTUP) — RESPONSIVE SEMUA DEVICE
    ====================================================== */

    (function () {
        const appEl = document.getElementById('absensiApp');
        const sidebarEl = document.getElementById('sidebarEl');
        const overlayEl = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        const mobileFab = document.getElementById('mobileMenuFab');

        function isMobile() {
            return window.innerWidth <= 900;
        }

        function openMobileSidebar() {
            sidebarEl.classList.add('mobile-open');
            overlayEl.classList.add('show');
        }

        function closeMobileSidebar() {
            sidebarEl.classList.remove('mobile-open');
            overlayEl.classList.remove('show');
        }

        function toggleSidebar() {
            if (isMobile()) {
                sidebarEl.classList.contains('mobile-open') ? closeMobileSidebar() : openMobileSidebar();
            } else {
                appEl.classList.toggle('sidebar-collapsed');
                localStorage.setItem(
                    'absenly_sidebar',
                    appEl.classList.contains('sidebar-collapsed') ? 'collapsed' : 'open'
                );
            }
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleSidebar);
        }

        if (mobileFab) {
            mobileFab.addEventListener('click', openMobileSidebar);
        }

        if (overlayEl) {
            overlayEl.addEventListener('click', closeMobileSidebar);
        }

        if (!isMobile() && localStorage.getItem('absenly_sidebar') === 'collapsed') {
            appEl.classList.add('sidebar-collapsed');
        }

        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeMobileSidebar();
            }
        });
    })();

    // Mode Gelap/Terang
    const themeToggle = document.getElementById('themeToggle');
    const sunIcon = document.getElementById('themeIconSun');
    const moonIcon = document.getElementById('themeIconMoon');

    function applyTheme(theme) {
        document.body.classList.toggle('dark-mode', theme === 'dark');
        sunIcon.style.display = theme === 'dark' ? 'none' : 'inline';
        moonIcon.style.display = theme === 'dark' ? 'inline' : 'none';
    }

    const savedTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', function () {
        const current = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem('absenly_theme', next);
    });
</script>

@if(file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}"></script>
@endif

@stack('scripts')

</body>

</html>