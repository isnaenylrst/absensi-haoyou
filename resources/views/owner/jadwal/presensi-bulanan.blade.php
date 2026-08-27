{{-- resources/views/owner/jadwal/presensi-bulanan.blade.php --}}
@extends('owner.dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
@endpush

@section('content')

<div class="crumb">
  <a href="{{ route('jadwal-kerja') }}"><b>Jadwal Kerja</b></a> / Presensi Bulanan
</div>

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

<div class="pb-topbar">
  <div class="pb-employee">
    <div class="pb-avatar" style="background:{{ $avatarColor }};">{{ $employee->initials() }}</div>
    <div>
      <div class="pb-name">{{ $employee->full_name }}</div>
      <div class="pb-sub">
        Karyawan Tetap &middot; {{ $employee->branch->name ?? '—' }}
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('jadwal-kerja.presensi-bulanan', $employee->id) }}" class="pb-month-form">
    <select name="bulan" onchange="this.form.submit()">
      @foreach ($daftarBulan as $num => $label)
        <option value="{{ $num }}" @selected($num == $bulan)>{{ $label }}</option>
      @endforeach
    </select>
    <select name="tahun" onchange="this.form.submit()">
      @foreach ($daftarTahun as $tahunOpsi)
        <option value="{{ $tahunOpsi }}" @selected($tahunOpsi == $tahun)>{{ $tahunOpsi }}</option>
      @endforeach
    </select>
  </form>
</div>

<div class="pb-summary">
  <div class="pb-summary-chip ok"><div class="pb-num">{{ $summary['tepat_waktu'] }}</div><div class="pb-label">Tepat waktu</div></div>
  <div class="pb-summary-chip late"><div class="pb-num">{{ $summary['terlambat'] }}</div><div class="pb-label">Terlambat</div></div>
  <div class="pb-summary-chip out"><div class="pb-num">{{ $summary['luar_radius'] }}</div><div class="pb-label">Di luar radius</div></div>
  <div class="pb-summary-chip"><div class="pb-num">{{ $summary['alpa'] }}</div><div class="pb-label">Alpa</div></div>
</div>

<div class="pb-week-navigation" aria-label="Navigasi minggu">
  <button type="button" class="btn btn-line btn-sm" id="pbPreviousWeek">&larr; Sebelumnya</button>
  <span class="pb-week-indicator" id="pbWeekIndicator">Minggu 1 dari {{ $mingguan->count() }}</span>
  <button type="button" class="btn btn-line btn-sm" id="pbNextWeek">Selanjutnya &rarr;</button>
</div>

