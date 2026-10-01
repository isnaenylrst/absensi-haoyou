@extends('owner.dashboard')

@section('title', 'Pengaturan')

@push('styles')

<style>
    .breadcrumb { font-size: 12.5px; color: #9AA0A8; margin-bottom: 14px; }
    .breadcrumb a { color: #9AA0A8; text-decoration: none; }
    .breadcrumb b { color: #22262B; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
    @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }

    .card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-c, #EDEEF0);
        border-radius: 16px;
        padding: 24px;
    }
    .card h2 { font-size: 16px; font-weight: 700; margin-bottom: 0; color: var(--text-main, #22262B); }

    .card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .btn-icon-add {
        width: 32px; height: 32px; border-radius: 50%;
        background: #ffbd08; color: #fff; border: none;
        cursor: pointer; font-size: 14px; display: flex;
        align-items: center; justify-content: center;
    }
    .btn-icon-add:hover { background: #DE8C0F; }

    .branch-row { border-top: 1px solid var(--border-c, #EDEEF0); padding: 14px 0; }
    .branch-row:first-of-type { border-top: none; padding-top: 0; }
    .branch-head { display: flex; justify-content: space-between; align-items: center; }
    .branch-name { font-weight: 700; font-size: 13.5px; color: var(--text-main, #22262B); }
    .branch-meta { font-size: 12px; color: var(--text-dim, #9AA0A8); margin-top: 2px; }
    .branch-edit-toggle {
        background: none; border: none; color: #DE8C0F;
        font-size: 12.5px; font-weight: 600; cursor: pointer;
    }

    .inline-form-panel {
        display: none;
        margin-top: 14px;
        padding: 16px;
        background: #FFF8E6;
        border: 1px solid #F5E4AD;
        border-radius: 10px;
    }
    .inline-form-panel.show { display: block; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }

    .field { margin-bottom: 16px; }
    .field label {
        display: block; font-size: 12.5px; font-weight: 600;
        color: var(--text-dim, #6B7280); margin-bottom: 6px;
    }
    .field input, .field select {
        width: 100%; padding: 10px 12px;
        border: 1px solid var(--border-c, #EDEEF0); border-radius: 9px;
        font-family: 'Poppins', sans-serif; font-size: 13px; outline: none;
        background: var(--input-bg, #FCFCFC); color: var(--text-main, #22262B);
        box-sizing: border-box;
    }
    .field input:focus, .field select:focus {
        border-color: #ffbd08; box-shadow: 0 0 0 3px rgba(255,189,8,.18);
    }
    .field small { display: block; margin-top: 5px; font-size: 11px; color: #888; }

    .field-checkbox { display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 16px; }
    .field-checkbox input { width: 16px; height: 16px; accent-color: #ffbd08; }

    .btn-gold {
        background: #ffbd08; color: #fff; border: none; padding: 11px 22px;
        border-radius: 10px; font-family: 'Poppins', sans-serif; font-weight: 700;
        font-size: 13.5px; cursor: pointer; margin-top: 8px;
    }
    .btn-gold:hover { background: #DE8C0F; }

    .alert { border-radius: 10px; padding: 11px 14px; font-size: 12.5px; font-weight: 600; margin-bottom: 16px; }
    .alert-success { background: #E7F5EC; color: #2F8A5B; border: 1px solid #CDEBD9; }
    .alert-error { background: #FCEAE7; color: #D34D3C; border: 1px solid #F5CFC8; }

    .section-note {
        background: #FFF8E6; border: 1px solid #F5E4AD; border-radius: 10px;
        padding: 12px; margin-bottom: 18px; font-size: 12px; color: #765D16; line-height: 1.5;
    }

    .tolerance-form { display: flex; gap: 10px; align-items: flex-end; margin-top: 18px; }
    .tolerance-form .field { margin-bottom: 0; flex: 1; }
</style>

@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('dashboard') }}">Home</a> &rsaquo; <b>Pengaturan</b>
</div>

<h1 style="font-size:26px; font-weight:800; margin-bottom:20px; color:var(--text-main,#22262B);">
    Pengaturan
</h1>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<div class="grid-2">

    {{-- ============================================================
    KARTU 1: LOKASI CABANG (mendukung banyak lokasi)
    ============================================================ --}}
    <div class="card">
        <div class="card-head">
            <h2>Lokasi Cabang</h2>
            <button type="button" class="btn-icon-add" onclick="togglePanel('addBranchPanel')" title="Tambah Lokasi">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        {{-- Form tambah lokasi baru --}}
        <div class="inline-form-panel" id="addBranchPanel">
            <form method="POST" action="{{ route('cabang.store') }}">
                @csrf
                <div class="form-row">
                    <div class="field">
                        <label>Nama Lokasi Baru</label>
                        <input name="name" required>
                    </div>
                    <div class="field">
                        <label>Radius (meter)</label>
                        <input type="number" name="radius_meter" min="10" max="5000" value="100" required>
                    </div>
                </div>
                <div class="field">
                    <label>Koordinat (latitude, longitude)</label>
                    <input name="koordinat" placeholder="-7.2891, 112.7381" required>
                </div>
                <button type="submit" class="btn-gold">Simpan Lokasi Baru</button>
            </form>
        </div>

        {{-- Daftar lokasi yang sudah ada --}}
        @forelse($branches as $b)
            <div class="branch-row">
                <div class="branch-head">
                    <div>
                        <div class="branch-name">{{ $b->name }}</div>
                        <div class="branch-meta">
                            Radius {{ $b->radius_meter }} m &middot; {{ $b->employees_count }} karyawan
                        </div>
                    </div>
                    <button type="button" class="branch-edit-toggle" onclick="togglePanel('editBranchPanel{{ $b->id }}')">
                        Edit
                    </button>
                </div>

                <div class="inline-form-panel" id="editBranchPanel{{ $b->id }}">
                    <form method="POST" action="{{ route('cabang.update', $b) }}">
                        @csrf @method('PUT')
                        <div class="form-row">
                            <div class="field">
                                <label>Nama Lokasi</label>
                                <input name="name" value="{{ $b->name }}" required>
                            </div>
                            <div class="field">
                                <label>Radius (meter)</label>
                                <input type="number" name="radius_meter" min="10" max="5000" value="{{ $b->radius_meter }}" required>
                            </div>
                        </div>
                        <div class="field">
                            <label>Koordinat (latitude, longitude)</label>
                            <input name="koordinat" value="{{ $b->latitude }}, {{ $b->longitude }}" required>
                        </div>
                        <button type="submit" class="btn-gold">Simpan Perubahan</button>
                    </form>

                    <form method="POST" action="{{ route('cabang.destroy', $b) }}" style="margin-top:10px;"
                          onsubmit="return confirm('Hapus lokasi {{ $b->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;color:#D34D3C;font-size:12px;font-weight:600;cursor:pointer;">
                            Hapus Lokasi Ini
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p style="color: var(--text-dim,#9AA0A8); font-size:13px;">Belum ada lokasi. Klik tombol + untuk menambahkan.</p>
        @endforelse

        {{-- Toleransi keterlambatan — global, berlaku semua lokasi --}}
        <form method="POST" action="{{ route('pengaturan.lokasi') }}" class="tolerance-form">
            @csrf @method('PUT')
            <div class="field">
                <label>Toleransi Keterlambatan Global (menit)</label>
                <input type="number" name="late_tolerance_minutes" min="0" max="120"
                       value="{{ old('late_tolerance_minutes', $settings->late_tolerance_minutes) }}" required>
                <small>Berlaku untuk semua lokasi cabang.</small>
            </div>
            <button type="submit" class="btn-gold">Simpan</button>
        </form>
    </div>


    {{-- ============================================================
    KARTU 2: ATURAN PAYROLL (tidak diubah sama sekali)
    ============================================================ --}}
    <div class="card">
        <h2 style="margin-bottom:16px;">Aturan Payroll &amp; Kehadiran</h2>

        <div class="section-note">
            Pengaturan di bawah ini digunakan untuk perhitungan <strong>karyawan tetap</strong>.
            Perubahan uang makan dan uang bensin akan digunakan pada perhitungan payroll.
            Sistem Part Time tetap menggunakan inputnya sendiri.
        </div>

        <form method="POST" action="{{ route('pengaturan.aturan') }}">
            @csrf @method('PUT')

            <div class="field">
                <label>Denda Keterlambatan / Menit (Rp)</label>
                <input type="number" name="late_deduction_per_minute" min="0" step="100"
                       value="{{ old('late_deduction_per_minute', $settings->late_deduction_per_minute) }}" required>
                <small>Contoh: Rp1.000 per menit keterlambatan.</small>
            </div>

            <div class="field">
                <label>Potongan Alpa / Hari (Rp)</label>
                <input type="number" name="alpa_deduction_per_day" min="0" step="1000"
                       value="{{ old('alpa_deduction_per_day', $settings->alpa_deduction_per_day) }}" required>
            </div>

            <div class="field">
                <label>Uang Makan / Hari (Rp)</label>
                <input type="number" name="meal_rate" min="0" step="1000"
                       value="{{ old('meal_rate', $settings->meal_rate ?? 10000) }}" required>
                <small>Karyawan tetap: tarif × jumlah hari hadir dari absensi.</small>
            </div>

            <div class="field">
                <label>Uang Bensin / Hari (Rp)</label>
                <input type="number" name="transport_rate" min="0" step="1000"
                       value="{{ old('transport_rate', $settings->transport_rate ?? 10000) }}" required>
                <small>Karyawan tetap: tarif × jumlah hari hadir dari absensi.</small>
            </div>

            <div class="field">
                <label>THR Mulai Tahun Ke-</label>
                <input type="number" name="thr_start_year" min="1" max="10"
                       value="{{ old('thr_start_year', $settings->thr_start_year ?? 2) }}" required>
                <small>Default 2 berarti THR aktif mulai tahun kedua dan dihitung 1× gaji pokok.</small>
            </div>

            <div class="field">
                <label>Kebijakan Absen di Luar Radius</label>
                <select name="out_of_radius_policy" required>
                    <option value="ditinjau_manual" @selected(old('out_of_radius_policy', $settings->out_of_radius_policy) === 'ditinjau_manual')>Ditinjau Manual</option>
                    <option value="ditolak_otomatis" @selected(old('out_of_radius_policy', $settings->out_of_radius_policy) === 'ditolak_otomatis')>Ditolak Otomatis</option>
                </select>
            </div>

            <label class="field-checkbox">
                <input type="checkbox" name="photo_required" value="1" @checked(old('photo_required', $settings->photo_required))>
                Wajib foto saat melakukan absensi
            </label>

            <button type="submit" class="btn-gold">Simpan Aturan Payroll</button>
        </form>
    </div>
</div>

<script>
    function togglePanel(id) {
        const panel = document.getElementById(id);
        panel.classList.toggle('show');
    }
</script>

@endsection