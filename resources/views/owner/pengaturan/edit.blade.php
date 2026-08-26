@extends('owner.dashboard')
@section('title', 'Pengaturan')

@push('styles')
<style>
    .breadcrumb { font-size: 12.5px; color: #9AA0A8; margin-bottom: 14px; }
    .breadcrumb a { color: #9AA0A8; text-decoration: none; }
    .breadcrumb b { color: #22262B; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
    @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }

    .card { background: var(--card-bg, #fff); border: 1px solid var(--border-c, #EDEEF0); border-radius: 16px; padding: 24px; }
    .card h2 { font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--text-main, #22262B); }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .field { margin-bottom: 16px; }
    .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-dim, #6B7280); margin-bottom: 6px; }
    .field input, .field select {
        width: 100%; padding: 10px 12px; border: 1px solid var(--border-c, #EDEEF0); border-radius: 9px;
        font-family: 'Poppins', sans-serif; font-size: 13px; outline: none;
        background: var(--input-bg, #FCFCFC); color: var(--text-main, #22262B);
    }
    .field input:focus, .field select:focus {
        border-color: #ffbd08; box-shadow: 0 0 0 3px rgba(255,189,8,.18);
    }
    .field-checkbox { display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 16px; }
    .field-checkbox input { width: 16px; height: 16px; accent-color: #ffbd08; }

    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-top: 1px solid var(--border-c, #F1F2F3); font-size: 13px; }
    .info-row .label { color: var(--text-dim, #6B7280); }
    .badge-red { color: #D34D3C; font-weight: 700; }
    .badge-green { color: #2F8A5B; font-weight: 700; }

    .btn-gold {
        background: #ffbd08; color: #fff; border: none; padding: 11px 22px;
        border-radius: 10px; font-family: 'Poppins', sans-serif; font-weight: 700;
        font-size: 13.5px; cursor: pointer; margin-top: 8px;
    }
    .btn-gold:hover { background: #DE8C0F; }

    .alert { border-radius: 10px; padding: 11px 14px; font-size: 12.5px; font-weight: 600; margin-bottom: 16px; }
    .alert-success { background: #E7F5EC; color: #2F8A5B; border: 1px solid #CDEBD9; }
    .alert-error { background: #FCEAE7; color: #D34D3C; border: 1px solid #F5CFC8; }
</style>
@endpush

@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Home</a> &rsaquo; <b>Pengaturan</b></div>
<h1 style="font-size:26px; font-weight:800; margin-bottom:20px; color:var(--text-main,#22262B);">Pengaturan</h1>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<div class="grid-2">
    {{-- ===== KARTU 1: Titik & Radius Kantor ===== --}}
    <div class="card">
        <h2>Titik &amp; Radius Kantor</h2>

        <form method="POST" action="{{ route('pengaturan.lokasi') }}">
            @csrf @method('PUT')

            <div class="form-row">
                <div class="field">
                    <label>Nama Titik Lokasi</label>
                    <input name="branch_name" value="{{ old('branch_name', $branch->name ?? '') }}" required>
                </div>
                <div class="field">
                    <label>Radius Diizinkan (meter)</label>
                    <input type="number" name="radius_meter" min="10" max="5000"
                           value="{{ old('radius_meter', $branch->radius_meter ?? 100) }}" required>
                </div>
            </div>

            <div class="field">
                <label>Koordinat (latitude, longitude)</label>
                <input name="koordinat" placeholder="-7.2891, 112.7381"
                       value="{{ old('koordinat', $branch ? $branch->latitude.', '.$branch->longitude : '') }}" required>
            </div>

            <div class="field">
                <label>Toleransi Keterlambatan (menit)</label>
                <input type="number" name="late_tolerance_minutes" min="0" max="120"
                       value="{{ old('late_tolerance_minutes', $settings->late_tolerance_minutes) }}" required>
            </div>

            <button type="submit" class="btn-gold">Simpan Pengaturan</button>
        </form>
    </div>

    {{-- ===== KARTU 2: Aturan Potongan & Kebijakan THR ===== --}}
    <div class="card">
        <h2>Aturan Potongan &amp; Kebijakan THR</h2>

        <form method="POST" action="{{ route('pengaturan.aturan') }}">
            @csrf @method('PUT')

            <div class="form-row">
                <div class="field">
                    <label>Potongan per Menit Terlambat (Rp)</label>
                    <input type="number" name="late_deduction_per_minute" min="0" step="0.01"
                           value="{{ old('late_deduction_per_minute', $settings->late_deduction_per_minute) }}" required>
                </div>
                <div class="field">
                    <label>Potongan Alpa / Hari (Rp)</label>
                    <input type="number" name="alpa_deduction_per_day" min="0" step="0.01"
                           value="{{ old('alpa_deduction_per_day', $settings->alpa_deduction_per_day) }}" required>
                </div>
            </div>

            <div class="field">
                <label>THR Mulai Berlaku pada</label>
                <select name="thr_start_year">
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @selected(old('thr_start_year', $settings->thr_start_year) == $i)>Tahun kerja ke-{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div class="field">
                <label>Absen di Luar Radius</label>
                <select name="out_of_radius_policy">
                    <option value="ditinjau_manual" @selected($settings->out_of_radius_policy === 'ditinjau_manual')>Ditinjau manual</option>
                    <option value="ditolak_otomatis" @selected($settings->out_of_radius_policy === 'ditolak_otomatis')>Ditolak otomatis</option>
                </select>
            </div>

            <div class="field-checkbox">
                <input type="checkbox" id="photo_required" name="photo_required" value="1"
                       @checked(old('photo_required', $settings->photo_required))>
                <label for="photo_required" style="margin:0;">Foto wajib saat absen</label>
            </div>

            <button type="submit" class="btn-gold">Simpan Aturan</button>
        </form>
    </div>
</div>
@endsection