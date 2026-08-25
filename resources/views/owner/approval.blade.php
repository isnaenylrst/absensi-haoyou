@extends('owner.dashboard')

@section('title', 'Approval Presensi | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/approval-presensi.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
@endpush

@section('content')

      <!-- ============ APPROVAL PRESENSI (Owner only) ============ -->
        <div class="crumb">Home <span>&rsaquo;</span> Kehadiran <span>&rsaquo;</span> <b>Approval Presensi</b></div>
        <div class="page-head"><div class="page-title">Approval Presensi</div></div>

        <form method="GET" action="{{ route('approval') }}" class="toolbar" id="filterForm">
          <div class="toolbar-left">
            <div class="search-box">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9AA0A8" stroke-width="2">
                <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
              </svg>
              <input type="text" name="q" id="filterSearch" placeholder="Cari nama karyawan..." value="{{ $filters['q'] ?? '' }}">
            </div>

            <input type="date" name="tanggal" class="field-input-inline" onchange="this.form.submit()"
                  value="{{ $filters['tanggal'] ?? now()->toDateString() }}">

            <select name="branch_id" class="field-input-inline" onchange="this.form.submit()">
              <option value="">Semua Cabang</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
                  {{ $branch->name }}
                </option>
              @endforeach
            </select>

            <select name="employee_type" class="field-input-inline" onchange="this.form.submit()">
              <option value="">Semua Tipe</option>
              <option value="tetap" @selected(($filters['employee_type'] ?? null) === 'tetap')>Karyawan Tetap</option>
              <option value="part_time" @selected(($filters['employee_type'] ?? null) === 'part_time')>Part Time</option>
            </select>

            <select name="status" class="field-input-inline" onchange="this.form.submit()">
              <option value="">Semua Status</option>
              <option value="tepat_waktu" @selected(($filters['status'] ?? null) === 'tepat_waktu')>Tepat Waktu</option>
              <option value="terlambat" @selected(($filters['status'] ?? null) === 'terlambat')>Terlambat</option>
              <option value="alpa" @selected(($filters['status'] ?? null) === 'alpa')>Alpa</option>
              <option value="luar_radius" @selected(($filters['status'] ?? null) === 'luar_radius')>Di Luar Radius</option>
            </select>

            {{-- @if(!empty(array_filter($filters ?? [])))
              <a href="{{ route('approval') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif --}}
          </div>

          <div class="quota-chip">
            Menampilkan <b>{{ $attendances->count() }}</b> dari <b>{{ $attendances->total() }}</b> data
          </div>
        </form>

        <script>
          // ===== Auto-submit filter search dengan debounce =====
          let searchTimer;
          document.getElementById('filterSearch').addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
              document.getElementById('filterForm').submit();
            }, 500);
          });
        </script>

        <div class="card">
          <div class="table-wrap">
            <table>
              <tr>
                <th>Karyawan</th>
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

                  $jadwalLabel = $isTetap
                      ? ($attendance->shift?->name ?? '—')
                      : ($attendance->partTimeSchedule?->activity ?? $attendance->activity ?? '—');

                  $distance = $attendance->distance_m;
                  $isOutOfRadius = $distance !== null && $distance > 100;

                  $lateLabel = 'Terlambat';

                  $startTime = $isTetap
                      ? $attendance->shift?->start_time
                      : $attendance->partTimeSchedule?->start_time;

                  if ($attendance->status === 'terlambat' && $attendance->check_in && $startTime) {
                      $tanggal = $attendance->tanggal->format('Y-m-d');

                      $scheduledTime = \Carbon\Carbon::parse(
                          $tanggal . ' ' . $startTime
                      );

                      // check_in sudah berisi tanggal dan waktu lengkap
                      $checkInTime = \Carbon\Carbon::parse($attendance->check_in);

                      $lateMinutes = $scheduledTime->diffInMinutes($checkInTime);

                      $hours = intdiv($lateMinutes, 60);
                      $minutes = $lateMinutes % 60;

                      $lateParts = [];

                      if ($hours > 0) {
                          $lateParts[] = $hours . ' jam';
                      }

                      if ($minutes > 0) {
                          $lateParts[] = $minutes . ' menit';
                      }

                      if (!empty($lateParts)) {
                          $lateLabel .= ' ' . implode(' ', $lateParts);
                      }
                  }

                  $statusMap = [
                      'tepat_waktu' => [
                          'label' => 'Tepat Waktu',
                          'class' => 'badge-green',
                      ],
                      'terlambat' => [
                          'label' => $lateLabel,
                          'class' => 'badge-rust',
                      ],
                      'alpa' => [
                          'label' => 'Alpa',
                          'class' => 'badge-rust',
                      ],
                  ];

                  $statusInfo = $statusMap[$attendance->status] ?? [
                      'label' => ucfirst(str_replace('_', ' ', $attendance->status ?? '-')),
                      'class' => 'badge-gray',
                  ];

                  $initials = $employee->initials();
                  $avatarColors = ['#8B5CF6', '#2E6FDB', '#E8863A', '#D34D9C', '#2F8A5B', '#D34D3C'];
                  $avatarColor = $avatarColors[$employee->id % count($avatarColors)];
                @endphp
                <tr>
                  <td class="row-name">
                    <div class="avatar-dot" style="background:{{ $avatarColor }};">{{ $initials }}</div>
                    {{ $employee->full_name }}
                  </td>
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
                  {{-- <td>
                    @if ($attendance->check_in_photo_url)
                      <div class="photo-thumb" onclick="openApprovalModal({{ $attendance->id }})">
                        <img src="{{ asset('storage/' . $attendance->check_in_photo_url) }}" alt="Foto presensi {{ $employee->full_name }}">
                      </div>
                    @else
                      <span class="badge badge-rust">Tidak Ada</span>
                    @endif
                  </td> --}}
                  <td>
                    <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                    @if ($isOutOfRadius)
                      <span class="badge badge-rust">Di Luar Radius</span>
                    @endif
                  </td>
                  <td>
                    <button type="button" class="btn btn-gold btn-xs" onclick="openApprovalModal({{ $attendance->id }})">
                      Detail
                    </button>
                  </td>
                </tr>

                <template id="modal-data-{{ $attendance->id }}">
                  <button 
                      type="button"
                      class="modal-close"
                      onclick="closeApprovalModal()"
                      aria-label="Tutup">
                      <i class="fa-solid fa-xmark"></i>
                  </button>

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

                    <div class="modal-content">
                        <div class="modal-content-head">
                          <div class="modal-employee-block">
                              {{-- <div class="avatar-dot" style="background:{{ $avatarColor }}; width:42px; height:42px; font-size:13px; border-radius:10px;">{{ $initials }}</div> --}}
                              <div>
                              <div class="modal-employee-name">{{ $employee->full_name }}</div>
                              <div class="modal-employee-sub">{{ $employee->position ?? '-' }} &middot; {{ $isTetap ? 'Tetap' : 'Part Time' }}</div>
                              </div>
                          </div>
                          {{-- <div class="modal-status-row">
                            <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                          </div> --}}
                        </div>

                        {{-- @if ($isOutOfRadius)
                        <div style="margin-top:10px;">
                          <span class="badge badge-rust">Di luar radius</span>
                        </div>
                        @endif --}}

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
                      </div>

                        @if ($attendance->activity)
                        <div class="modal-block notes">
                            <div class="modal-block-label">Aktivitas</div>
                            <p>{{ $attendance->activity }}</p>
                        </div>
                        @endif

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
              @empty
                <tr>
                  <td colspan="10" class="empty-state">Tidak ada data presensi untuk filter ini.</td>
                </tr>
              @endforelse
            </table>
          </div>

          <div class="note-box">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
              <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>
            </svg>
            <div>Status ditentukan otomatis oleh sistem berdasarkan jam masuk vs jadwal. Absensi dengan jarak &gt;100 m dari titik kantor tetap tercatat dan perlu ditinjau manual.</div>
          </div>

          @if ($attendances->hasPages())
            <div class="pagination-bar">
              @if ($attendances->onFirstPage())
                <span class="btn btn-line btn-sm btn-disabled">&larr; Sebelumnya</span>
              @else
                <a href="{{ $attendances->previousPageUrl() }}" class="btn btn-line btn-sm">&larr; Sebelumnya</a>
              @endif

              <span class="pagination-info">Halaman {{ $attendances->currentPage() }} dari {{ $attendances->lastPage() }}</span>

              @if ($attendances->hasMorePages())
                <a href="{{ $attendances->nextPageUrl() }}" class="btn btn-line btn-sm">Selanjutnya &rarr;</a>
              @else
                <span class="btn btn-line btn-sm btn-disabled">Selanjutnya &rarr;</span>
              @endif
            </div>
          @endif
        </div>

        <!-- ===== MODAL DETAIL ===== -->
        <div class="modal-overlay" id="approvalModalOverlay" onclick="closeApprovalModal(event)">
          <div class="modal-box" id="approvalModalBody" onclick="event.stopPropagation()"></div>
        </div>


<script>
  // ===== Modal detail presensi =====
  function openApprovalModal(id) {
    const source = document.getElementById('modal-data-' + id);
    if (!source) return;
    document.getElementById('approvalModalBody').innerHTML = source.innerHTML;
    document.getElementById('approvalModalOverlay').classList.add('show');
  }
  function closeApprovalModal(e) {
    document.getElementById('approvalModalOverlay').classList.remove('show');
  }

  // ===== Toast notifikasi =====
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
</script>

@endsection