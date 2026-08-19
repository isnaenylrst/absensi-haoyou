@extends('owner.dashboard')

@section('title', 'Jadwal Kerja | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20, 18, 10, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 14px;
            width: 420px;
            max-width: 92vw;
            max-height: 88vh;
            overflow-y: auto;
            padding: 22px 24px 20px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
        }
        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .modal-head .card-title { margin: 0; }
        .modal-close {
            border: none;
            background: transparent;
            font-size: 20px;
            line-height: 1;
            color: #8a8a86;
            cursor: pointer;
            padding: 2px 4px;
        }
        .modal-close:hover { color: #333; }
        .form-field { margin-bottom: 14px; }
        .form-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4b4a45;
            margin-bottom: 6px;
        }
        .form-field input[type="text"],
        .form-field input[type="time"],
        .form-field input[type="number"] {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid #ddd8cc;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-field input:focus {
            outline: none;
            border-color: #E8863A;
            box-shadow: 0 0 0 3px rgba(232,134,58,0.15);
        }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-field { flex: 1; }
        .day-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .day-chip {
            position: relative;
        }
        .day-chip input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }
        .day-chip span {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #ddd8cc;
            border-radius: 999px;
            font-size: 13px;
            color: #5a5952;
            background: #faf9f5;
        }
        .day-chip input:checked + span {
            background: #FCEBD9;
            border-color: #E8863A;
            color: #8A6212;
            font-weight: 600;
        }
        .field-error {
            color: #c0392b;
            font-size: 12px;
            margin-top: 5px;
        }
        .modal-actions {
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

          <div class="card" style="margin-top:16px;">
            <div class="card-title" style="margin-bottom:14px;">Penempatan Shift Mingguan</div>
            <div class="table-wrap">
              <table>
                <tr>
                  <th>Karyawan</th>
                  @foreach($hariKerja as $hari)
                  <th>{{ $hari }}</th>
                  @endforeach
                </tr>
                @forelse($penempatanShift as $karyawan)
                <tr>
                  <td class="row-name">
                    <div class="avatar-dot" style="background:{{ $karyawan->warna_avatar }};">{{ $karyawan->inisial }}</div>
                    {{ $karyawan->nama }}
                  </td>
                  @foreach($hariKerja as $hari)
                    @php $status = $karyawan->jadwal[$hari]; @endphp
                    <td>
                      @if($status === 'Libur')
                        <span class="badge badge-gray">Libur</span>
                      @elseif(str_contains($status, 'Siang'))
                        <span class="badge badge-orange">{{ $status }}</span>
                      @else
                        <span class="badge badge-blue">{{ $status }}</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                @empty
                <tr><td colspan="{{ count($hariKerja) + 1 }}"><span class="kv-lbl">Belum ada karyawan tetap yang terdaftar.</span></td></tr>
                @endforelse
              </table>
            </div>
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

  @if(session('success'))
    showToast('{{ session('success') }}');
  @endif
</script>

@endsection