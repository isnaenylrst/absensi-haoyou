@extends('karyawan.dashboard')

@section('title', 'Izin & Cuti')

@section('content')
<div class="crumb">Home <span>›</span> Kehadiran <span>›</span> <b>Izin &amp; Cuti</b></div>
<div class="page-title" style="margin-bottom:18px;">Izin &amp; Cuti</div>

@if (session('success'))
    <div class="badge badge-green" style="display:block; padding:10px 14px; margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-2">
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