@foreach ($mingguan as $mingguKe => $hariList)
  <div class="pb-week pb-week-page">
    <div class="pb-week-head">
      <div class="pb-week-title">Minggu {{ $mingguKe }}</div>
      <div class="pb-week-range">
        {{ $hariList->first()->tanggal->translatedFormat('d M') }} &ndash; {{ $hariList->last()->tanggal->translatedFormat('d M Y') }}
      </div>
    </div>

    <div class="table-wrap">
      <table class="jadwal-approval-table">
        <tr>
          <th>Tanggal</th>
          <th>Cabang</th>
          <th>Shift / Jadwal</th>
          <th>Masuk</th>
          <th>Pulang</th>
          <th>Radius</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>

        @foreach ($hariList as $hari)
          @php $attendance = $hari->attendance; @endphp
          <tr>
            <td>
              <div class="pb-tgl">
                <span class="pb-tgl-hari">{{ $namaHari[$hari->tanggal->format('l')] ?? $hari->tanggal->format('l') }}</span>
                <span class="pb-tgl-tanggal">{{ $hari->tanggal->translatedFormat('d M Y') }}</span>
              </div>
            </td>
            <td>{{ $employee->branch->name ?? '—' }}</td>
            <td>{{ $attendance ? $hari->jadwal : '—' }}</td>
            <td class="mono">{{ $attendance?->check_in?->format('H:i') ?? '—' }}</td>
            <td class="mono">{{ $attendance?->check_out?->format('H:i') ?? '—' }}</td>
            <td class="mono">
              @if ($hari->distance !== null)
                <span class="{{ $hari->is_out_of_radius ? 'pb-radius-out' : '' }}">{{ number_format($hari->distance, 0) }} m</span>
              @else
                —
              @endif
            </td>
            <td>
              @if ($attendance)
                <span class="badge {{ $hari->status_class }}">{{ $hari->status_label }}</span>
                @if ($hari->is_out_of_radius)
                  <span class="badge badge-rust">Di Luar Radius</span>
                @endif
              @else
                <span class="pb-empty">Belum ada data presensi</span>
              @endif
            </td>
            <td>
                <button type="button" class="btn btn-gold btn-xs" @if(!$attendance) disabled @endif
                  @if($attendance) onclick="openApprovalModal({{ $attendance->id }})" @endif>
                  Detail
                </button>
            </td>
          </tr>

          {{-- Template modal — struktur SAMA PERSIS dengan yang di tabel Approval Presensi,
               supaya openApprovalModal() yang sudah ada bisa langsung dipakai --}}
          @if ($attendance)
            <template id="modal-data-{{ $attendance->id }}">
              <button type="button" class="modal-close" onclick="closeApprovalModal()" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
              </button>

              <div class="modal-photo-panel">
                <div class="modal-photo-cell">
                  <span class="modal-photo-tag">CHECK IN &middot; {{ $attendance->check_in?->format('H:i') ?? '—' }}</span>
                  @if ($attendance->check_in_photo_url)
                    <img src="{{ asset('storage/' . $attendance->check_in_photo_url) }}" alt="Foto check-in {{ $employee->full_name }}">
                  @else
                    <div class="modal-photo-empty">
                      <div class="modal-photo-empty-inner"><i class="ti ti-photo" style="font-size:18px;"></i>Tidak ada foto</div>
                    </div>
                  @endif
                </div>
                <div class="modal-photo-cell">
                  <span class="modal-photo-tag">CHECK OUT &middot; {{ $attendance->check_out?->format('H:i') ?? '—' }}</span>
                  @if ($attendance->check_out_photo_url)
                    <img src="{{ asset('storage/' . $attendance->check_out_photo_url) }}" alt="Foto check-out {{ $employee->full_name }}">
                  @else
                    <div class="modal-photo-empty">
                      <div class="modal-photo-empty-inner"><i class="ti ti-photo" style="font-size:18px;"></i>Tidak ada foto</div>
                    </div>
                  @endif
                </div>
              </div>

              <div class="modal-content">
                <div class="modal-content-head">
                  <div class="modal-employee-block">
                    <div>
                      <div class="modal-employee-name">{{ $employee->full_name }}</div>
                      <div class="modal-employee-sub">{{ $employee->position ?? '-' }} &middot; Tetap</div>
                    </div>
                  </div>
                </div>

                <div class="modal-info-list">
                  <div class="modal-info-row">
                    <span class="modal-info-label">Status</span>
                    <span class="modal-info-value">
                      <span class="badge {{ $hari->status_class }}">{{ $hari->status_label }}</span>
                      @if ($hari->is_out_of_radius)
                        <span class="badge badge-rust">Di luar radius</span>
                      @endif
                    </span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Tanggal</span>
                    <span class="modal-info-value">{{ $attendance->tanggal->translatedFormat('d F Y') }}</span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Shift</span>
                    <span class="modal-info-value">{{ $hari->jadwal }}</span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Jam masuk / pulang</span>
                    <span class="modal-info-value mono">{{ $attendance->check_in?->format('H:i') ?? '—' }} &ndash; {{ $attendance->check_out?->format('H:i') ?? '—' }}</span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Jarak dari kantor</span>
                    <span class="modal-info-value {{ $hari->is_out_of_radius ? 'text-rust' : '' }}">{{ $hari->distance !== null ? number_format($hari->distance, 0) . ' m' : '—' }}</span>
                  </div>
                </div>

                <div class="modal-actions">
                  @if ($attendance->check_in_lat && $attendance->check_in_lng)
                    <a href="https://www.google.com/maps?q={{ $attendance->check_in_lat }},{{ $attendance->check_in_lng }}" target="_blank" class="modal-action-btn line">
                      <i class="ti ti-map-pin" style="font-size:15px;"></i> Lihat lokasi di peta
                    </a>
                  @else
                    <button type="button" class="modal-action-btn line" disabled>
                      <i class="ti ti-map-pin" style="font-size:15px;"></i> Lihat lokasi di peta
                    </button>
                  @endif
                </div>
              </div>
            </template>
          @endif
        @endforeach
      </table>
    </div>
  </div>
@endforeach

<div class="note-box">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
    <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>
  </svg>
  <div>Status ditentukan otomatis oleh sistem berdasarkan jam masuk vs jadwal. Absensi dengan jarak &gt;100 m dari titik kantor tetap tercatat dan perlu ditinjau manual.</div>
</div>

<!-- Modal dipakai bersama, sama seperti di halaman Jadwal Kerja -->
<div class="modal-overlay" id="approvalModalOverlay" onclick="closeApprovalModal(event)">
  <div class="modal-box" id="approvalModalBody" onclick="event.stopPropagation()"></div>
</div>

<script>
  (() => {
    const weeks = Array.from(document.querySelectorAll('.pb-week-page'));
    const previousButton = document.getElementById('pbPreviousWeek');
    const nextButton = document.getElementById('pbNextWeek');
    const indicator = document.getElementById('pbWeekIndicator');
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

    previousButton.addEventListener('click', () => showWeek(currentWeek - 1));
    nextButton.addEventListener('click', () => showWeek(currentWeek + 1));
    showWeek(0);
  })();
</script>

@endsection