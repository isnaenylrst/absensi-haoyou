@extends('owner.dashboard')
@section('title', 'Import Karyawan')

@push('styles')
<style>
    .import-card { background: #fff; border: 1px solid #EDEEF0; border-radius: 16px; padding: 28px; max-width: 560px; }
    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: 12.5px; font-weight: 600; color: #6B7280; margin-bottom: 6px; }
    .field input[type=file] {
        width: 100%; padding: 11px 13px; border: 1px dashed #EDEEF0; border-radius: 10px;
        font-family: 'Poppins', sans-serif; font-size: 13px;
    }
    .btn-gold {
        background: #ffbd08; color: #fff; border: none; padding: 11px 22px;
        border-radius: 10px; font-family: 'Poppins', sans-serif; font-weight: 700;
        font-size: 13.5px; cursor: pointer;
    }
    .btn-gold:hover { background: #DE8C0F; }
    .hint { font-size: 12.5px; color: #6B7280; line-height: 1.6; margin-bottom: 18px; }
    .hint code { background: #F7F8FA; padding: 1px 6px; border-radius: 5px; font-size: 11.5px; }
    .template-link { font-size: 12.5px; color: #DE8C0F; text-decoration: none; font-weight: 600; }
    .alert-error {
        background: #FCEAE7; color: #D34D3C; border: 1px solid #F5CFC8;
        border-radius: 10px; padding: 11px 14px; font-size: 12.5px; margin-bottom: 16px;
    }
</style>
@endpush

@section('content')
<div class="breadcrumb" style="font-size:12.5px; color:#9AA0A8; margin-bottom:14px;">
    <a href="{{ route('dashboard') }}" style="color:#9AA0A8; text-decoration:none;">Home</a> &rsaquo;
    <a href="{{ route('karyawan.index') }}" style="color:#9AA0A8; text-decoration:none;">Karyawan</a> &rsaquo;
    <b style="color:#22262B;">Import</b>
</div>

<h1 style="font-size:22px; font-weight:700; margin-bottom:20px;">Import Karyawan dari CSV</h1>

@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="import-card">
    <div class="hint">
        Format file harus <b>.csv</b> dengan kolom berikut (urutan harus sama):<br>
        <code>full_name, branch_name, gender, religion, blood_type, phone, email, address, position, employee_type, join_date, nik</code><br><br>
        <code>branch_name</code> harus persis sama dengan nama cabang yang sudah ada di sistem.
        <code>employee_type</code> hanya boleh diisi <code>tetap</code> atau <code>part_time</code>.
        Akun login akan dibuat otomatis untuk setiap baris yang berhasil diimport.<br><br>
        <a href="{{ route('karyawan.import.template') }}" class="template-link">&#8595; Download Template CSV</a>
    </div>

    <form method="POST" action="{{ route('karyawan.import.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="field">
            <label>Pilih File CSV</label>
            <input type="file" name="file" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn-gold">Mulai Import</button>
    </form>
</div>
@endsection