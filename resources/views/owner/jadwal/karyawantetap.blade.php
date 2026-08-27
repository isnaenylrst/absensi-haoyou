{{-- Jadwal: Karyawan Tetap (shift) --}}
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

      <select name="branch_id" class="field-input-inline" onchange="this.form.submit()">
        <option value="">Semua Cabang</option>
        @foreach ($branches as $branch)
          <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
            {{ $branch->name }}
          </option>
        @endforeach
      </select>

      <select name="status" class="field-input-inline" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="tepat_waktu" @selected(($filters['status'] ?? null) === 'tepat_waktu')>Tepat Waktu</option>
        <option value="terlambat" @selected(($filters['status'] ?? null) === 'terlambat')>Terlambat</option>
        <option value="alpa" @selected(($filters['status'] ?? null) === 'alpa')>Alpa</option>
        <option value="belum_absen" @selected(($filters['status'] ?? null) === 'belum_absen')>Belum Absen</option>
        <option value="luar_radius" @selected(($filters['status'] ?? null) === 'luar_radius')>Di Luar Radius</option>
      </select>
    </div>

    <div class="quota-chip">
      Menampilkan <b>{{ $attendances->count() }}</b> dari <b>{{ $attendances->total() }}</b> data
    </div>
  </form>

  <div class="card">
    <div class="table-wrap">
              <table class="jadwal-approval-table">
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
            $sudahAbsen = $attendance->sudah_absen ?? true;

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

            // Karyawan yang belum absen: status null -> tampil "-" (badge-gray),
            // bukan dianggap "alpa" secara otomatis oleh sistem.
            $statusInfo = $sudahAbsen
                ? ($statusMap[$attendance->status] ?? [
                    'label' => ucfirst(str_replace('_', ' ', $attendance->status ?? '-')),
                    'class' => 'badge-gray',
                ])
                : ['label' => '-', 'class' => 'badge-gray'];

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
            <td class="jadwal-action-cell">
              <div class="jadwal-action-buttons">
              @if ($sudahAbsen)
                <button type="button" class="btn btn-gold btn-xs" onclick="openApprovalModal({{ $attendance->id }})">
                  Detail
                </button>
              @else
                <button type="button" class="btn btn-gold btn-xs" disabled title="Karyawan belum absen">
                  Detail
                </button>
              @endif

              <a href="{{ route('jadwal-kerja.presensi-bulanan', ['employee' => $employee->id, 'bulan' => $attendance->tanggal->month, 'tahun' => $attendance->tanggal->year]) }}"
                class="btn btn-line btn-xs" title="Lihat presensi bulanan {{ $employee->full_name }}">
                Presensi Bulanan
              </a>
              </div>
            </td>
          </tr>

          {{-- Template modal detail hanya dibuat kalau karyawan sudah absen --}}
          @if ($sudahAbsen)
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
          @endif
        @empty
          <tr>
            <td colspan="9" class="empty-state">Tidak ada data presensi untuk filter ini.</td>
          </tr>
        @endforelse
      </table>
    </div>

    {{-- <div class="note-box">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
        <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>
      </svg>
      <div>Status ditentukan otomatis oleh sistem berdasarkan jam masuk vs jadwal. Karyawan yang belum melakukan absen ditandai "-" dan perlu ditinjau manual (belum tentu alpa). Absensi dengan jarak &gt;100 m dari titik kantor tetap tercatat dan perlu ditinjau manual.</div>
    </div> --}}

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