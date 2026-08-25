{{-- Detail jadwal bulanan satu guru --}}
@extends('owner.dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
@endpush

@section('content')
@php
  $avatarColors = ['#8B5CF6', '#2E6FDB', '#E8863A', '#D34D9C', '#2F8A5B', '#D34D3C'];
  $avatarColor = $avatarColors[$employee->id % count($avatarColors)];
  $namaHari = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
  ];
@endphp

<div class="crumb">
  <a href="{{ route('jadwal-kerja') }}"><b>Jadwal Kerja</b></a> / Jadwal Bulanan
</div>

<div class="pb-topbar">
  <div class="pb-employee">
    <div class="pb-avatar" style="background:{{ $avatarColor }};">{{ $employee->initials() }}</div>
    <div>
      <div class="pb-name">{{ $employee->full_name }}</div>
      <div class="pb-sub">{{ $employee->position ?? 'Guru' }} &middot; {{ $employee->branch->name ?? '—' }}</div>
    </div>
  </div>

  <div class="page-actions">
    <a href="{{ route('jadwal-kerja') }}" class="btn btn-line btn-sm">Kembali</a>
    <button type="button" class="btn btn-gold btn-sm">Edit Jadwal</button>
  </div>
</div>

<form method="GET" action="{{ route('jadwal-kerja.guru-bulanan', $employee->id) }}" class="pb-month-form pb-month-form-page">
  <label for="jadwal-bulan">Bulan</label>
  <select id="jadwal-bulan" name="bulan" onchange="this.form.submit()">
    @foreach ($daftarBulan as $num => $label)
      <option value="{{ $num }}" @selected($num == $bulan)>{{ $label }}</option>
    @endforeach
  </select>
  <label for="jadwal-tahun">Tahun</label>
  <select id="jadwal-tahun" name="tahun" onchange="this.form.submit()">
    @foreach ($daftarTahun as $tahunOpsi)
      <option value="{{ $tahunOpsi }}" @selected($tahunOpsi == $tahun)>{{ $tahunOpsi }}</option>
    @endforeach
  </select>
</form>

<div class="pb-week-navigation" aria-label="Navigasi minggu">
  <button type="button" class="btn btn-line btn-sm" id="jadwalPreviousWeek">&larr; Sebelumnya</button>
  <span class="pb-week-indicator" id="jadwalWeekIndicator">Minggu 1 dari {{ $mingguan->count() }}</span>
  <button type="button" class="btn btn-line btn-sm" id="jadwalNextWeek">Selanjutnya &rarr;</button>
</div>

@foreach ($mingguan as $mingguKe => $hariList)
  <div class="pb-week pb-week-page jadwal-guru-week">
    <div class="pb-week-head">
      <div class="pb-week-title">Minggu {{ $mingguKe }}</div>
      <div class="pb-week-range">
        {{ $hariList->first()->tanggal->translatedFormat('d M') }} &ndash; {{ $hariList->last()->tanggal->translatedFormat('d M Y') }}
      </div>
    </div>

    <div class="week-grid jadwal-guru-week-grid">
      @foreach ($hariList as $hari)
        <div class="week-day">
          <div class="week-day-label">{{ $namaHari[$hari->tanggal->format('l')] ?? $hari->tanggal->format('l') }}</div>
          <div class="field-hint">{{ $hari->tanggal->format('d M Y') }}</div>
          @forelse ($hari->sesi as $sesi)
            <div class="session-chip">
              <div class="session-chip-time">{{ $sesi->jam_mulai }}–{{ $sesi->jam_selesai }}</div>
              <div class="session-chip-label">{{ $sesi->kegiatan ?? 'Mengajar Kelas' }}</div>
            </div>
          @empty
            <div class="week-day-empty">— Libur</div>
          @endforelse
        </div>
      @endforeach
    </div>
  </div>
@endforeach

@if ($mingguan->isEmpty())
  <div class="card"><span class="kv-lbl">Belum ada jadwal untuk guru ini.</span></div>
@endif

<script>
  (() => {
    const weeks = Array.from(document.querySelectorAll('.jadwal-guru-week'));
    const previousButton = document.getElementById('jadwalPreviousWeek');
    const nextButton = document.getElementById('jadwalNextWeek');
    const indicator = document.getElementById('jadwalWeekIndicator');
    let currentWeek = 0;

    function showWeek(index) {
      currentWeek = Math.max(0, Math.min(index, weeks.length - 1));
      weeks.forEach((week, weekIndex) => {
        week.hidden = weekIndex !== currentWeek;
      });
      indicator.textContent = `Minggu ${currentWeek + 1} dari ${weeks.length}`;
      previousButton.disabled = currentWeek === 0;
      nextButton.disabled = currentWeek === weeks.length - 1;
    }

    if (weeks.length) {
      previousButton.addEventListener('click', () => showWeek(currentWeek - 1));
      nextButton.addEventListener('click', () => showWeek(currentWeek + 1));
      showWeek(0);
    } else {
      previousButton.disabled = true;
      nextButton.disabled = true;
      indicator.textContent = 'Tidak ada minggu';
    }
  })();
</script>
@endsection
