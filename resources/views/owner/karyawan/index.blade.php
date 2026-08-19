@extends('owner.dashboard')
@section('title', 'Karyawan')

@push('styles')
<style>
    .breadcrumb { font-size: 12.5px; color: #9AA0A8; margin-bottom: 14px; }
    .breadcrumb a { color: #9AA0A8; text-decoration: none; }
    .breadcrumb b { color: #22262B; }

    .page-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .page-head h1 { font-size: 26px; font-weight: 800; }
    .page-actions { display: flex; gap: 8px; }

    .btn-gold {
        background: #ffbd08; color: #fff; border: none; padding: 11px 18px;
        border-radius: 10px; font-family: 'Poppins', sans-serif; font-weight: 700;
        font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-gold:hover { background: #DE8C0F; }
    .btn-outline {
        background: #fff; color: #22262B; border: 1px solid #EDEEF0; padding: 11px 16px;
        border-radius: 10px; font-family: 'Poppins', sans-serif; font-weight: 600;
        font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none;
    }
    .btn-outline:hover { background: #F7F8FA; }

    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    .toolbar-left { display: flex; align-items: center; gap: 14px; position: relative; }
    .btn-filter {
        background: #fff; border: 1px solid #EDEEF0; padding: 9px 14px; border-radius: 9px;
        font-family: 'Poppins', sans-serif; font-size: 12.5px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-filter.active { border-color: #ffbd08; color: #DE8C0F; background: #FFF7E0; }
    .quota { font-size: 12.5px; color: #6B7280; }
    .quota b { color: #DE8C0F; }
    .search-box input {
        padding: 9px 14px; border: 1px solid #EDEEF0; border-radius: 9px;
        font-family: 'Poppins', sans-serif; font-size: 13px; width: 240px;
    }

    .filter-panel {
        display: none;
        position: absolute; top: calc(100% + 8px); left: 0; z-index: 40;
        background: #fff; border: 1px solid #EDEEF0; border-radius: 12px;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,.2); padding: 16px; min-width: 260px;
    }
    .filter-panel.open { display: block; }
    .filter-panel .field { margin-bottom: 12px; }
    .filter-panel label { display: block; font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 5px; }
    .filter-panel select {
        width: 100%; padding: 8px 10px; border: 1px solid #EDEEF0; border-radius: 8px;
        font-family: 'Poppins', sans-serif; font-size: 12.5px;
    }
    .filter-panel-actions { display: flex; gap: 8px; margin-top: 4px; }
    .filter-panel-actions button, .filter-panel-actions a {
        flex: 1; text-align: center; padding: 8px; border-radius: 8px; font-size: 12.5px;
        font-family: 'Poppins', sans-serif; font-weight: 600; cursor: pointer; text-decoration: none;
    }
    .filter-apply { background: #ffbd08; color: #fff; border: none; }
    .filter-apply:hover { background: #DE8C0F; }
    .filter-reset { background: #F7F8FA; color: #6B7280; border: 1px solid #EDEEF0; }

    .card { background: #fff; border: 1px solid #EDEEF0; border-radius: 16px; overflow: hidden; }

    .tabs { display: flex; gap: 4px; padding: 14px 20px 0; border-bottom: 1px solid #EDEEF0; overflow-x: auto; }
    .tab-btn {
        background: none; border: none; padding: 10px 14px; font-family: 'Poppins', sans-serif;
        font-size: 13px; font-weight: 600; color: #9AA0A8; cursor: pointer; border-radius: 9px 9px 0 0;
        white-space: nowrap;
    }
    .tab-btn.active { color: #22262B; background: #F7F8FA; }

    .table-scroll { overflow-x: auto; }
    table.tbl { width: 100%; min-width: 900px; border-collapse: collapse; font-size: 13px; }
    table.tbl th {
        text-align: left; padding: 12px 16px; background: #FCFCFC;
        color: #9AA0A8; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .04em;
        white-space: nowrap;
    }
    table.tbl td { padding: 12px 16px; border-top: 1px solid #F1F2F3; vertical-align: middle; }

    .emp-cell { display: flex; align-items: center; gap: 10px; }
    .avatar-badge {
        width: 34px; height: 34px; border-radius: 50%; color: #fff; font-weight: 700;
        font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .emp-name { color: #DE8C0F; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .mono { font-family: monospace; }

    .badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
    .badge-green { background: #E7F5EC; color: #2F8A5B; }
    .badge-gray { background: #EDEEF0; color: #9AA0A8; }

    /* ===== Tombol Aksi berwarna ===== */
    .row-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .action-chip {
        font-size: 11.5px; font-weight: 700; padding: 5px 11px; border-radius: 7px;
        border: none; cursor: pointer; text-decoration: none; font-family: 'Poppins', sans-serif;
        white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;
        transition: opacity .15s;
    }
    .action-chip:hover { opacity: .8; }
    .chip-edit { background: #FFF7E0; color: #92700C; }
    .chip-reset { background: #E9F1FF; color: #2563EB; }
    .chip-toggle-on { background: #FFEFE0; color: #C2540A; }
    .chip-toggle-off { background: #E7F5EC; color: #2F8A5B; }
    .chip-delete { background: #FCEAE7; color: #D34D3C; }

    .alert { border-radius: 10px; padding: 11px 14px; font-size: 12.5px; font-weight: 600; margin-bottom: 16px; }
    .alert-success { background: #E7F5EC; color: #2F8A5B; border: 1px solid #CDEBD9; }
    .alert-warning { background: #FFF7E0; color: #92700C; border: 1px solid #FCE9AE; }
    .pagination-wrap { padding: 16px 20px; font-size: 12.5px; }

    [data-tab-col] { display: none; }
    [data-tab-col].tab-visible { display: table-cell; }

    .pagination-wrap nav { font-size: 12.5px; }
    .pagination-wrap svg {
        width: 16px !important;
        height: 16px !important;
        display: inline-block;
        vertical-align: middle;
    }

    @media print {
        @page { size: landscape; margin: 8mm; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sidebar, .topbar, .breadcrumb, .page-actions, .toolbar, .tabs, .no-print,
        .pagination-wrap, .alert { display: none !important; }
        .content { padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table-scroll { overflow: visible !important; }
        [data-tab-col] { display: table-cell !important; }
        table.tbl { width: 100%; min-width: 0; font-size: 9px; table-layout: fixed; }
        table.tbl th, table.tbl td { padding: 5px 6px; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; }
        .emp-cell { gap: 4px; }
        .avatar-badge { width: 20px; height: 20px; font-size: 8px; }
    }
</style>
@endpush

@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Home</a> &rsaquo; <b>Karyawan</b></div>

<div class="page-head">
    <h1>Karyawan</h1>
    <div class="page-actions">
        <a href="{{ route('karyawan.create') }}" class="btn-gold">
            <i class="fa-solid fa-plus"></i> Tambah Karyawan
        </a>
        <a href="{{ route('karyawan.import.form') }}" class="btn-outline">
            <i class="fa-solid fa-download"></i> Import
        </a>
        <a href="{{ route('karyawan.export', request()->query()) }}" class="btn-outline">
            <i class="fa-solid fa-upload"></i> Ekspor
        </a>
        <button type="button" class="btn-outline" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if (session('generated_password'))
    <div class="alert alert-warning">
        Password akun: <b>{{ session('generated_password') }}</b> — catat sekarang, tidak akan ditampilkan lagi.
    </div>
@endif
@if (session('import_skipped') && count(session('import_skipped')) > 0)
    <div class="alert alert-warning">
        Baris yang dilewati saat import:
        <ul style="margin:6px 0 0; padding-left:18px;">
            @foreach (session('import_skipped') as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-warning">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-left">
        <button type="button" class="btn-filter" id="filterToggle">
            <i class="fa-solid fa-filter"></i> Filter
        </button>

        <div class="filter-panel" id="filterPanel">
            <form method="GET" action="{{ route('karyawan.index') }}">
                <input type="hidden" name="q" value="{{ request('q') }}">

                <div class="field">
                    <label>Cabang</label>
                    <select name="branch_id">
                        <option value="">Semua Cabang</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Tipe Karyawan</label>
                    <select name="employee_type">
                        <option value="">Semua Tipe</option>
                        <option value="tetap" @selected(request('employee_type')==='tetap')>Tetap</option>
                        <option value="part_time" @selected(request('employee_type')==='part_time')>Part Time</option>
                    </select>
                </div>

                <div class="field">
                    <label>Status Akun</label>
                    <select name="status_akun">
                        <option value="">Semua Status</option>
                        <option value="aktif" @selected(request('status_akun')==='aktif')>Aktif</option>
                        <option value="nonaktif" @selected(request('status_akun')==='nonaktif')>Nonaktif</option>
                    </select>
                </div>

                <div class="filter-panel-actions">
                    <a href="{{ route('karyawan.index') }}" class="filter-reset">Reset</a>
                    <button type="submit" class="filter-apply">Terapkan</button>
                </div>
            </form>
        </div>

        <div class="quota">Total Karyawan: <b>{{ $employees->total() }}</b></div>
    </div>

    <form method="GET" class="search-box">
        @if(request('branch_id')) <input type="hidden" name="branch_id" value="{{ request('branch_id') }}"> @endif
        @if(request('employee_type')) <input type="hidden" name="employee_type" value="{{ request('employee_type') }}"> @endif
        @if(request('status_akun')) <input type="hidden" name="status_akun" value="{{ request('status_akun') }}"> @endif
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari karyawan...">
    </form>
</div>

<div class="card">
    <div class="tabs" id="empTabs">
        <button type="button" class="tab-btn active" data-tab="pribadi">Informasi Pribadi</button>
        <button type="button" class="tab-btn" data-tab="kontak">Kontak</button>
        <button type="button" class="tab-btn" data-tab="kepegawaian">Kepegawaian</button>
        <button type="button" class="tab-btn" data-tab="login">Login ESS</button>
    </div>

    <div class="table-scroll">
    <table class="tbl">
        <thead>
        <tr>
            <th>No.</th>
            <th>Nama</th>
            <th data-tab-col="pribadi" class="tab-visible">NIK</th>
            <th data-tab-col="pribadi" class="tab-visible">Jenis Kelamin</th>
            <th data-tab-col="pribadi" class="tab-visible">Kewarganegaraan</th>
            <th data-tab-col="pribadi" class="tab-visible">Agama</th>
            <th data-tab-col="pribadi" class="tab-visible">Gol. Darah</th>
            <th data-tab-col="pribadi" class="tab-visible">Tempat Lahir</th>
            <th data-tab-col="pribadi" class="tab-visible">Tanggal Lahir</th>
            <th data-tab-col="pribadi" class="tab-visible">Status Pernikahan</th>
            <th data-tab-col="pribadi" class="tab-visible">Pendidikan Terakhir</th>

            <th data-tab-col="kontak">Telepon</th>
            <th data-tab-col="kontak">Email</th>
            <th data-tab-col="kontak">Alamat</th>

            <th data-tab-col="kepegawaian">Cabang</th>
            <th data-tab-col="kepegawaian">Jabatan</th>
            <th data-tab-col="kepegawaian">Tipe</th>
            <th data-tab-col="kepegawaian">Tanggal Bergabung</th>

            <th data-tab-col="login">Username</th>
            <th data-tab-col="login">Status Akun</th>
            <th data-tab-col="login">Terakhir Login</th>

            <th class="no-print">Aksi</th>
        </tr>
        </thead>
        <tbody>
        @forelse($employees as $i => $emp)
        <tr>
            <td>{{ $employees->firstItem() + $i }}</td>
            <td>
                <div class="emp-cell">
                    <div class="avatar-badge" style="background: {{ $emp->avatarColor() }};">{{ $emp->initials() }}</div>
                    <a href="{{ route('karyawan.edit', $emp) }}" class="emp-name">{{ $emp->full_name }}</a>
                </div>
            </td>
            
            <td data-tab-col="pribadi" class="tab-visible mono">{{ $emp->nik ?? '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ $emp->gender ? ucfirst($emp->gender) : '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ $emp->nationality ?? '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ $emp->religion ?? '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ $emp->blood_type ?? '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ $emp->birth_place ?? '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ optional($emp->birth_date)->format('d M Y') ?? '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ $emp->marital_status ? str_replace('_',' ',ucfirst($emp->marital_status)) : '-' }}</td>
            <td data-tab-col="pribadi" class="tab-visible">{{ $emp->last_education ?? '-' }}</td>
    

            <td data-tab-col="kontak">{{ $emp->phone ?? '-' }}</td>
            <td data-tab-col="kontak">{{ $emp->email ?? '-' }}</td>
            <td data-tab-col="kontak">{{ \Illuminate\Support\Str::limit($emp->address, 30) ?: '-' }}</td>

            <td data-tab-col="kepegawaian">{{ $emp->branch->name ?? '-' }}</td>
            <td data-tab-col="kepegawaian">{{ $emp->position ?? '-' }}</td>
            <td data-tab-col="kepegawaian">{{ $emp->employee_type === 'tetap' ? 'Tetap' : 'Part Time' }}</td>
            <td data-tab-col="kepegawaian">{{ optional($emp->join_date)->format('d M Y') ?? '-' }}</td>

            <td data-tab-col="login" class="mono">{{ $emp->user->username ?? '-' }}</td>
            <td data-tab-col="login">
                @if($emp->user)
                    <span class="badge {{ $emp->user->status_akun === 'aktif' ? 'badge-green' : 'badge-gray' }}">
                        {{ ucfirst($emp->user->status_akun) }}
                    </span>
                @else
                    <span class="badge badge-gray">Belum ada akun</span>
                @endif
            </td>
            <td data-tab-col="login">{{ optional($emp->user?->last_login)->format('d M Y, H:i') ?? 'Belum pernah' }}</td>

            <td class="row-actions no-print">
                <a href="{{ route('karyawan.edit', $emp) }}" class="action-chip chip-edit">
                    <i class="fa-solid fa-pen"></i> Edit
                </a>

                <form method="POST" action="{{ route('karyawan.reset-password', $emp) }}">
                    @csrf
                    <button type="submit" class="action-chip chip-reset" onclick="return confirm('Reset password {{ $emp->full_name }}?')">
                        <i class="fa-solid fa-key"></i> Reset PW
                    </button>
                </form>

                <form method="POST" action="{{ route('karyawan.toggle-status', $emp) }}">
                    @csrf
                    @if($emp->user?->status_akun === 'aktif')
                        <button type="submit" class="action-chip chip-toggle-on">
                            <i class="fa-solid fa-user-slash"></i> Nonaktifkan
                        </button>
                    @else
                        <button type="submit" class="action-chip chip-toggle-off">
                            <i class="fa-solid fa-user-check"></i> Aktifkan
                        </button>
                    @endif
                </form>

                <form method="POST" action="{{ route('karyawan.destroy', $emp) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="action-chip chip-delete" onclick="return confirm('Hapus {{ $emp->full_name }}? Akun login ikut terhapus.')">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="21" style="text-align:center; padding:24px; color:#9AA0A8;">Tidak ada data yang cocok dengan filter/pencarian.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <div class="pagination-wrap">
        {{ $employees->links() }}
    </div>
</div>

<script>
    const tabButtons = document.querySelectorAll('#empTabs .tab-btn');
    const tabColumns = document.querySelectorAll('[data-tab-col]');

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = btn.dataset.tab;
            tabButtons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            tabColumns.forEach(function (col) {
                col.classList.toggle('tab-visible', col.dataset.tabCol === target);
            });
        });
    });

    const filterToggle = document.getElementById('filterToggle');
    const filterPanel = document.getElementById('filterPanel');

    filterToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        filterPanel.classList.toggle('open');
    });

    filterPanel.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    document.addEventListener('click', function () {
        filterPanel.classList.remove('open');
    });

    @if(request('branch_id') || request('employee_type') || request('status_akun'))
        filterToggle.classList.add('active');
    @endif
</script>
@endsection