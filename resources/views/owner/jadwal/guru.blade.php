{{-- Jadwal: Karyawan Part Time / Guru (per sesi) --}}
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
      <a href="{{ route('jadwal-kerja.guru-bulanan', $pegawai->employee_id) }}" class="btn btn-line btn-sm">
        Edit Jadwal
      </a>
    </div>
    @forelse($pegawai->sesi_mingguan as $sesiMinggu)
      <div class="guru-week-block">
        <div class="guru-week-title">
          Minggu {{ $sesiMinggu->first()->tanggal->weekOfMonth }}
          <span>{{ $sesiMinggu->first()->tanggal->startOfWeek()->translatedFormat('d M') }} &ndash; {{ $sesiMinggu->first()->tanggal->endOfWeek()->translatedFormat('d M Y') }}</span>
        </div>
        <div class="week-grid guru-week-grid">
          @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari)
            @php
              $tanggalHari = $sesiMinggu->first()->tanggal->copy()->startOfWeek()->addDays($loop->index);
              $sesiHari = $sesiMinggu->get($tanggalHari->format('Y-m-d'), (object) ['sesi' => collect()]);
            @endphp
            <div class="week-day">
              <div class="week-day-label">{{ $hari }}</div>
              <div class="field-hint">{{ $tanggalHari->format('d/m/Y') }}</div>
              @forelse($sesiHari->sesi as $sesi)
                <div class="session-chip">
                  <div class="session-chip-time">{{ $sesi->jam_mulai }}–{{ $sesi->jam_selesai }}</div>
                  <div class="session-chip-label">{{ $sesi->kegiatan ?? 'Mengajar Kelas' }}</div>
                </div>
              @empty
                <div class="week-day-empty">— Libur</div>
              @endforelse
            </div>
          @endforeach
        </div>
      </div>
    @empty
      <div class="empty-state">Belum ada presensi mengajar.</div>
    @endforelse
    <div class="field-hint" style="margin-top:10px;">Total {{ $pegawai->total_sesi }} sesi tercatat · estimasi {{ $pegawai->total_jam }} jam.</div>
  </div>
  @empty
  <div class="card"><span class="kv-lbl">Belum ada karyawan part time yang terdaftar.</span></div>
  @endforelse
</div>