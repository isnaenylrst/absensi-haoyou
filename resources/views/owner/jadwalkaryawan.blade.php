@extends('owner.dashboard')

@section('title', 'Jadwal Kerja | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
@endpush

@section('content')

    <!-- ============ JADWAL KERJA (Owner only) ============ -->
    <div class="crumb">Home <span>›</span> Kehadiran <span>›</span> <b>Jadwal Kerja</b></div>
    <div class="page-head"><div class="page-title">Jadwal Kerja</div></div>

    <div class="subpage active" id="jdw-tetap">
      <div class="shift-page-actions">
        <button type="button" class="btn btn-gold btn-sm" id="btnBukaTambahShift">+ Tambah Shift</button>
        <button type="button" class="btn btn-line btn-sm" id="btnBukaEditShift"><i class="fa-solid fa-pen" style="margin-right:4px;"></i>Edit Shift</button>
      </div>

      <div class="grid grid-2-even" style="margin-top:22px;margin-bottom:12px;">
        @forelse($shifts as $shift)
        <div class="card">
          <div class="card-title" style="margin-bottom:14px;">{{ $shift->nama }}</div>
          <div class="kv"><span class="kv-lbl">Hari berlaku</span><span class="kv-val">{{ $shift->hari_berlaku }}</span></div>
          <div class="kv"><span class="kv-lbl">Jam kerja</span><span class="kv-val mono">{{ $shift->jam_mulai }} – {{ $shift->jam_selesai }}</span></div>
          <div class="kv"><span class="kv-lbl">Toleransi telat</span><span class="kv-val mono">{{ $shift->toleransi_menit }} menit</span></div>
          <div class="kv"><span class="kv-lbl">Jumlah karyawan</span><span class="kv-val">{{ $shift->jumlah_karyawan }} orang</span></div>
        </div>
        @empty
        <div class="card"><span class="kv-lbl">Tidak ada shift yang berlaku hari ini.</span></div>
        @endforelse
      </div>

      <!-- ===== Approval Presensi ===== -->
      <div class="card-title" style="margin-top:22px; margin-bottom:12px;">Approval Presensi</div>

      <form method="GET" action="{{ route('jadwal-kerja') }}" class="toolbar" id="filterForm">
        <input type="hidden" name="tab" value="tetap">
        <div class="toolbar-left">
          <div class="search-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9AA0A8" stroke-width="2">
              <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="text" name="q" id="filterSearch" placeholder="Cari nama karyawan..." value="{{ $filters['q'] ?? '' }}">
          </div>

          <input type="text" id="filterDateRange" class="field-input-inline" placeholder="Pilih rentang tanggal" autocomplete="off" style="min-width:190px;">
          <input type="hidden" name="tanggal_mulai" id="tanggalMulaiInput" value="{{ $filters['tanggal_mulai'] ?? now()->toDateString() }}">
          <input type="hidden" name="tanggal_akhir" id="tanggalAkhirInput" value="{{ $filters['tanggal_akhir'] ?? now()->toDateString() }}">

          <select name="branch_id" class="field-input-inline">
            <option value="">Semua Cabang</option>
            @foreach ($branches as $branch)
              <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
                {{ $branch->name }}
              </option>
            @endforeach
          </select>

          <select name="status" class="field-input-inline">
            <option value="">Semua Status</option>
            <option value="tepat_waktu" @selected(($filters['status'] ?? null) === 'tepat_waktu')>Tepat Waktu</option>
            <option value="terlambat" @selected(($filters['status'] ?? null) === 'terlambat')>Terlambat</option>
            <option value="tidak_checkout" @selected(($filters['status'] ?? null) === 'tidak_checkout')>Tidak Checkout</option>
            <option value="cuti" @selected(($filters['status'] ?? null) === 'cuti')>Cuti</option>
            <option value="alpa" @selected(($filters['status'] ?? null) === 'alpa')>Alpa</option>
            <option value="belum_absen" @selected(($filters['status'] ?? null) === 'belum_absen')>Belum Absen</option>
            <option value="luar_radius" @selected(($filters['status'] ?? null) === 'luar_radius')>Di Luar Radius</option>
          </select>
        </div>
      </form>

      <div id="approvalSection">
      @section('approval')
        <div class="pb-summary" style="margin-bottom:18px;">
          @php
            $statusAktif = $filters['status'] ?? null;
            $chipStatuses = [
                'tepat_waktu'    => ['label' => 'Tepat waktu', 'class' => 'ok'],
                'terlambat'      => ['label' => 'Terlambat', 'class' => 'late'],
                'tidak_checkout' => ['label' => 'Tidak checkout', 'class' => 'out'],
                'cuti'           => ['label' => 'Cuti', 'class' => 'cuti'],
                'alpa'           => ['label' => 'Alpa', 'class' => ''],
                'luar_radius'    => ['label' => 'Di luar radius', 'class' => 'radius'],
            ];
          @endphp
          @foreach ($chipStatuses as $statusValue => $chip)
            <div
              class="pb-summary-chip {{ $chip['class'] }} {{ $statusAktif === $statusValue ? 'active' : '' }}"
              data-status="{{ $statusValue }}"
              role="button"
              tabindex="0"
              style="cursor:pointer;"
            >
              <div class="pb-num">{{ $summary[$statusValue] }}</div>
              <div class="pb-label">{{ $chip['label'] }}</div>
            </div>
          @endforeach
        </div>

        <div class="quota-chip">
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

                  $jadwalLabel = $isTetap
                      ? ($attendance->shift?->name ?? '—')
                      : ($attendance->partTimeSchedule?->activity ?? $attendance->activity ?? '—');

                  $distance = $attendance->distance_m;
                  $isOutOfRadius = $distance !== null && $distance > 100;

                  // Label "Terlambat X jam Y menit" sudah dihitung final di controller
                  // (resolveStatus() / manual override) dan disimpan di $attendance->late_label,
                  // sudah memperhitungkan tolerance_minutes shift. Tidak dihitung ulang di sini
                  // supaya tidak ada dua sumber kebenaran yang bisa out-of-sync.
                  $lateLabel = $attendance->late_label ?? 'Terlambat';

                  // Peta status -> label & warna badge. Mencakup ke-5 status yang mungkin ada
                  // di kolom attendances.status: tepat_waktu, terlambat, tidak_checkout, cuti, alpa.
                  $statusMap = [
                      'tepat_waktu'    => ['label' => 'Tepat Waktu', 'class' => 'badge-green'],
                      'terlambat'      => ['label' => $lateLabel, 'class' => 'badge-rust'],
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

                  // Baris "cuti" (placeholder LeaveRequest) DAN baris "alpa"/"belum absen"
                  // (tidak ada record attendance sama sekali) sama-sama punya id null.
                  // Butuh key unik sendiri (bukan $attendance->id) supaya tombol Detail &
                  // <template> modal tetap bisa dipasangkan dengan benar.
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
                  @if ($sudahAbsen)
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
                    <td>
                      <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                      @if ($isOutOfRadius)
                        <span class="badge badge-rust">Di Luar Radius</span>
                      @endif
                    </td>
                  @else
                    <td colspan="5" style="text-align:center; color:#9AA0A8; font-style:italic;">
                      {{ $attendance->status_label }}
                    </td>
                  @endif
                  <td class="jadwal-action-cell">
                    <div class="jadwal-action-buttons">
                    <button type="button" class="btn btn-gold btn-xs" onclick="openApprovalModal('{{ $modalKey }}')">
                      Detail
                    </button>
                    </div>
                  </td>
                </tr>

                @if ($sudahAbsen)
                <template id="modal-data-{{ $modalKey }}">
                  <button type="button" class="modal-close" onclick="closeApprovalModal()" aria-label="Tutup">
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

                        {{-- Baris "cuti" bisa berupa placeholder dari LeaveRequest tanpa record
                             Attendance asli (id null) — form override cuma valid untuk record
                             Attendance sungguhan, jadi wajib dicek id-nya dulu. --}}
                        @if ($attendance->id)
                        <div class="modal-block override-status">
                          <div class="modal-block-label">Ubah status secara manual</div>
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
                @else
                {{-- Baris "alpa" / "belum melakukan absensi": tidak ada record Attendance
                     sama sekali, jadi modalnya lebih ringkas (tanpa foto/lokasi) dan form
                     override-nya mengirim employee_id + tanggal ke route khusus yang akan
                     MEMBUAT record Attendance baru, bukan meng-update yang sudah ada. --}}
                <template id="modal-data-{{ $modalKey }}">
                  <button type="button" class="modal-close" onclick="closeApprovalModal()" aria-label="Tutup">
                      <i class="fa-solid fa-xmark"></i>
                  </button>

                  <div class="modal-content" style="padding-top:8px;">
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
                          <span class="badge badge-gray">{{ $attendance->status_label ?? 'Belum melakukan absensi' }}</span>
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
                    </div>

                    <div class="modal-block override-status">
                      <div class="modal-block-label">Ubah status secara manual</div>
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
                    </div>
                  </div>
                </template>
                @endif
                @empty
                  <tr>
                    <td colspan="10" class="empty-state">Tidak ada data presensi untuk filter ini.</td>
                  </tr>
                @endforelse
            </table>
          </div>

          @if ($attendances->hasPages())
            <div class="pagination-bar" style="display:flex; justify-content:center; align-items:center; gap:10px; margin-top:16px;">
              @if ($attendances->onFirstPage())
                <span class="pg-arrow pg-arrow-disabled" aria-label="Sebelumnya">
                  <i class="fa-solid fa-chevron-left"></i>
                </span>
              @else
                <a href="{{ $attendances->previousPageUrl() }}" class="pg-arrow" aria-label="Sebelumnya">
                  <i class="fa-solid fa-chevron-left"></i>
                </a>
              @endif

              <span class="pg-current">{{ $attendances->currentPage() }}</span>

              @if ($attendances->hasMorePages())
                <a href="{{ $attendances->nextPageUrl() }}" class="pg-arrow" aria-label="Selanjutnya">
                  <i class="fa-solid fa-chevron-right"></i>
                </a>
              @else
                <span class="pg-arrow pg-arrow-disabled" aria-label="Selanjutnya">
                  <i class="fa-solid fa-chevron-right"></i>
                </span>
              @endif
            </div>
          @endif
        </div>

        <div class="modal-overlay" id="approvalModalOverlay" onclick="closeApprovalModal(event)">
          <div class="modal-box" id="approvalModalBody" onclick="event.stopPropagation()"></div>
        </div>
      @show
      </div>
      {{-- ===== akhir #approvalSection ===== --}}
    </div>
    {{-- ===== akhir subpage jdw-tetap ===== --}}

    <!-- ============ MODAL: TAMBAH SHIFT ============ -->
    <div class="modal-overlay @if($errors->any() && old('_form') === 'tambah_shift') show @endif" id="modalTambahShift">
        <div class="modal-box">
            <div class="modal-head">
                <div class="card-title">Tambah Shift Baru</div>
                <button type="button" class="modal-close" id="btnTutupTambahShift" aria-label="Tutup">&times;</button>
            </div>

            <form method="POST" action="{{ route('owner.shift.store') }}">
                @csrf
                <input type="hidden" name="_form" value="tambah_shift">

                <div class="form-field">
                    <label for="shift_name">Nama Shift</label>
                    <input type="text" id="shift_name" name="name" placeholder="Shift Pagi" value="{{ old('name') }}" required>
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-field">
                    <label>Hari Berlaku</label>
                    <div class="day-checkboxes">
                        @foreach(['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu'] as $key => $label)
                            <label class="day-chip">
                                <input type="checkbox" name="applicable_days[]" value="{{ $key }}" {{ in_array($key, old('applicable_days', [])) ? 'checked' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('applicable_days')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="shift_start">Jam Mulai</label>
                        <input type="time" id="shift_start" name="start_time" value="{{ old('start_time') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="shift_end">Jam Selesai</label>
                        <input type="time" id="shift_end" name="end_time" value="{{ old('end_time') }}" required>
                    </div>
                </div>
                @error('end_time')<div class="field-error">{{ $message }}</div>@enderror

                <div class="form-field">
                    <label for="shift_toleransi">Toleransi Telat (menit)</label>
                    <input type="number" id="shift_toleransi" name="tolerance_minutes" value="{{ old('tolerance_minutes', 15) }}" min="0" max="120">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-line btn-sm" id="btnBatalTambahShift">Batal</button>
                    <button type="submit" class="btn btn-gold btn-sm">Simpan Shift</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ MODAL: EDIT SHIFT ============ -->
    <div class="modal-overlay @if($errors->any() && old('_form') === 'edit_shift') show @endif" id="modalEditShift">
        <div class="modal-box">
            <div class="modal-head">
                <div class="card-title">Edit Shift</div>
                <button type="button" class="modal-close" id="btnTutupEditShift" aria-label="Tutup">&times;</button>
            </div>

            <div class="form-field">
                <label for="edit_shift_select">Pilih Shift</label>
                <select id="edit_shift_select">
                    <option value="">-- Pilih shift yang mau diedit --</option>
                    @foreach ($allShifts as $shift)
                        <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>{{ $shift->nama }}</option>
                    @endforeach
                </select>
            </div>

            <form method="POST" id="formEditShift" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_shift">
                <input type="hidden" name="shift_id" id="edit_shift_id_hidden" value="{{ old('shift_id') }}">

                <div class="form-field">
                    <label for="edit_shift_name">Nama Shift</label>
                    <input type="text" id="edit_shift_name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-field">
                    <label>Hari Berlaku</label>
                    <div class="day-checkboxes">
                        @foreach(['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu'] as $key => $label)
                            <label class="day-chip">
                                <input type="checkbox" name="applicable_days[]" value="{{ $key }}" class="edit-day-checkbox" {{ in_array($key, old('applicable_days', [])) ? 'checked' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('applicable_days')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_shift_start">Jam Mulai</label>
                        <input type="time" id="edit_shift_start" name="start_time" value="{{ old('start_time') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="edit_shift_end">Jam Selesai</label>
                        <input type="time" id="edit_shift_end" name="end_time" value="{{ old('end_time') }}" required>
                    </div>
                </div>
                @error('end_time')<div class="field-error">{{ $message }}</div>@enderror

                <div class="form-field">
                    <label for="edit_shift_toleransi">Toleransi Telat (menit)</label>
                    <input type="number" id="edit_shift_toleransi" name="tolerance_minutes" value="{{ old('tolerance_minutes', 15) }}" min="0" max="120">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-line btn-sm" id="btnBatalEditShift">Batal</button>
                    <button type="submit" class="btn btn-gold btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script>
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

  // ===== Modal Tambah Shift =====
  const modalTambahShift = document.getElementById('modalTambahShift');
  document.getElementById('btnBukaTambahShift').addEventListener('click', () => {
    modalTambahShift.classList.add('show');
  });
  document.getElementById('btnTutupTambahShift').addEventListener('click', () => {
    modalTambahShift.classList.remove('show');
  });
  document.getElementById('btnBatalTambahShift').addEventListener('click', () => {
    modalTambahShift.classList.remove('show');
  });
  modalTambahShift.addEventListener('click', (e) => {
    if (e.target === modalTambahShift) modalTambahShift.classList.remove('show');
  });

  // ===== Modal detail presensi (approval) =====
  function openApprovalModal(id) {
    const source = document.getElementById('modal-data-' + id);
    if (!source) return;
    document.getElementById('approvalModalBody').innerHTML = source.innerHTML;
    document.getElementById('approvalModalOverlay').classList.add('show');
  }
  function closeApprovalModal(e) {
    const overlay = document.getElementById('approvalModalOverlay');
    if (overlay) overlay.classList.remove('show');
  }

  // ================================================================
  // AJAX filter + pagination untuk section Approval Presensi
  // ================================================================
  const approvalSection = document.getElementById('approvalSection');
  const filterForm = document.getElementById('filterForm');

  async function loadApproval(url, { pushState = true } = {}) {
    approvalSection.classList.add('is-loading');
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error('Gagal memuat data (' + res.status + ')');
      const html = await res.text();
      approvalSection.innerHTML = html;
      if (pushState) {
        history.pushState({ approval: true }, '', url);
      }
    } catch (err) {
      showToast('Gagal memuat data presensi.');
      console.error(err);
    } finally {
      approvalSection.classList.remove('is-loading');
    }
  }

  // Submit form filter (search / dropdown / date range) -> AJAX, no reload
  filterForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const params = new URLSearchParams(new FormData(filterForm)).toString();
    loadApproval(filterForm.action + '?' + params);
  });

  // Auto-submit dropdown branch & status
  filterForm.querySelectorAll('select[name="branch_id"], select[name="status"]').forEach((select) => {
    select.addEventListener('change', () => filterForm.requestSubmit());
  });

  // Klik link pagination di dalam approvalSection -> AJAX, no reload
  approvalSection.addEventListener('click', function (e) {
    const link = e.target.closest('a[href]');
    if (link && link.closest('.pagination-bar')) {
      e.preventDefault();
      loadApproval(link.href);
      return;
    }

    // Klik chip summary (Tepat waktu / Terlambat / dst) -> filter status, sama
    // seperti pilih dari dropdown "Semua Status". Klik chip yang sedang aktif
    // lagi -> filter dilepas (kembali ke "Semua Status").
    const chip = e.target.closest('.pb-summary-chip[data-status]');
    if (chip) {
      applyStatusChipFilter(chip.dataset.status, chip.classList.contains('active'));
    }
  });

  // Aksesibilitas: chip juga bisa diaktifkan lewat keyboard (Enter / Space).
  approvalSection.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const chip = e.target.closest('.pb-summary-chip[data-status]');
    if (!chip) return;
    e.preventDefault();
    applyStatusChipFilter(chip.dataset.status, chip.classList.contains('active'));
  });

  function applyStatusChipFilter(statusValue, isCurrentlyActive) {
    const statusSelect = filterForm.querySelector('select[name="status"]');
    if (!statusSelect) return;
    statusSelect.value = isCurrentlyActive ? '' : statusValue;
    filterForm.requestSubmit();
  }

  // Tombol Back/Forward browser
  window.addEventListener('popstate', function () {
    loadApproval(location.href, { pushState: false });
  });

  // ===== Auto-submit filter search dengan debounce =====
  let searchTimer;
  const filterSearchInput = document.getElementById('filterSearch');
  if (filterSearchInput) {
    filterSearchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        filterForm.requestSubmit();
      }, 500);
    });
  }

  // ===== Filter rentang tanggal (Flatpickr) =====
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
          filterForm.requestSubmit();
        } else if (selectedDates.length === 1) {
          mulaiInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          akhirInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          filterForm.requestSubmit();
        }
      },
    });
  }

  // ===== Data shift untuk prefill form edit (semua shift, bukan cuma hari ini) =====
  const shiftData = {
    @foreach ($allShifts as $shift)
      {{ $shift->id }}: {
        name: @json($shift->nama),
        applicable_days: @json($shift->applicable_days),
        start_time: @json($shift->jam_mulai),
        end_time: @json($shift->jam_selesai),
        tolerance_minutes: {{ $shift->toleransi_menit }}
      },
    @endforeach
  };

  // ===== Modal Edit Shift =====
  const modalEditShift = document.getElementById('modalEditShift');
  const editShiftSelect = document.getElementById('edit_shift_select');
  const formEditShift = document.getElementById('formEditShift');

  document.getElementById('btnBukaEditShift').addEventListener('click', () => {
    modalEditShift.classList.add('show');

    if (!editShiftSelect.value && editShiftSelect.options.length > 1) {
      editShiftSelect.selectedIndex = 1; // index 0 = placeholder "-- Pilih shift --"
      const id = editShiftSelect.value;
      if (shiftData[id]) {
        isiFormEditShift(id, shiftData[id]);
      }
    }
  });
  
  document.getElementById('btnTutupEditShift').addEventListener('click', () => {
    modalEditShift.classList.remove('show');
  });
  document.getElementById('btnBatalEditShift').addEventListener('click', () => {
    modalEditShift.classList.remove('show');
  });
  modalEditShift.addEventListener('click', (e) => {
    if (e.target === modalEditShift) modalEditShift.classList.remove('show');
  });

  function isiFormEditShift(id, data) {
    formEditShift.action = `/owner/shift/${id}`;
    formEditShift.style.display = 'block';

    document.getElementById('edit_shift_id_hidden').value = id;
    document.getElementById('edit_shift_name').value = data.name;
    document.getElementById('edit_shift_start').value = data.start_time;
    document.getElementById('edit_shift_end').value = data.end_time;
    document.getElementById('edit_shift_toleransi').value = data.tolerance_minutes;

    document.querySelectorAll('.edit-day-checkbox').forEach((cb) => {
      cb.checked = data.applicable_days.includes(cb.value);
    });
  }

  editShiftSelect.addEventListener('change', () => {
    const id = editShiftSelect.value;
    if (!id || !shiftData[id]) {
      formEditShift.style.display = 'none';
      return;
    }
    isiFormEditShift(id, shiftData[id]);
  });

  @if($errors->any() && old('_form') === 'edit_shift')
    modalEditShift.classList.add('show');
    formEditShift.style.display = 'block';
    formEditShift.action = '/owner/shift/{{ old('shift_id') }}';
  @endif

  @if(session('success'))
    showToast('{{ session('success') }}');
  @endif
</script>

@endsection