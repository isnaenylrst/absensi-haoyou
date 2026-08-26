@extends('owner.dashboard')
@section('title', 'Tambah Karyawan')

@section('content')
<div class="breadcrumb" style="font-size:12.5px; color:#9AA0A8; margin-bottom:14px;">
    <a href="{{ route('dashboard') }}" style="color:#9AA0A8; text-decoration:none;">Home</a> &rsaquo;
    <a href="{{ route('karyawan.index') }}" style="color:#9AA0A8; text-decoration:none;">Karyawan</a> &rsaquo;
    <b style="color:#22262B;">Tambah</b>
</div>

<h1 style="font-size:22px; font-weight:700; margin-bottom:20px;">Tambah Karyawan</h1>

@if ($errors->any())
    <div style="background:#FCEAE7; color:#D34D3C; border:1px solid #F5CFC8; border-radius:10px; padding:11px 14px; font-size:12.5px; margin-bottom:16px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="form-card">
    <form method="POST" action="{{ route('karyawan.store') }}">
        @csrf
        @include('owner.karyawan._form')
    </form>
</div>
@endsection