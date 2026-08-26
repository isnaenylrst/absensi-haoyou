{{-- Jadwal: Karyawan Part Time / Guru --}}
<div class="subpage" id="jdw-parttime">

    <div class="page-actions" style="margin-bottom:14px;">
        <button type="button" class="btn btn-gold btn-sm">
            + Tambah Sesi Jadwal
        </button>
    </div>

    <div class="note-box" style="margin-top:0; margin-bottom:16px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
             stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 8v5M12 16h.01"/>
        </svg>
        <div>
            Jadwal Guru dibuat berdasarkan sesi mengajar.
            Dalam satu hari guru dapat memiliki lebih dari satu sesi.
        </div>
    </div>

    @forelse($jadwalPartTime as $pegawai)

        <div class="card" style="{{ $loop->last ? '' : 'margin-bottom:16px;' }}">

            {{-- HEADER GURU --}}
            <div class="card-head">
                <div>
                    <div class="card-title">{{ $pegawai->nama }}</div>
                    <div class="card-sub">
                        {{ $pegawai->jabatan }}
                        · Rate Rp {{ number_format($pegawai->rate_per_jam, 0, ',', '.') }}/jam
                    </div>
                </div>

                <a href="{{ route('jadwal-kerja.guru-bulanan', $pegawai->employee_id) }}"
                   class="btn btn-line btn-sm">
                    Lihat Jadwal
                </a>
            </div>

            {{-- JADWAL MINGGUAN (rekuren, tanpa tanggal) --}}
            @if($pegawai->sesi_mingguan->isNotEmpty())

                <div class="week-grid guru-week-grid">

                    @foreach(['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu'] as $hariKey => $hariLabel)

                        @php
                            $sesiHari = $pegawai->sesi_mingguan->get($hariKey);
                        @endphp

                        <div class="week-day">

                            <div class="week-day-label">{{ $hariLabel }}</div>

                            @if($sesiHari && $sesiHari->sesi->isNotEmpty())

                                @foreach($sesiHari->sesi as $sesi)
                                    <div class="session-chip">
                                        <div class="session-chip-time">
                                            {{ $sesi->jam_mulai }} – {{ $sesi->jam_selesai }}
                                        </div>
                                        <div class="session-chip-label">
                                            {{ $sesi->kegiatan ?: 'Mengajar Kelas' }}
                                        </div>
                                    </div>
                                @endforeach

                            @else

                                <div class="week-day-empty">— Libur</div>

                            @endif

                        </div>

                    @endforeach

                </div>

            @else

                <div class="empty-state">Belum ada jadwal mengajar.</div>

            @endif

            <div class="field-hint" style="margin-top:10px;">
                Total {{ $pegawai->total_sesi }} sesi
                · estimasi {{ $pegawai->total_jam }} jam.
            </div>

        </div>

    @empty

        <div class="card">
            <span class="kv-lbl">Belum ada guru yang memiliki jadwal.</span>
        </div>

    @endforelse

</div>