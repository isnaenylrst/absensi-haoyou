@extends('owner.dashboard')

@php
    $judulKategori = [
        'masuk' => 'Masuk',
        'cuti'  => 'Cuti',
        'alpa'  => 'Alpa',
    ][$kategori] ?? ucfirst($kategori);
@endphp

@section('title', 'Presensi '.$judulKategori.' | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
@endpush

@section('content')

    @php
        $modeAktif = $filters['mode'] ?? 'range';
    @endphp

    <!-- Presensi {{ $judulKategori }} -->
    <div class="crumb">
      Home <span>›</span> Kehadiran <span>›</span>
      <a href="{{ route('jadwal-kerja') }}">Jadwal Kerja</a> <span>›</span>
      <b>{{ $judulKategori }}</b>
    </div>
    <div class="page-head"><div class="page-title">Presensi &mdash; {{ $judulKategori }}</div></div>

    <form method="GET" action="{{ route('owner.presensi.kategori', $kategori) }}" class="toolbar" id="filterForm">
      <input type="hidden" name="mode" id="modeInput" value="{{ $modeAktif }}">
      <div class="toolbar-left" style="flex-wrap:wrap; gap:8px;">
        <div class="search-box">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9AA0A8" stroke-width="2">
            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
          </svg>
          <input type="text" name="q" id="filterSearch" placeholder="Cari nama karyawan..." value="{{ $filters['q'] ?? '' }}">
        </div>

        <div style="display:inline-flex; gap:6px;">
          <button type="button" class="btn btn-xs {{ $modeAktif === 'range' ? 'btn-gold' : 'btn-line' }}" id="btnModeRange">Rentang Tanggal</button>
          <button type="button" class="btn btn-xs {{ $modeAktif === 'bulanan' ? 'btn-gold' : 'btn-line' }}" id="btnModeBulanan">Bulanan</button>
        </div>

        <span id="rangeFields" style="{{ $modeAktif === 'bulanan' ? 'display:none;' : '' }}">
          <input type="text" id="filterDateRange" class="field-input-inline" placeholder="Pilih rentang tanggal" autocomplete="off" style="min-width:190px;">
          <input type="hidden" name="tanggal_mulai" id="tanggalMulaiInput" value="{{ $filters['tanggal_mulai'] ?? $tanggalMulai->toDateString() }}">
          <input type="hidden" name="tanggal_akhir" id="tanggalAkhirInput" value="{{ $filters['tanggal_akhir'] ?? $tanggalAkhir->toDateString() }}">
        </span>

        <span id="bulananFields" style="{{ $modeAktif === 'bulanan' ? '' : 'display:none;' }}">
          <select name="bulan" class="field-input-inline">
            @foreach ($daftarBulan as $angka => $nama)
              <option value="{{ $angka }}" @selected((int) ($filters['bulan'] ?? $tanggalMulai->month) === $angka)>{{ $nama }}</option>
            @endforeach
          </select>
          <select name="tahun" class="field-input-inline">
            @foreach ($daftarTahun as $tahunOpt)
              <option value="{{ $tahunOpt }}" @selected((int) ($filters['tahun'] ?? $tanggalMulai->year) === $tahunOpt)>{{ $tahunOpt }}</option>
            @endforeach
          </select>
        </span>

        <select name="branch_id" class="field-input-inline">
          <option value="">Semua Cabang</option>
          @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
              {{ $branch->name }}
            </option>
          @endforeach
        </select>

        @if ($kategori === 'masuk')
        <select name="status" class="field-input-inline">
          <option value="">Semua Status</option>
          <option value="tepat_waktu" @selected(($filters['status'] ?? null) === 'tepat_waktu')>Tepat Waktu</option>
          <option value="terlambat" @selected(($filters['status'] ?? null) === 'terlambat')>Terlambat</option>
          <option value="luar_radius" @selected(($filters['status'] ?? null) === 'luar_radius')>Di Luar Radius</option>
          <option value="tidak_checkout" @selected(($filters['status'] ?? null) === 'tidak_checkout')>Tidak Checkout</option>
        </select>
        @endif

        <button type="submit" class="btn btn-gold btn-sm">Terapkan Filter</button>
      </div>
    </form>

    <div class="quota-chip" style="margin:14px 0;">
      Menampilkan <b>{{ $attendances->count() }}</b> dari <b>{{ $attendances->total() }}</b> data
    </div>

    <div class="card">
      <div class="table-wrap">
        <table class="jadwal-approval-table">
          <tr>
            <th>Karyawan</th>
            <th>Tanggal</th>
            <th>Tipe</th>
            <th>Cabang</th>
            <th>Shift / Jadwal</th>
            <th>Masuk</th>
            <th>Pulang</th>
            <th>Radius</th>
            <th style="text-align: center;">Status</th>
            <th style="text-align: center;">Aksi</th>
          </tr>

          @forelse ($attendances as $attendance)
            @php
              $employee = $attendance->employee;
              $isTetap = $employee->employee_type === 'tetap';
              $sudahAbsen = $attendance->sudah_absen ?? true;

              $jadwalLabel = $attendance->shift?->name ?? '—';
              $distance = $attendance->distance_m;
              $isOutOfRadius = $distance !== null && $distance > 100;

              $statusMap = [
                  'tepat_waktu'    => ['label' => 'Tepat Waktu', 'class' => 'badge-green'],
                  'terlambat'      => ['label' => $attendance->late_label ?? 'Terlambat', 'class' => 'badge-rust'],
                  'tidak_checkout' => ['label' => 'Tidak Checkout', 'class' => 'badge-orange'],
                  'cuti'           => ['label' => 'Cuti', 'class' => 'badge-blue'],
                  'alpa'           => ['label' => 'Alpa', 'class' => 'badge-gray-dark'],
              ];

              $statusInfo = $sudahAbsen
                  ? ($statusMap[$attendance->status] ?? [
                      'label' => ucfirst(str_replace('_', ' ', $attendance->status ?? '-')),
                      'class' => 'badge-gray',
                  ])
                  : ['label' => $attendance->status_label ?? 'Belum melakukan absensi', 'class' => 'badge-gray'];

              $initials = $employee->initials();
              $avatarColors = ['#8B5CF6', '#2E6FDB', '#E8863A', '#D34D9C', '#2F8A5B', '#D34D3C'];
              $avatarColor = $avatarColors[$employee->id % count($avatarColors)];

              // Key unik untuk modal detail
              $modalKey = $attendance->id ?? 'row-'.$employee->id.'-'.$attendance->tanggal->format('Ymd');
            @endphp
            <tr>
              <td class="row-name">
                <div class="avatar-dot" style="background:{{ $avatarColor }};">{{ $initials }}</div>
                {{ $employee->full_name }}
              </td>
              <td class="mono">{{ $attendance->tanggal->translatedFormat('d M Y') }}</td>
              <td>
                <span class="badge {{ $isTetap ? 'badge-blue' : 'badge-gray' }}">
                  {{ $isTetap ? 'Tetap' : 'Part Time' }}
                </span>
              </td>
              <td>{{ $employee->branch->name ?? '—' }}</td>
              <td>{{ $jadwalLabel }}</td>
              <td class="mono">{{ $attendance->check_in?->format('H:i') ?? '—' }}</td>
              <td class="mono">{{ $attendance->check_out?->format('H:i') ?? '—' }}</td>
              <td class="mono">
                @if ($distance !== null)
                  <span class="{{ $isOutOfRadius ? 'text-rust' : '' }}">{{ number_format($distance, 0) }} m</span>
                @else
                  —
                @endif
              </td>
              <td style="text-align:center;">
                <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                @if ($isOutOfRadius)
                  <span class="badge badge-rust">Di Luar Radius</span>
                @endif
              </td>
              <td class="jadwal-action-cell">
                <div class="jadwal-action-buttons">
                  <button type="button" class="btn btn-gold btn-xs" onclick="openDetailModal('{{ $modalKey }}')">
                    Detail
                  </button>
                </div>
              </td>
            </tr>

            <template id="modal-data-{{ $modalKey }}">
              <button type="button" class="modal-close" onclick="closeDetailModal()" aria-label="Tutup">
                  <i class="fa-solid fa-xmark"></i>
              </button>

              @if ($attendance->check_in)
              <div class="modal-photo-panel">
                <div class="modal-photo-cell">
                  <span class="modal-photo-tag">CHECK IN &middot; {{ $attendance->check_in?->format('H:i') ?? '—' }}</span>
                  @if ($attendance->check_in_photo_url)
                    <img src="{{ asset('storage/' . $attendance->check_in_photo_url) }}" alt="Foto check-in {{ $employee->full_name }}"
                        onerror="this.replaceWith(Object.assign(document.createElement('div'), {className:'modal-photo-empty', innerHTML:'<div class=&quot;modal-photo-empty-inner&quot;><i class=&quot;ti ti-photo-off&quot; style=&quot;font-size:18px;&quot;></i>Gagal dimuat</div>'}))">
                  @else
                    <div class="modal-photo-empty">
                      <div class="modal-photo-empty-inner">
                        <i class="ti ti-photo" style="font-size:18px;"></i>
                        Tidak ada foto
                      </div>
                    </div>
                  @endif
                </div>

                <div class="modal-photo-cell">
                  <span class="modal-photo-tag">CHECK OUT &middot; {{ $attendance->check_out?->format('H:i') ?? '—' }}</span>
                  @if ($attendance->check_out_photo_url)
                    <img src="{{ asset('storage/' . $attendance->check_out_photo_url) }}" alt="Foto check-out {{ $employee->full_name }}"
                        onerror="this.replaceWith(Object.assign(document.createElement('div'), {className:'modal-photo-empty', innerHTML:'<div class=&quot;modal-photo-empty-inner&quot;><i class=&quot;ti ti-photo-off&quot; style=&quot;font-size:18px;&quot;></i>Gagal dimuat</div>'}))">
                  @else
                    <div class="modal-photo-empty">
                      <div class="modal-photo-empty-inner">
                        <i class="ti ti-photo" style="font-size:18px;"></i>
                        Tidak ada foto
                      </div>
                    </div>
                  @endif
                </div>
              </div>
              @endif

              <div class="modal-content" style="{{ $attendance->check_in ? '' : 'padding-top:8px;' }}">
                <div class="modal-content-head">
                  <div class="modal-employee-block">
                    <div>
                      <div class="modal-employee-name">{{ $employee->full_name }}</div>
                      <div class="modal-employee-sub">{{ $employee->position ?? '-' }} &middot; {{ $isTetap ? 'Tetap' : 'Part Time' }}</div>
                    </div>
                  </div>
                </div>

                <div class="modal-info-list">
                  <div class="modal-info-row">
                    <span class="modal-info-label">Status</span>
                    <span class="modal-info-value">
                      <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                      @if ($isOutOfRadius)
                        <span class="badge badge-rust">Di luar radius</span>
                      @endif
                    </span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Tanggal</span>
                    <span class="modal-info-value">{{ $attendance->tanggal->translatedFormat('d F Y') }}</span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Cabang</span>
                    <span class="modal-info-value">{{ $employee->branch->name ?? '—' }}</span>
                  </div>
                  @if ($attendance->check_in)
                  <div class="modal-info-row">
                    <span class="modal-info-label">Shift</span>
                    <span class="modal-info-value">{{ $jadwalLabel }}</span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Jam masuk / pulang</span>
                    <span class="modal-info-value mono">{{ $attendance->check_in?->format('H:i') ?? '—' }} &ndash; {{ $attendance->check_out?->format('H:i') ?? '—' }}</span>
                  </div>
                  <div class="modal-info-row">
                    <span class="modal-info-label">Jarak dari kantor</span>
                    <span class="modal-info-value {{ $isOutOfRadius ? 'text-rust' : '' }}">{{ $distance !== null ? number_format($distance, 0) . ' m' : '—' }}</span>
                  </div>
                  @endif
                </div>

                @if ($attendance->activity ?? null)
                <div class="modal-block notes">
                    <div class="modal-block-label">Aktivitas</div>
                    <p>{{ $attendance->activity }}</p>
                </div>
                @endif

                {{-- Override status --}}
                <div class="modal-block override-status">
                  <div class="modal-block-label">Ubah status secara manual</div>
                  @if ($attendance->id)
                    <form method="POST" action="{{ route('owner.attendance.status.update', $attendance->id) }}" class="modal-override-form" style="display:flex; gap:8px; align-items:stretch;">
                      @csrf
                      @method('PUT')
                      <select name="status" class="field-input-inline" style="flex:1;">
                        <option value="tepat_waktu" @selected($attendance->status === 'tepat_waktu')>Tepat Waktu</option>
                        <option value="terlambat" @selected($attendance->status === 'terlambat')>Terlambat</option>
                        <option value="tidak_checkout" @selected($attendance->status === 'tidak_checkout')>Tidak Checkout</option>
                        <option value="cuti" @selected($attendance->status === 'cuti')>Cuti</option>
                        <option value="alpa" @selected($attendance->status === 'alpa')>Alpa</option>
                      </select>
                      <button type="submit" class="btn btn-gold btn-xs" style="white-space:nowrap; flex-shrink:0;">Simpan Status</button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('owner.attendance.status.override') }}" class="modal-override-form" style="display:flex; gap:8px; align-items:stretch;">
                      @csrf
                      <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                      <input type="hidden" name="tanggal" value="{{ $attendance->tanggal->format('Y-m-d') }}">
                      <select name="status" class="field-input-inline" style="flex:1;">
                        <option value="tepat_waktu" @selected($attendance->status === 'tepat_waktu')>Tepat Waktu</option>
                        <option value="terlambat" @selected($attendance->status === 'terlambat')>Terlambat</option>
                        <option value="tidak_checkout" @selected($attendance->status === 'tidak_checkout')>Tidak Checkout</option>
                        <option value="cuti" @selected($attendance->status === 'cuti')>Cuti</option>
                        <option value="alpa" @selected(($attendance->status ?? 'alpa') === 'alpa')>Alpa</option>
                      </select>
                      <button type="submit" class="btn btn-gold btn-xs" style="white-space:nowrap; flex-shrink:0;">Simpan Status</button>
                    </form>
                  @endif
                </div>

                @if ($attendance->check_in_lat && $attendance->check_in_lng)
                <div class="modal-actions">
                    <a href="https://www.google.com/maps?q={{ $attendance->check_in_lat }},{{ $attendance->check_in_lng }}" target="_blank" class="modal-action-btn line">
                    <i class="ti ti-map-pin" style="font-size:15px;"></i> Lihat lokasi di peta
                    </a>
                </div>
                @endif
              </div>
            </template>
          @empty
            <tr>
              <td colspan="10" class="empty-state">Tidak ada data presensi untuk filter ini.</td>
            </tr>
          @endforelse
        </table>
      </div>

      @if ($attendances->hasPages())
        @php
          $current = $attendances->currentPage();
          $last = $attendances->lastPage();
          $window = 1; // jumlah halaman di kiri & kanan halaman aktif yang ditampilkan penuh

          $pages = collect([1]);
          for ($p = $current - $window; $p <= $current + $window; $p++) {
              if ($p > 1 && $p < $last) {
                  $pages->push($p);
              }
          }
          if ($last > 1) {
              $pages->push($last);
          }
          $pages = $pages->unique()->sort()->values();
        @endphp

        <div class="pg-bar">
          <div class="pg-info">
            Menampilkan <b>{{ $attendances->firstItem() }}</b> &ndash; <b>{{ $attendances->lastItem() }}</b>
            dari <b>{{ $attendances->total() }}</b> data
          </div>

          <div class="pg-nav">
            @if ($attendances->onFirstPage())
              <span class="pg-btn disabled" aria-label="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></span>
            @else
              <a href="{{ $attendances->previousPageUrl() }}" class="pg-btn" aria-label="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></a>
            @endif

            @foreach ($pages as $i => $page)
              @if ($i > 0 && $page - $pages[$i - 1] > 1)
                <span class="pg-ellipsis">&hellip;</span>
              @endif

              @if ($page == $current)
                <span class="pg-btn active">{{ $page }}</span>
              @else
                <a href="{{ $attendances->url($page) }}" class="pg-btn">{{ $page }}</a>
              @endif
            @endforeach

            @if ($attendances->hasMorePages())
              <a href="{{ $attendances->nextPageUrl() }}" class="pg-btn" aria-label="Selanjutnya"><i class="fa-solid fa-chevron-right"></i></a>
            @else
              <span class="pg-btn disabled" aria-label="Selanjutnya"><i class="fa-solid fa-chevron-right"></i></span>
            @endif
          </div>
        </div>
      @endif
    </div>

    <div class="modal-overlay" id="detailModalOverlay" onclick="closeDetailModal(event)">
      <div class="modal-box" id="detailModalBody" onclick="event.stopPropagation()"></div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script>
  // Toast
  let toastTimer;
  function showToast(msg){
    let toast = document.getElementById('appToast');
    if(!toast){
      toast = document.createElement('div');
      toast.id = 'appToast';
      toast.className = 'toast';
      toast.innerHTML = '<span class="toast-dot"></span><span id="appToastMsg"></span>';
      document.body.appendChild(toast);
    }
    document.getElementById('appToastMsg').textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
  }

  // Modal detail
  function openDetailModal(id) {
    const source = document.getElementById('modal-data-' + id);
    if (!source) return;
    document.getElementById('detailModalBody').innerHTML = source.innerHTML;
    document.getElementById('detailModalOverlay').classList.add('show');
  }
  function closeDetailModal(e) {
    const overlay = document.getElementById('detailModalOverlay');
    if (overlay) overlay.classList.remove('show');
  }

  // Filter
  const filterForm = document.getElementById('filterForm');
  const modeInput = document.getElementById('modeInput');
  const rangeFields = document.getElementById('rangeFields');
  const bulananFields = document.getElementById('bulananFields');

  filterForm.querySelectorAll('select[name="branch_id"], select[name="status"], select[name="bulan"], select[name="tahun"]').forEach((select) => {
    select.addEventListener('change', () => filterForm.requestSubmit());
  });

  // Mode filter
  document.getElementById('btnModeRange').addEventListener('click', () => {
    modeInput.value = 'range';
    rangeFields.style.display = '';
    bulananFields.style.display = 'none';
  });
  document.getElementById('btnModeBulanan').addEventListener('click', () => {
    modeInput.value = 'bulanan';
    rangeFields.style.display = 'none';
    bulananFields.style.display = '';
    filterForm.requestSubmit();
  });

  // Rentang tanggal
  const dateRangeInput = document.getElementById('filterDateRange');
  if (dateRangeInput) {
    const mulaiInput = document.getElementById('tanggalMulaiInput');
    const akhirInput = document.getElementById('tanggalAkhirInput');

    flatpickr(dateRangeInput, {
      mode: 'range',
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'd/m/Y',
      defaultDate: [mulaiInput.value, akhirInput.value],
      onClose: function (selectedDates, dateStr, instance) {
        if (selectedDates.length === 2) {
          mulaiInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          akhirInput.value = instance.formatDate(selectedDates[1], 'Y-m-d');
          modeInput.value = 'range';
          filterForm.requestSubmit();
        } else if (selectedDates.length === 1) {
          mulaiInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          akhirInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          modeInput.value = 'range';
          filterForm.requestSubmit();
        }
      },
    });
  }

  @if(session('success'))
    showToast('{{ session('success') }}');
  @endif
</script>

@endsection