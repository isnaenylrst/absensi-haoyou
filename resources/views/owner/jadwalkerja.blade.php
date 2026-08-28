@extends('owner.dashboard')

@section('title', 'Jadwal Kerja | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/owner/approval-presensi.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <style>
        #modalTambahShift.modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20, 18, 10, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        #modalTambahShift.modal-overlay.show { display: flex; }
        #modalTambahShift .modal-box {
            background: #fff;
            border-radius: 14px;
            width: 420px;
            max-width: 92vw;
            max-height: 88vh;
            overflow-y: auto;
            padding: 22px 24px 20px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
        }
        #modalTambahShift .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        #modalTambahShift .modal-head .card-title { margin: 0; }
        #modalTambahShift .modal-close {
            border: none;
            background: transparent;
            font-size: 20px;
            line-height: 1;
            color: #8a8a86;
            cursor: pointer;
            padding: 2px 4px;
        }
        #modalTambahShift .modal-close:hover { color: #333; }
        #modalTambahShift .form-field { margin-bottom: 14px; }
        #modalTambahShift .form-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4b4a45;
            margin-bottom: 6px;
        }
        #modalTambahShift .form-field input[type="text"],
        #modalTambahShift .form-field input[type="time"],
        #modalTambahShift .form-field input[type="number"] {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid #ddd8cc;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        #modalTambahShift .form-field input:focus {
            outline: none;
            border-color: #E8863A;
            box-shadow: 0 0 0 3px rgba(232,134,58,0.15);
        }
        #modalTambahShift .form-row { display: flex; gap: 12px; }
        #modalTambahShift .form-row .form-field { flex: 1; }
        #modalTambahShift .day-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        #modalTambahShift .day-chip { position: relative; }
        #modalTambahShift .day-chip input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }
        #modalTambahShift .day-chip span {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #ddd8cc;
            border-radius: 999px;
            font-size: 13px;
            color: #5a5952;
            background: #faf9f5;
        }
        #modalTambahShift .day-chip input:checked + span {
            background: #FCEBD9;
            border-color: #E8863A;
            color: #8A6212;
            font-weight: 600;
        }
        #modalTambahShift .field-error {
            color: #c0392b;
            font-size: 12px;
            margin-top: 5px;
        }
        #modalTambahShift .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #eee;
        }
    </style>
@endpush

@section('content')

      <!-- ============ JADWAL KERJA (Owner only) ============ -->
        <div class="crumb">Home <span>›</span> Kehadiran <span>›</span> <b>Jadwal Kerja</b></div>
        <div class="page-head"><div class="page-title">Jadwal Kerja</div></div>

        <div class="pilltabs">
          <div class="pilltab active" data-sub="jdw-tetap">Karyawan Tetap (Shift)</div>
          <div class="pilltab" data-sub="jdw-parttime">Karyawan Part Time (Per Jam)</div>
        </div>

        <!-- Jadwal: Karyawan Tetap (shift) -->
        <div class="subpage active" id="jdw-tetap">
          <div class="page-actions" style="margin-bottom:14px;">
            <button type="button" class="btn btn-gold btn-sm" id="btnBukaTambahShift">+ Tambah Shift</button>
          </div>

          <div class="grid grid-2-even">
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

          <!-- ===== Approval Presensi (dipindah dari halaman /approval) ===== -->
          <div class="card-title" style="margin-top:22px; margin-bottom:12px;">Approval Presensi</div>

          <form method="GET" action="{{ route('jadwal-kerja') }}" class="toolbar" id="filterForm">
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
            </div>

            <div class="quota-chip">
              Menampilkan <b>{{ $attendances->count() }}</b> dari <b>{{ $attendances->total() }}</b> data
            </div>
          </form>

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
                        'tepat_waktu' => ['label' => 'Tepat Waktu', 'class' => 'badge-green'],
                        'terlambat' => ['label' => $lateLabel, 'class' => 'badge-rust'],
                        'alpa' => ['label' => 'Alpa', 'class' => 'badge-rust'],
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
                    <td colspan="9" class="empty-state">Tidak ada data presensi untuk filter ini.</td>
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

          <!-- ===== MODAL DETAIL APPROVAL ===== -->
          <div class="modal-overlay" id="approvalModalOverlay" onclick="closeApprovalModal(event)">
            <div class="modal-box" id="approvalModalBody" onclick="event.stopPropagation()"></div>
          </div>
        </div>

        <!-- Jadwal: Karyawan Part Time (per sesi) -->
        <div class="subpage" id="jdw-parttime">
          <div class="page-actions" style="margin-bottom:14px;"><button class="btn btn-gold btn-sm">+ Tambah Sesi Jadwal</button></div>
          <div class="note-box" style="margin-top:0; margin-bottom:16px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            <div>Karyawan part time (termasuk Guru) tidak memakai shift tetap — presensi aktif per sesi sesuai jadwal, dan <strong>dalam satu hari bisa terdapat lebih dari satu sesi mengajar/kerja</strong>. Setiap sesi wajib unggah foto kegiatan tersendiri.</div>
          </div>

          @forelse($jadwalPartTime as $pegawai)
          <div class="card" style="{{ $loop->last ? '' : 'margin-bottom:16px;' }}">
            <div class="card-head">
              <div>
                <div class="card-title">{{ $pegawai->nama }}</div>
                <div class="card-sub">{{ $pegawai->jabatan }} · Rate Rp {{ number_format($pegawai->rate_per_jam, 0, ',', '.') }}/jam</div>
              </div>
              <button class="btn btn-line btn-sm">Edit Jadwal</button>
            </div>
            <div class="week-grid">
              @foreach($hariKerja as $hari)
              <div class="week-day">
                <div class="week-day-label">{{ $hari }}</div>
                @forelse($pegawai->sesi[$hari] as $s)
                  <div class="session-chip">
                    <div class="session-chip-time">{{ $s->jam_mulai }}–{{ $s->jam_selesai }}</div>
                    <div class="session-chip-label">{{ $s->kegiatan }}</div>
                  </div>
                @empty
                  <div class="week-day-empty">— Libur</div>
                @endforelse
              </div>
              @endforeach
            </div>
            <div class="field-hint" style="margin-top:10px;">Total {{ $pegawai->total_sesi }} sesi / minggu · estimasi {{ $pegawai->total_jam }} jam/minggu.</div>
          </div>
          @empty
          <div class="card"><span class="kv-lbl">Belum ada karyawan part time yang terdaftar.</span></div>
          @endforelse
        </div>

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script>
  // ===== Ganti tab Karyawan Tetap / Part Time =====
  document.querySelectorAll('.pilltab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.pilltab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      document.querySelectorAll('.subpage').forEach(s => s.classList.remove('active'));
      document.getElementById(tab.dataset.sub).classList.add('active');
    });
  });

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
    document.getElementById('approvalModalOverlay').classList.remove('show');
  }

  // ===== Auto-submit filter search dengan debounce =====
  let searchTimer;
  const filterSearchInput = document.getElementById('filterSearch');
  if (filterSearchInput) {
    filterSearchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        document.getElementById('filterForm').submit();
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
          document.getElementById('filterForm').submit();
        } else if (selectedDates.length === 1) {
          mulaiInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          akhirInput.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          document.getElementById('filterForm').submit();
        }
      },
    });
  }

  @if(session('success'))
    showToast('{{ session('success') }}');
  @endif
</script>

@endsection