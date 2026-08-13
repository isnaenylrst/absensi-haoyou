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

    {{-- CSS Tambahan dari halaman --}}
    @stack('styles')
</head>

<body data-role="owner">

<div class="app" id="absensiApp">

    {{-- =====================================================
        SIDEBAR (OWNER)
    ====================================================== --}}
    <aside class="sidebar">

        {{-- BRAND --}}
        <div class="brand">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Haoyou" class="logo">
            {{-- <div class="brand-sub">Absensi &amp; Payroll</div> --}}
        </div>

        {{-- NAVIGATION --}}
        <nav class="navlist">

            <a href="#" class="nav-item active" data-page="beranda">
                <i class="fa-solid fa-house nav-ico"></i>
                Beranda
            </a>

            <a href="#" class="nav-item" data-page="karyawan">
                <i class="fa-solid fa-users nav-ico"></i>
                Karyawan
            </a>

            {{-- ===== KEHADIRAN (SUBMENU) ===== --}}
            <div class="nav-group open" id="navKehadiran">
                <div class="nav-item" onclick="toggleGroup()">
                    <i class="fa-solid fa-clock nav-ico"></i>
                    Kehadiran
                    <span class="nav-badge nav-badge-inline">2</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>

                <div class="nav-sub">
                    <a href="#" class="nav-sub-item active" data-page="approval">
                        <span class="nav-sub-ico"><i class="fa-solid fa-clipboard-check"></i></span>
                        <span class="nav-sub-label">Approval Presensi</span>
                        <span class="nav-sub-badge">2</span>
                    </a>
                    <a href="#" class="nav-sub-item" data-page="jadwal">
                        <span class="nav-sub-ico"><i class="fa-solid fa-calendar-days"></i></span>
                        <span class="nav-sub-label">Jadwal Kerja</span>
                    </a>
                    <a href="#" class="nav-sub-item" data-page="izin">
                        <span class="nav-sub-ico"><i class="fa-solid fa-plane-departure"></i></span>
                        <span class="nav-sub-label">Izin &amp; Cuti</span>
                    </a>
                    <a href="#" class="nav-sub-item" data-page="kunjungan">
                        <span class="nav-sub-ico"><i class="fa-solid fa-map-location-dot"></i></span>
                        <span class="nav-sub-label">Kunjungan Klien</span>
                    </a>
                </div>
            </div>

            <a href="#" class="nav-item" data-page="payroll">
                <i class="fa-solid fa-sack-dollar nav-ico"></i>
                Payroll
            </a>

            <a href="#" class="nav-item" data-page="pengaturan">
                <i class="fa-solid fa-gear nav-ico"></i>
                Pengaturan
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
                <span><b class="mono">Mode Owner</b> &mdash; akses penuh ke seluruh fitur: Karyawan, Approval, Jadwal Kerja, Payroll, dan Pengaturan.</span>
            </div>
        </div>

    </aside>

    {{-- =====================================================
        MAIN
    ====================================================== --}}
    <main class="main">

        {{-- TOPBAR --}}
        <header class="topbar">
            <div class="org-select">
                Haoyou Educator
                <i class="fa-solid fa-chevron-down"></i>
            </div>

            <div class="topbar-right">
                <div class="tb-pill">ID <i class="fa-solid fa-chevron-down"></i></div>

                <div class="tb-icon" title="Approval tertunda">
                    <i class="fa-solid fa-bell"></i>
                    <div class="tb-badge">2</div>
                </div>

                <div class="tb-icon" title="Pengaturan cepat">
                    <i class="fa-solid fa-gear"></i>
                </div>

                <div class="tb-avatar">HR</div>
            </div>
        </header>

        {{-- PAGE CONTENT --}}
        <div class="content">
            @yield('content')
        </div>

    </main>

</div>

{{-- Toggle submenu Kehadiran + aktif-kan menu yang diklik --}}
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
</script>

{{-- Global JavaScript (Opsional) --}}
@if(file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}"></script>
@endif

{{-- JavaScript tambahan dari halaman --}}
@stack('scripts')

</body>

</html>