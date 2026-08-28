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

    {{-- Jadwal & approval presensi karyawan tetap (shift) --}}
    @include('owner.jadwal.karyawantetap')

    {{-- Jadwal per sesi karyawan part time / guru --}}
    @include('owner.jadwal.guru')

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

  // ===== Modal detail riwayat absensi bulanan =====
  function openRiwayatModal(id) {
    const source = document.getElementById('riwayat-data-' + id);
    if (!source) return;
    document.getElementById('riwayatModalBody').innerHTML = source.innerHTML;
    document.getElementById('riwayatModalOverlay').classList.add('show');
  }
  function closeRiwayatModal(e) {
    document.getElementById('riwayatModalOverlay').classList.remove('show');
  }

  // ===== Pertahankan tab aktif setelah filter bulan/tahun/tanggal submit (reload halaman) =====
  const urlParams = new URLSearchParams(window.location.search);
  const activeTabParam = urlParams.get('tab');
  if (activeTabParam) {
    const targetTab = document.querySelector('.pilltab[data-sub="jdw-' + activeTabParam + '"]');
    if (targetTab) targetTab.click();
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