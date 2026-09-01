<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Beranda') | Presence</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    {{-- Font Awesome --}}
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        referrerpolicy="no-referrer"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- CSS Dropdown Profile & Notifikasi --}}
    <style>
        /* ==========================
           DROPDOWN PROFILE / NOTIF
        ========================== */

        .tb-avatar-wrap {
            position: relative;
        }

        .tb-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .25);
            color: #fff;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tb-avatar:hover {
            background: rgba(255, 255, 255, .35);
        }

        .tb-avatar-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, .25);
            min-width: 210px;
            overflow: hidden;
            z-index: 9999;
            font-family: 'Poppins', sans-serif;
        }

        .tb-avatar-menu.open {
            display: block;
        }

        .tb-avatar-menu-header {
            padding: 12px 14px;
            border-bottom: 1px solid #EDEEF0;
        }

        .tb-avatar-menu-name {
            font-size: 13px;
            font-weight: 700;
            color: #22262B;
        }

        .tb-avatar-menu-role {
            font-size: 11px;
            color: #9AA0A8;
            text-transform: capitalize;
            margin-top: 2px;
        }

        .tb-avatar-menu-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            box-sizing: border-box;
            text-align: left;
            background: none;
            border: none;
            padding: 11px 14px;
            font-size: 12.5px;
            color: #22262B;
            text-decoration: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .tb-avatar-menu-item:hover {
            background: #F7F8FA;
        }

        .tb-avatar-menu-danger {
            color: #D34D3C;
        }

        .logout-form {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .logout-form button {
            border: none;
            margin: 0;
        }

        /* ==========================
           SIDEBAR SUBMENU
        ========================== */

        .nav-group {
            width: 100%;
        }

        .nav-group .nav-item {
            cursor: pointer;
        }

        .nav-sub {
            display: none;
        }

        .nav-group.open .nav-sub {
            display: block;
        }

        .nav-chevron {
            margin-left: auto;
            transition: transform .2s ease;
        }

        .nav-group.open .nav-chevron {
            transform: rotate(90deg);
        }

        .nav-sub-item {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-sub-label {
            display: block;
        }

        /* ===== Ikon Mode Gelap/Terang — dibedakan lewat warna ikon ===== */
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


<body data-role="karyawan">

<div class="app" id="absensiApp">


    {{-- =====================================================
        SIDEBAR KARYAWAN
    ====================================================== --}}

    <aside class="sidebar">

        <div class="brand">
            <img
                src="{{ asset('assets/img/logo.png') }}"
                alt="Haoyou"
                class="logo"
            >
        </div>

        {{-- =====================================================
            NAVIGATION
        ====================================================== --}}

        <nav class="navlist">

            {{-- BERANDA --}}
            <a
                href="{{ route('dashboard') }}"
                class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
            >
                <i class="fa-solid fa-house nav-ico"></i>
                <span>Beranda</span>
            </a>

            {{-- =================================================
                KARYAWAN — KHUSUS ADMIN
            ================================================== --}}

            @if(auth()->user()->isAdmin())
                <a
                    href="{{ route('karyawan.index') }}"
                    class="nav-item {{ request()->routeIs('karyawan.*') ? 'active' : '' }}"
                >
                    <i class="fa-solid fa-users nav-ico"></i>
                    <span>Karyawan</span>
                </a>
            @endif


            {{-- =================================================
                KEHADIRAN
            ================================================== --}}

            <div
                class="nav-group {{ request()->routeIs(['presensi', 'kunjungan-klien-saya', 'leave-requests.*']) ? 'open' : '' }}"
                id="navKehadiran"
            >

                {{-- Header Kehadiran --}}
                <div
                    class="nav-item"
                    onclick="toggleGroup()"
                >
                    <i class="fa-solid fa-clock nav-ico"></i>

                    <span>Kehadiran</span>

                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>


                {{-- SUBMENU --}}
                <div class="nav-sub">
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


                    {{-- ==============================
                        KUNJUNGAN KLIEN
                    =============================== --}}

                    <a
                        href="{{ route('kunjungan-klien-saya') }}"
                        class="nav-sub-item {{ request()->routeIs('kunjungan-klien-saya') ? 'active' : '' }}"
                    >

                        <span class="nav-sub-ico">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </span>

                        <span class="nav-sub-label">
                            Kunjungan Klien
                        </span>

                    </a>


                </div>

            </div>


            {{-- =================================================
                GAJI SAYA
            ================================================== --}}

            <a
                href="{{ route('payslips.index') }}"
                class="nav-item {{ request()->routeIs('payslips.*') ? 'active' : '' }}"
            >

                <i class="fa-solid fa-sack-dollar nav-ico"></i>

                <span>Gaji Saya</span>

            </a>

        </nav>

    </aside>


    {{-- =====================================================
        MAIN
    ====================================================== --}}

    <main class="main">

        {{-- =================================================
            TOPBAR — Organisasi, ID, Notifikasi, Pengaturan,
            Mode Gelap/Terang, Profil
        ================================================== --}}

        <header class="topbar">


            {{-- ORGANIZATION --}}
            <div class="org-select">

                <span>Haoyou Educator</span>

                <i class="fa-solid fa-chevron-down"></i>

            </div>


            {{-- TOPBAR RIGHT --}}
            <div class="topbar-right">


                {{-- ===== Notifikasi ===== --}}
                <div style="position:relative;">
                    <button
                        type="button"
                        class="tb-icon"
                        id="notifToggle"
                        title="Notifikasi"
                        style="border:none; cursor:pointer;"
                    >
                        <i class="fa-solid fa-bell"></i>
                        @if(($myLeaveNotifCount ?? 0) > 0)
                            <div class="tb-badge">{{ $myLeaveNotifCount }}</div>
                        @endif
                    </button>

                    <div class="tb-avatar-menu" id="notifMenu" style="min-width: 280px;">
                        <div class="tb-avatar-menu-header">
                            <div class="tb-avatar-menu-name">Notifikasi</div>
                        </div>
                        @forelse(($myLeaveNotifications ?? []) as $leave)
                            <div class="tb-avatar-menu-item" style="cursor:default; display:block;">
                                <b>Izin {{ str_replace('_',' ', ucfirst($leave->leave_type)) }}</b><br>
                                <span style="font-size:11.5px; color:{{ $leave->status === 'disetujui' ? '#2F8A5B' : '#D34D3C' }};">
                                    {{ $leave->status === 'disetujui' ? 'Disetujui' : 'Ditolak' }}
                                    @if($leave->approved_at)
                                        &middot; {{ $leave->approved_at->diffForHumans() }}
                                    @endif
                                </span>
                            </div>
                        @empty
                            <div class="tb-avatar-menu-item" style="cursor:default; color:#9AA0A8;">Tidak ada notifikasi baru</div>
                        @endforelse
                    </div>
                </div>


                {{-- ===== Mode Gelap/Terang ===== --}}
                <button
                    type="button"
                    class="tb-icon"
                    id="themeToggle"
                    title="Mode Gelap/Terang"
                    style="border:none; cursor:pointer;"
                >
                    <i class="fa-solid fa-sun" id="themeIconSun"></i>
                    <i class="fa-solid fa-moon" id="themeIconMoon" style="display:none;"></i>
                </button>


                {{-- =================================================
                    PROFILE DROPDOWN
                ================================================== --}}

                <div class="tb-avatar-wrap">


                    {{-- AVATAR --}}
                    <button
                        type="button"
                        class="tb-avatar"
                        id="avatarToggle"
                        aria-label="Menu akun"
                    >
                        {{ auth()->user()->employee->initials() ?? 'U' }}
                    </button>


                    {{-- DROPDOWN --}}
                    <div
                        class="tb-avatar-menu"
                        id="avatarMenu"
                    >


                        {{-- PROFILE HEADER --}}
                        <div class="tb-avatar-menu-header">

                            <div class="tb-avatar-menu-name">
                                {{ auth()->user()->employee->full_name }}
                            </div>

                            <div class="tb-avatar-menu-role">
                                {{ auth()->user()->role }}
                            </div>

                        </div>


                        {{-- PROFILE --}}
                        <a
                            href="{{ route('profil.edit') }}"
                            class="tb-avatar-menu-item"
                        >

                            <i class="fa-solid fa-user"></i>

                            <span>
                                Profil Saya
                            </span>

                        </a>


                        {{-- LOGOUT --}}
                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            class="logout-form"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="tb-avatar-menu-item tb-avatar-menu-danger"
                            >

                                <i class="fa-solid fa-arrow-right-from-bracket"></i>

                                <span>
                                    Keluar
                                </span>

                            </button>

                        </form>


                    </div>

                </div>


            </div>

        </header>


        {{-- =====================================================
            PAGE CONTENT
        ====================================================== --}}

        <div class="content">

            @yield('content')

        </div>


    </main>


</div>

{{-- =========================================================
    JAVASCRIPT
========================================================= --}}

<script>

    /* =====================================================
       TOGGLE SIDEBAR KEHADIRAN
    ====================================================== */

    function toggleGroup() {

        const group = document.getElementById('navKehadiran');

        if (!group) {
            return;
        }

        group.classList.toggle('open');
    }


    /* =====================================================
       DROPDOWN GENERIK (Profil & Notifikasi)
    ====================================================== */

    function setupDropdown(toggleId, menuId) {

        const toggle = document.getElementById(toggleId);
        const menu = document.getElementById(menuId);

        if (!toggle || !menu) {
            return;
        }

        toggle.addEventListener('click', function (e) {

            e.preventDefault();
            e.stopPropagation();

            // Tutup dropdown lain yang sedang terbuka
            document.querySelectorAll('.tb-avatar-menu.open').forEach(function (m) {
                if (m !== menu) m.classList.remove('open');
            });

            menu.classList.toggle('open');
        });

        menu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    setupDropdown('notifToggle', 'notifMenu');
    setupDropdown('avatarToggle', 'avatarMenu');

    document.addEventListener('click', function () {
        document.querySelectorAll('.tb-avatar-menu.open').forEach(function (m) {
            m.classList.remove('open');
        });
    });


    /* =====================================================
       MODE GELAP / TERANG
    ====================================================== */

    const themeToggle = document.getElementById('themeToggle');
    const sunIcon = document.getElementById('themeIconSun');
    const moonIcon = document.getElementById('themeIconMoon');

    function applyTheme(theme) {
        document.body.classList.toggle('dark-mode', theme === 'dark');
        sunIcon.style.display = theme === 'dark' ? 'none' : 'inline';
        moonIcon.style.display = theme === 'dark' ? 'inline' : 'none';
    }

    if (themeToggle) {
        const savedTheme = localStorage.getItem('absenly_theme') || 'light';
        applyTheme(savedTheme);

        themeToggle.addEventListener('click', function () {
            const current = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem('absenly_theme', next);
        });
    }

</script>


{{-- Global JavaScript --}}
@if(file_exists(public_path('js/app.js')))

    <script src="{{ asset('js/app.js') }}"></script>

@endif

{{-- JavaScript tambahan dari halaman --}}
@stack('scripts')


</body>

</html>