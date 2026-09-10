@extends('karyawan.dashboard')

@section('title', 'Izin & Cuti')

@push('styles')
<style>
    /* ===== Perbaikan agar konten tidak melebar dari topbar (cegah scroll horizontal) ===== */
    html, body {
        overflow-x: hidden;
        max-width: 100%;
    }

    .content {
        box-sizing: border-box;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .izin-grid,
    .izin-grid * {
        box-sizing: border-box;
        max-width: 100%;
    }

    /* input date/select native kadang punya lebar minimum bawaan browser di HP */
    .izin-grid input[type="date"] {
        min-width: 0;
    }

    /* ===== Layout utama halaman Izin & Cuti ===== */
    .izin-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
    }

    @media (max-width: 900px) {
        .izin-grid {
            grid-template-columns: 1fr;
        }
    }

    .izin-grid .card {
        margin: 0;
        border-radius: 14px;
        background: var(--card-bg, #fff);
        color: var(--text-main, #22262B);
        border: 1px solid var(--border-c, transparent);
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }

    body.dark-mode .izin-grid .card {
        box-shadow: none;
    }

    /* ===== Form ===== */
    .izin-grid .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }

    @media (max-width: 560px) {
        .izin-grid .form-row {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }

    .izin-grid .field {
        display: flex;
        flex-direction: column;
        margin-bottom: 14px;
    }

    .izin-grid .field label {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
        color: var(--text-main, #3a3a3a);
    }

    .izin-grid select,
    .izin-grid input[type="text"],
    .izin-grid input[type="date"],
    .izin-grid input[type="file"],
    .izin-grid textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        border: 1px solid var(--border-c, #E0E0E0);
        border-radius: 8px;
        font-size: 14px;
        background: var(--input-bg, #fff);
        color: var(--text-main, #22262B);
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .izin-grid select:focus,
    .izin-grid input:focus,
    .izin-grid textarea:focus {
        outline: none;
        border-color: #C9A24B;
        box-shadow: 0 0 0 3px rgba(201,162,75,0.15);
    }

    .izin-grid input:disabled {
        background: #F5F5F5;
        color: #9AA0A8;
    }

    .izin-grid textarea {
        resize: vertical;
        min-height: 70px;
    }

    .field-hint {
        font-size: 12px;
        color: #9AA0A8;
        margin-top: 4px;
    }

    .izin-grid .btn-block {
        width: 100%;
        padding: 12px 16px;
        font-size: 15px;
        border-radius: 8px;
        margin-top: 4px;
    }

    /* ===== Tabel Riwayat (responsif, scroll di layar sempit) ===== */
    .izin-grid .table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 10px;
    }

    .izin-grid table {
        width: 100%;
        border-collapse: collapse;
        min-width: 480px; /* biar kolom tidak dempet, muncul scroll di HP */
        font-size: 13.5px;
        color: var(--text-main, #22262B);
    }

    .izin-grid table th,
    .izin-grid table td {
        padding: 10px 12px;
        text-align: left;
        white-space: nowrap;
        border-bottom: 1px solid var(--border-c, #EFEFEF);
    }

    .izin-grid table th {
        background: var(--input-bg, #FAFAFA);
        font-weight: 600;
        color: var(--text-dim, #555);
        position: sticky;
        top: 0;
    }

    .izin-grid table tr:hover td {
        background: rgba(201,162,75,0.08);
    }

    .izin-grid .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* ===== Penyesuaian ekstra untuk layar HP kecil ===== */
    @media (max-width: 480px) {
        .page-title { font-size: 20px; }
        .izin-grid .card { padding: 16px; }
        .izin-grid table { font-size: 12.5px; min-width: 420px; }
    }
</style>
@endpush

@section('content')
<div class="crumb">Home <span>›</span> Kehadiran <span>›</span> <b>Izin &amp; Cuti</b></div>
<div class="page-title" style="margin-bottom:18px;">Izin &amp; Cuti</div>

@if (session('success'))
    <div class="badge badge-green" style="display:block; padding:10px 14px; margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

<div class="izin-grid">
    {{-- Form Ajukan Izin/Cuti --}}
    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">Ajukan Surat Izin / Cuti</div>

        <form action="{{ route('leave-requests.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-row">
                <div class="field">
                    <label>Jenis Izin</label>
                    <select name="leave_type" required>
                        <option value="sakit">Sakit</option>
                        <option value="cuti_tahunan">Cuti Tahunan</option>
                        <option value="izin_pribadi">Izin Pribadi</option>
                        <option value="dinas_luar">Dinas Luar</option>
                    </select>
                    @error('leave_type') <div class="field-hint" style="color:#D34D3C;">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Durasi</label>
                    <input type="text" id="durasiPreview" placeholder="cth. 2 hari" disabled>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="start_date" id="startDate" value="{{ old('start_date') }}" required>
                    @error('start_date') <div class="field-hint" style="color:#D34D3C;">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="end_date" id="endDate" value="{{ old('end_date') }}" required>
                    @error('end_date') <div class="field-hint" style="color:#D34D3C;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="field">
                <label>Keterangan</label>
                <textarea name="reason" rows="3" placeholder="Jelaskan alasan izin...">{{ old('reason') }}</textarea>
                @error('reason') <div class="field-hint" style="color:#D34D3C;">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label>Lampiran (opsional)</label>
                <input type="file" name="attachment" accept="image/*,.pdf">
                <div class="field-hint">Unggah surat dokter atau dokumen pendukung lain — JPG, PNG, atau PDF, maks 5MB.</div>
                @error('attachment') <div class="field-hint" style="color:#D34D3C;">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-gold btn-block">Ajukan Izin</button>
        </form>
    </div>

    {{-- Riwayat Pengajuan Saya --}}
    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">Riwayat Pengajuan Saya</div>
        <div class="table-wrap">
            <table>
                <tr><th>Jenis</th><th>Tanggal</th><th>Durasi</th><th>Lampiran</th><th>Status</th></tr>

                @forelse ($myLeaveRequests as $leave)
                    <tr>
                        <td>{{ str_replace('_', ' ', ucfirst($leave->leave_type)) }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M') }}
                            @if ($leave->start_date != $leave->end_date)
                                – {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M') }}
                            @endif
                        </td>
                        <td>{{ $leave->duration_days }} hari</td>
                        <td>
                            @if ($leave->attachment_url)
                                <a href="{{ $leave->attachment_url }}" target="_blank" class="badge badge-gray">📎 lihat</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($leave->status === 'menunggu')
                                <span class="badge badge-gold">Menunggu</span>
                            @elseif ($leave->status === 'disetujui')
                                <span class="badge badge-green">Disetujui</span>
                            @else
                                <span class="badge badge-rust">Ditolak</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; color:#9AA0A8;">Belum ada pengajuan izin/cuti.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Isi otomatis field "Durasi" (read-only) begitu tanggal dipilih
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');
    const durasiPreview = document.getElementById('durasiPreview');

    function updateDurasi() {
        if (startInput.value && endInput.value) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            const diffDays = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
            durasiPreview.value = diffDays > 0 ? diffDays + ' hari' : '';
        }
    }
    startInput.addEventListener('change', updateDurasi);
    endInput.addEventListener('change', updateDurasi);
</script>
@endpush