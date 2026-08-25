@extends('karyawan.dashboard')

@section('title', 'Presensi | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/karyawan/presensi.css') }}">
@endpush

@section('content')

    <!-- ============ PRESENSI ============ -->
    <div class="crumb">
        Home <span>›</span> Kehadiran <span>›</span> <b>Presensi</b>
    </div>

    <div class="page-title" style="margin-bottom:14px;">
        Presensi
    </div>

    @if (session('success'))
        <div style="background:var(--green-soft); color:var(--green); border-radius:10px; padding:11px 14px; font-size:12.5px; font-weight:600; margin-bottom:14px;">
            {{ session('success') }}
        </div>
    @endif

    @error('attendance')
        <div style="background:var(--rust-soft); color:var(--rust); border-radius:10px; padding:11px 14px; font-size:12.5px; font-weight:600; margin-bottom:14px;">
            {{ $message }}
        </div>
    @enderror

    @if ($errors->any() && ! $errors->has('attendance'))
        <div style="background:var(--rust-soft); color:var(--rust); border-radius:10px; padding:11px 14px; font-size:12.5px; font-weight:600; margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="pilltabs">
        <div class="pilltab {{ $employee->employee_type !== 'part_time' ? 'active' : '' }}" data-sub="ps-tetap">
            Karyawan Tetap
        </div>

        @if ($canSubmitTeachingSessions)
            <div class="pilltab {{ $employee->employee_type === 'part_time' ? 'active' : '' }}" data-sub="ps-parttime">
                Sesi Mengajar
            </div>
        @endif
    </div>

    <!-- Presensi: Karyawan Tetap (shift + radius + kamera) -->
    <div class="subpage {{ $employee->employee_type !== 'part_time' ? 'active' : '' }}" id="ps-tetap">

        <div class="grid grid-2">

            <!-- Absen Foto & Radius -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">
                            Absen dengan Foto &amp; Radius
                        </div>

                        <div class="card-sub">
                            Wajah harus terlihat jelas, lokasi diverifikasi otomatis
                        </div>
                    </div>
                </div>

                @if ($shifts->isEmpty())
                    {{-- ===== Tidak ada shift yang berlaku untuk hari ini ===== --}}
                    <div class="note-box">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v5M12 16h.01"/>
                        </svg>
                        <div>
                            Tidak ada shift kerja yang berlaku untuk hari ini.
                        </div>
                    </div>

                @elseif (! $todayAttendance)
                    {{-- ===== Belum absen masuk hari ini ===== --}}
                    <form action="{{ route('presensi.check-in') }}" method="POST" enctype="multipart/form-data" id="camForm">
                        @csrf
                        <input type="hidden" name="attendance_mode" value="fixed">

                        <div class="cam-frame" id="camFrame">
                            <video id="camVideo" autoplay playsinline style="display:none;"></video>
                            <img id="camPreview" style="display:none;">

                            <div class="cam-placeholder" id="camPlaceholder">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#B9A98A" stroke-width="1.6">
                                    <path d="M4 8a2 2 0 0 1 2-2h1.2l1-1.6h7.6l1 1.6H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/>
                                    <circle cx="12" cy="12.5" r="3.4"/>
                                </svg>
                                Pratinjau kamera real-time
                            </div>

                            <div class="cam-timestamp" id="camTimestamp"></div>
                            <div class="cam-geo" id="camGeoLabel">📍 Mendeteksi lokasi...</div>
                        </div>

                        <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" required>
                        <input type="hidden" name="latitude" id="latInput">
                        <input type="hidden" name="longitude" id="lngInput">
                        <input type="hidden" name="shift_id" id="shiftInput" value="{{ $shifts->first()->id ?? '' }}">

                        <div class="shift-toggle">
                            @foreach ($shifts as $s)
                                <div class="shift-opt {{ $loop->first ? 'active' : '' }}" data-shift-id="{{ $s->id }}">
                                    <div class="shift-opt-label">{{ $s->name ?? 'Shift' }}</div>
                                    <div class="shift-opt-time">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div style="display:flex; gap:8px; margin-top:18px;">
                            <button type="button" id="btnCapture" class="btn btn-line" style="flex:1;">Ambil Foto</button>
                            <button type="button" id="btnRetake" class="btn btn-line" style="flex:1; display:none;">Ambil Ulang</button>
                        </div>

                        <button type="submit" id="btnSubmitCam" class="btn btn-gold btn-block" style="margin-top:10px; padding:14px;" disabled>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            Absen Masuk Sekarang
                        </button>
                    </form>

                @elseif (! $todayAttendance->check_out)
                    {{-- ===== Sudah check-in, belum check-out ===== --}}
                    <form action="{{ route('presensi.check-out') }}" method="POST" enctype="multipart/form-data" id="camForm">
                        @csrf

                        <div class="cam-frame" id="camFrame">
                            <video id="camVideo" autoplay playsinline style="display:none;"></video>
                            <img id="camPreview" style="display:none;">

                            <div class="cam-placeholder" id="camPlaceholder">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#B9A98A" stroke-width="1.6">
                                    <path d="M4 8a2 2 0 0 1 2-2h1.2l1-1.6h7.6l1 1.6H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/>
                                    <circle cx="12" cy="12.5" r="3.4"/>
                                </svg>
                                Pratinjau kamera real-time
                            </div>

                            <div class="cam-timestamp" id="camTimestamp"></div>
                            <div class="cam-geo">📍 Sudah absen masuk {{ $todayAttendance->check_in->format('H:i') }}</div>
                        </div>

                        <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" required>

                        <div style="display:flex; gap:8px; margin-top:18px;">
                            <button type="button" id="btnCapture" class="btn btn-line" style="flex:1;">Ambil Foto</button>
                            <button type="button" id="btnRetake" class="btn btn-line" style="flex:1; display:none;">Ambil Ulang</button>
                        </div>

                        <button type="submit" id="btnSubmitCam" class="btn btn-gold btn-block" style="margin-top:10px; padding:14px;" disabled>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            Absen Pulang Sekarang
                        </button>
                    </form>

                @else
                    {{-- ===== Sudah absen masuk & pulang hari ini ===== --}}
                    <div class="note-box">
                        <div>
                            Presensi hari ini sudah selesai — masuk <b>{{ $todayAttendance->check_in->format('H:i') }}</b>,
                            pulang <b>{{ $todayAttendance->check_out->format('H:i') }}</b>.
                        </div>
                    </div>
                @endif
            </div>

            <!-- Status Radius Lokasi -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">
                            Status Radius Lokasi
                        </div>

                        <div class="card-sub">
                            Titik kantor: {{ $employee->branch->address ?? '—' }}
                        </div>
                    </div>
                </div>

                <div class="geo-wrap">

                    <div class="radar">
                        <div class="radar-ring r1"></div>
                        <div class="radar-ring r2"></div>
                        <div class="radar-ring r3"></div>
                        <div class="radar-pulse"></div>

                        <div class="radar-center">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8">
                                <path d="M12 21s7-6.2 7-11.5A7 7 0 1 0 5 9.5C5 14.8 12 21 12 21Z"/>
                                <circle cx="12" cy="9.5" r="2.3"/>
                            </svg>
                        </div>
                    </div>

                    <div class="geo-status">
                        <div class="geo-status-title" id="geoStatusTitle">Mendeteksi lokasi...</div>
                        <div class="geo-status-sub" id="geoStatusSub">Mohon izinkan akses lokasi di browser.</div>
                    </div>

                    <div class="geo-meta">
                        <div class="geo-meta-item">
                            <div class="geo-meta-val">{{ $employee->branch->radius_meter ?? '—' }} m</div>
                            <div class="geo-meta-lbl">Batas radius</div>
                        </div>

                        <div class="geo-meta-item">
                            <div class="geo-meta-val" id="geoMetaDistance">— m</div>
                            <div class="geo-meta-lbl">Jarak saat ini</div>
                        </div>

                        <div class="geo-meta-item">
                            <div class="geo-meta-val" id="geoMetaAccuracy">GPS</div>
                            <div class="geo-meta-lbl">Akurasi tinggi</div>
                        </div>
                    </div>
                </div>

                <div class="divider-label">
                    Riwayat absensi minggu ini
                </div>

                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status</th>
                        </tr>

                        @forelse ($weekAttendances as $a)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($a->tanggal)->translatedFormat('D, d M') }}</td>
                                <td>{{ $a->shift->name ?? '—' }}</td>
                                <td class="mono">{{ $a->check_in?->format('H:i') ?? '—' }}</td>
                                <td class="mono">{{ $a->check_out?->format('H:i') ?? '—' }}</td>
                                <td>
                                    @if ($a->status === 'tepat_waktu')
                                        <span class="badge badge-green">Tepat waktu</span>
                                    @elseif ($a->status === 'terlambat')
                                        @php
                                            $lateMinutes = (int) ($a->late_minutes ?? 0);
                                            $lateHours = intdiv($lateMinutes, 60);
                                            $remainingMinutes = $lateMinutes % 60;
                                            $lateText = [];

                                            if ($lateHours > 0) {
                                                $lateText[] = $lateHours . ' jam';
                                            }

                                            if ($remainingMinutes > 0) {
                                                $lateText[] = $remainingMinutes . ' menit';
                                            }
                                        @endphp

                                        <span class="badge badge-rust">
                                            Terlambat {{ implode(' ', $lateText) }}
                                        </span>
                                    @else
                                        <span class="badge badge-gray">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Belum ada riwayat minggu ini.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Presensi: Karyawan Part Time (hari+jam + upload foto) -->
    @if ($canSubmitTeachingSessions)
    <div class="subpage {{ $employee->employee_type === 'part_time' ? 'active' : '' }}" id="ps-parttime">

        <div class="card" style="margin-bottom:16px;">
            <div class="card-head">
                <div>
                    <div class="card-title">
                        Sesi Hari Ini — {{ \Carbon\Carbon::now()->translatedFormat('l, d F') }}
                    </div>

                    <div class="card-sub">
                        Kamu bisa presensi lebih dari satu kali sehari, satu kali per sesi
                    </div>
                </div>
            </div>

            @forelse ($todayAttendances as $a)
                <div class="session-status-row">
                    <div>
                        <div class="session-status-time">
                            {{ $a->check_in?->format('H:i') ?? '—' }} – {{ $a->check_out?->format('H:i') ?? '—' }} · {{ $a->activity }}
                        </div>
                        <div class="session-status-label">
                            Presensi terkirim {{ $a->check_in?->format('H:i') ?? '—' }}
                        </div>
                    </div>
                    <span class="badge badge-green">✓ Selesai</span>
                </div>
            @empty
                <div class="session-status-row">
                    <div>
                        <div class="session-status-time">Belum ada sesi</div>
                        <div class="session-status-label">Kirim presensi sesi pertamamu hari ini lewat form di bawah</div>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="grid grid-2">

            <!-- Presensi Sesuai Jadwal -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Tambah Presensi</div>
                        <div class="card-sub">Tambahkan beberapa sesi sekaligus</div>
                    </div>
                </div>

                <div class="note-box">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8A6212" stroke-width="2" style="flex-shrink:0; margin-top:1px;">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                    <div>
                        Isi jam dan keterangan untuk setiap sesi yang kamu jalani hari ini.
                    </div>
                </div>

                <form action="{{ route('presensi.check-in') }}" method="POST" id="partTimeForm">
                    @csrf
                    <input type="hidden" name="attendance_mode" value="teaching">
                    <div id="sessionRows" class="session-rows">
                        @php($oldSessions = old('sessions', [['start_time' => '', 'end_time' => '', 'activity' => '']]))
                        @foreach ($oldSessions as $index => $session)
                            <div class="session-input-row">
                                <div class="field">
                                    <label>Jam Mulai</label>
                                    <input type="time" name="sessions[{{ $index }}][start_time]" value="{{ $session['start_time'] ?? '' }}" required>
                                </div>
                                <div class="field">
                                    <label>Jam Selesai</label>
                                    <input type="time" name="sessions[{{ $index }}][end_time]" value="{{ $session['end_time'] ?? '' }}" required>
                                </div>
                                <div class="field session-activity-field">
                                    <label>Keterangan</label>
                                    <input type="text" name="sessions[{{ $index }}][activity]" placeholder="cth. Mengajar Kelas 6B" value="{{ $session['activity'] ?? '' }}" required>
                                </div>
                                <button type="button" class="remove-session btn btn-line" title="Hapus sesi" aria-label="Hapus sesi">-</button>
                            </div>
                        @endforeach
                    </div>

                    <div class="session-form-actions">
                        <button type="button" id="addSession" class="btn btn-line add-session">+ Tambah Sesi</button>
                        <button type="submit" class="btn btn-gold" style="padding:14px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                            Kirim Semua Presensi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Jadwal Minggu Ini -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Jadwal Minggu Ini</div>
                        <div class="card-sub">{{ $employee->full_name }}</div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <tr><th>Hari</th><th>Jam</th><th>Kegiatan</th></tr>
                        @forelse ($weekSchedules as $s)
                            <tr>
                                <td style="text-transform:capitalize;">{{ $s->day_of_week }}</td>
                                <td class="mono">{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</td>
                                <td>{{ $s->activity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">Belum ada jadwal referensi.</td></tr>
                        @endforelse
                    </table>
                </div>

                <div class="field-hint" style="margin-top:8px;">
                    Jadwal di atas hanya referensi. Jam presensi aktual tetap diisi manual sesuai kegiatan sebenarnya — bisa punya beberapa sesi terpisah dalam sehari.
                </div>

                <div class="divider-label">Riwayat Presensi Terakhir</div>

                <div class="table-wrap">
                    <table>
                        <tr><th>Tanggal</th><th>Sesi</th><th>Kirim</th><th>Status</th></tr>
                        @forelse ($recentAttendances as $a)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($a->tanggal)->translatedFormat('d M') }}</td>
                                <td class="mono">{{ $a->check_in?->format('H:i') ?? '—' }}–{{ $a->check_out?->format('H:i') ?? '—' }}</td>
                                <td class="mono">{{ $a->check_in?->format('H:i') ?? '—' }}</td>
                                <td><span class="badge badge-green">Tercatat</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada riwayat.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>

        </div>
    </div>
    @endif

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

        // ===== Pilih shift (Karyawan Tetap) =====
        const shiftInput = document.getElementById('shiftInput');
        document.querySelectorAll('.shift-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                opt.parentElement.querySelectorAll('.shift-opt').forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                if (shiftInput) shiftInput.value = opt.dataset.shiftId;
            });
        });

        // ===== Tambah beberapa sesi part-time =====
        (function () {
            const rows = document.getElementById('sessionRows');
            const addButton = document.getElementById('addSession');

            if (!rows || !addButton) return;

            function renumberRows() {
                rows.querySelectorAll('.session-input-row').forEach((row, index) => {
                    row.querySelectorAll('input').forEach(input => {
                        input.name = input.name.replace(/sessions\[\d+\]/, `sessions[${index}]`);
                    });
                    row.querySelector('.remove-session').disabled = rows.children.length === 1;
                });
            }

            addButton.addEventListener('click', () => {
                if (rows.children.length >= 20) return;
                const row = rows.querySelector('.session-input-row').cloneNode(true);
                row.querySelectorAll('input').forEach(input => input.value = '');
                rows.appendChild(row);
                renumberRows();
            });

            rows.addEventListener('click', event => {
                if (event.target.closest('.remove-session')) {
                    event.target.closest('.session-input-row').remove();
                    renumberRows();
                }
            });

            renumberRows();
        })();

        // ===== Kamera + GPS + Radar (Karyawan Tetap) =====
        (function () {
            const video = document.getElementById('camVideo');
            const preview = document.getElementById('camPreview');
            const placeholder = document.getElementById('camPlaceholder');
            const camTimestamp = document.getElementById('camTimestamp');
            const camGeoLabel = document.getElementById('camGeoLabel');
            const btnCapture = document.getElementById('btnCapture');
            const btnRetake = document.getElementById('btnRetake');
            const photoInput = document.getElementById('photoInput');
            const btnSubmitCam = document.getElementById('btnSubmitCam');
            const latInput = document.getElementById('latInput');
            const lngInput = document.getElementById('lngInput');

            if (!video) return; // form kamera tidak dirender (mis. sudah selesai absen hari ini)

            const branchLat = {{ $employee->branch->latitude ?? 'null' }};
            const branchLng = {{ $employee->branch->longitude ?? 'null' }};
            const branchRadius = {{ $employee->branch->radius_meter ?? 'null' }};

            const geoStatusTitle = document.getElementById('geoStatusTitle');
            const geoStatusSub = document.getElementById('geoStatusSub');
            const geoMetaDistance = document.getElementById('geoMetaDistance');
            const geoMetaAccuracy = document.getElementById('geoMetaAccuracy');

            let stream = null;
            let hasPhoto = false;
            let hasLocation = !latInput; // kalau nggak ada latInput (form checkout), lokasi tidak wajib

            function distanceMeters(lat1, lng1, lat2, lng2) {
                const R = 6371000;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLng = (lng2 - lng1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
                return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
            }

            function updateSubmitState() {
                if (btnSubmitCam) btnSubmitCam.disabled = !(hasPhoto && hasLocation);
            }

            function tickClock() {
                if (camTimestamp) camTimestamp.textContent = new Date().toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'medium' });
            }
            setInterval(tickClock, 1000);
            tickClock();

            async function startCamera() {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    video.srcObject = stream;
                    video.style.display = 'block';
                    placeholder.style.display = 'none';
                } catch (e) {
                    placeholder.querySelector('div') || null;
                    placeholder.innerHTML = 'Kamera tidak tersedia / izin ditolak';
                    if (btnCapture) btnCapture.disabled = true;
                }
            }

            if (btnCapture) {
                btnCapture.addEventListener('click', function () {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);

                    canvas.toBlob(function (blob) {
                        const file = new File([blob], `presensi-${Date.now()}.jpg`, { type: 'image/jpeg' });
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        photoInput.files = dt.files;

                        preview.src = URL.createObjectURL(blob);
                        preview.style.display = 'block';
                        video.style.display = 'none';
                        btnCapture.style.display = 'none';
                        btnRetake.style.display = 'inline-flex';

                        hasPhoto = true;
                        updateSubmitState();

                        if (stream) stream.getTracks().forEach(t => t.stop());
                    }, 'image/jpeg', 0.85);
                });
            }

            if (btnRetake) {
                btnRetake.addEventListener('click', function () {
                    preview.style.display = 'none';
                    btnRetake.style.display = 'none';
                    btnCapture.style.display = 'inline-flex';
                    hasPhoto = false;
                    updateSubmitState();
                    photoInput.value = '';
                    startCamera();
                });
            }

            let locationWatchId = null;
            let bestAccuracy = Infinity;

            function watchLocation() {
                if (!navigator.geolocation) {
                    if (geoStatusTitle) {
                        geoStatusTitle.textContent = 'GPS tidak didukung';
                    }
                    return;
                }

                if (geoStatusTitle) {
                    geoStatusTitle.textContent = 'Mencari lokasi terbaik...';
                }

                locationWatchId = navigator.geolocation.watchPosition(
                    function (pos) {
                        const { latitude, longitude, accuracy } = pos.coords;

                        if (accuracy >= bestAccuracy) return;

                        bestAccuracy = accuracy;

                        if (latInput) {
                            latInput.value = latitude;
                            lngInput.value = longitude;
                            hasLocation = true;
                        }

                        if (camGeoLabel) {
                            camGeoLabel.textContent =
                                `📍 ${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;
                        }

                        if (geoMetaAccuracy) {
                            geoMetaAccuracy.textContent =
                                `±${Math.round(accuracy)} m`;
                        }

                        if (branchLat !== null && branchLng !== null) {
                            const d = distanceMeters(
                                branchLat,
                                branchLng,
                                latitude,
                                longitude
                            );

                            if (geoMetaDistance) {
                                geoMetaDistance.textContent = `${d} m`;
                            }

                            if (branchRadius !== null && d <= branchRadius) {
                                geoStatusTitle.textContent = 'Dalam radius kerja';
                                geoStatusSub.textContent =
                                    `Kamu berada ${d} m dari titik kantor`;
                            } else {
                                geoStatusTitle.textContent = 'Di luar radius kerja';
                                geoStatusSub.textContent =
                                    `Kamu berada ${d} m dari titik kantor`;
                            }
                        }

                        if (accuracy <= 30 && locationWatchId !== null) {
                            navigator.geolocation.clearWatch(locationWatchId);
                            locationWatchId = null;
                        }

                        updateSubmitState();
                    },
                    function () {
                        if (geoStatusTitle) {
                            geoStatusTitle.textContent = 'Gagal mendeteksi lokasi';
                        }

                        if (geoStatusSub) {
                            geoStatusSub.textContent =
                                'Aktifkan GPS atau izinkan akses lokasi browser.';
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 8000,
                        maximumAge: 0,
                    }
                );

                setTimeout(function () {
                    if (locationWatchId !== null) {
                        navigator.geolocation.clearWatch(locationWatchId);
                        locationWatchId = null;
                    }
                }, 8000);
            }

            startCamera();
            watchLocation();
        })();

        // ===== Toast notifikasi =====
        let toastTimer;
        function showToast(msg) {
            let toast = document.getElementById('appToast');
            if (!toast) {
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
    </script>

@endsection