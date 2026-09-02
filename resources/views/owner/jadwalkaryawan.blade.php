@extends('owner.dashboard')

@section('title', 'Jadwal Kerja | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

      <!-- ===== Ringkasan Presensi ===== -->
      <div class="card-title" style="margin-top:22px; margin-bottom:12px;">Ringkasan Presensi</div>

      <form method="GET" action="{{ route('jadwal-kerja') }}" class="toolbar" id="filterForm">
        <div class="toolbar-left" style="flex-wrap:wrap; gap:8px;">
          <div class="search-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9AA0A8" stroke-width="2">
              <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="text" name="q" id="filterSearch" placeholder="Cari nama karyawan..." value="{{ $filters['q'] ?? '' }}">
          </div>

          <select name="branch_id" class="field-input-inline">
            <option value="">Semua Cabang</option>
            @foreach ($branches as $branch)
              <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
                {{ $branch->name }}
              </option>
            @endforeach
          </select>

          <select name="bulan" class="field-input-inline">
            @foreach ($daftarBulan as $angka => $nama)
              <option value="{{ $angka }}" @selected($bulanTerpilih === $angka)>{{ $nama }}</option>
            @endforeach
          </select>
          <select name="tahun" class="field-input-inline">
            @foreach ($daftarTahun as $tahunOpt)
              <option value="{{ $tahunOpt }}" @selected($tahunTerpilih === $tahunOpt)>{{ $tahunOpt }}</option>
            @endforeach
          </select>

          <button type="submit" class="btn btn-line btn-sm">Terapkan Filter</button>
        </div>
      </form>

      @php
        $queryUntukKategori = array_filter([
            'mode'      => 'bulanan',
            'bulan'     => $bulanTerpilih,
            'tahun'     => $tahunTerpilih,
            'branch_id' => $filters['branch_id'] ?? null,
            'q'         => $filters['q'] ?? null,
        ]);
      @endphp
      <div class="pb-summary" style="margin-bottom:18px;">
        <a href="{{ route('owner.presensi.kategori', array_merge(['kategori' => 'masuk'], $queryUntukKategori)) }}"
           class="pb-summary-chip ok" style="text-decoration:none; display:block;">
          <div class="pb-num">{{ $summaryCards['masuk'] }}</div>
          <div class="pb-label">Masuk</div>
        </a>
        <a href="{{ route('owner.presensi.kategori', array_merge(['kategori' => 'cuti'], $queryUntukKategori)) }}"
           class="pb-summary-chip cuti" style="text-decoration:none; display:block;">
          <div class="pb-num">{{ $summaryCards['cuti'] }}</div>
          <div class="pb-label">Cuti</div>
        </a>
        <a href="{{ route('owner.presensi.kategori', array_merge(['kategori' => 'alpa'], $queryUntukKategori)) }}"
           class="pb-summary-chip" style="text-decoration:none; display:block;">
          <div class="pb-num">{{ $summaryCards['alpa'] }}</div>
          <div class="pb-label">Alpa</div>
        </a>
      </div>

      <!-- ===== Riwayat Bulanan ===== -->
      <div class="card-title" style="margin-top:22px; margin-bottom:12px;">Riwayat Bulanan</div>

      <div class="quota-chip" style="margin-bottom:14px;">
        Menampilkan <b>{{ $riwayat->count() }}</b> karyawan &middot; periode <b>{{ $daftarBulan[$bulanTerpilih] }} {{ $tahunTerpilih }}</b>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="jadwal-approval-table">
            <tr>
              <th>Karyawan</th>
              <th>Cabang</th>
              <th style="text-align:center;">Hadir</th>
              <th style="text-align:center;">Telat</th>
              <th style="text-align:center;">Tidak Checkout</th>
              <th style="text-align:center;">Cuti</th>
              <th style="text-align:center;">Alpa</th>
              <th style="text-align:center;">Aksi</th>
            </tr>

            @forelse ($riwayat as $r)
              <tr>
                <td>{{ $r->nama }}</td>
                <td>{{ $r->cabang }}</td>
                <td style="text-align:center;">{{ $r->hadir }}</td>
                <td style="text-align:center;">{{ $r->telat }}</td>
                <td style="text-align:center;">{{ $r->tidak_checkout }}</td>
                <td style="text-align:center;">{{ $r->cuti }}</td>
                <td style="text-align:center;">{{ $r->alpa }}</td>
                <td class="jadwal-action-cell">
                  <button type="button" class="btn btn-line btn-xs" onclick="openRiwayatModal('riwayat-{{ $r->id }}')">Detail</button>
                </td>
              </tr>

              <template id="modal-riwayat-{{ $r->id }}">
                <button type="button" class="modal-close" onclick="closeRiwayatModal()" aria-label="Tutup">
                  <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="modal-content" style="padding-top:8px;">
                  <div class="modal-content-head">
                    <div class="modal-employee-name">{{ $r->nama }}</div>
                    <div class="modal-employee-sub">{{ $r->cabang }} &middot; {{ ucfirst($r->tipe) }}</div>
                  </div>
                  <div class="table-wrap" style="margin-top:12px;">
                    <table class="jadwal-approval-table">
                      <tr><th>Tanggal</th><th>Jadwal</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr>
                      @forelse ($r->detail as $d)
                        <tr>
                          <td class="mono">{{ \Carbon\Carbon::parse($d->tanggal)->translatedFormat('d M Y') }}</td>
                          <td>{{ $d->jadwal }}</td>
                          <td class="mono">{{ $d->jam_masuk ? \Carbon\Carbon::parse($d->jam_masuk)->format('H:i') : '—' }}</td>
                          <td class="mono">{{ $d->jam_keluar ? \Carbon\Carbon::parse($d->jam_keluar)->format('H:i') : '—' }}</td>
                          <td>{{ ucfirst(str_replace('_', ' ', $d->status ?? '-')) }}</td>
                        </tr>
                      @empty
                        <tr><td colspan="5" class="empty-state">Belum ada record presensi bulan ini.</td></tr>
                      @endforelse
                    </table>
                  </div>
                </div>
              </template>
            @empty
              <tr><td colspan="8" class="empty-state">Tidak ada data untuk bulan ini.</td></tr>
            @endforelse
          </table>
        </div>
      </div>

      <div class="modal-overlay" id="riwayatModalOverlay" onclick="closeRiwayatModal(event)">
        <div class="modal-box" id="riwayatModalBody" onclick="event.stopPropagation()"></div>
      </div>
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

  // ===== Filter (search / cabang / bulan / tahun) =====
  const filterForm = document.getElementById('filterForm');
  filterForm.querySelectorAll('select[name="branch_id"], select[name="bulan"], select[name="tahun"]').forEach((select) => {
    select.addEventListener('change', () => filterForm.requestSubmit());
  });

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

  // ===== Modal Detail Riwayat Bulanan =====
  function openRiwayatModal(id) {
    const source = document.getElementById('modal-' + id);
    if (!source) return;
    document.getElementById('riwayatModalBody').innerHTML = source.innerHTML;
    document.getElementById('riwayatModalOverlay').classList.add('show');
  }
  function closeRiwayatModal(e) {
    document.getElementById('riwayatModalOverlay').classList.remove('show');
  }

  @if(session('success'))
    showToast('{{ session('success') }}');
  @endif
</script>

@endsection
