@extends('owner.dashboard')

@section('title', 'Gaji Saya')

@section('content')
<div class="crumb">Home <span></span> <b>Gaji Saya</b></div>
<div class="page-title" style="margin-bottom:18px;">Gaji Saya+</div>

<div class="card locked-panel">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-faint)" stroke-width="1.6"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
    <div class="locked-panel-title">Kelola gaji tim di menu Karyawan</div>
    <div class="field-hint">Sebagai Owner, atur gaji tiap karyawan lewat <b>Karyawan → tab Payroll</b>.</div>
</div>
@endsection