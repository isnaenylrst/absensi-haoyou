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

                        <button type="button" id="btnSwitchCam" class="cam-switch-btn" title="Ganti kamera">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 2.1 21 6l-4 3.9"/>
                                <path d="M3 12v-1a4 4 0 0 1 4-4h14"/>
                                <path d="m7 21.9-4-3.9 4-3.9"/>
                                <path d="M21 12v1a4 4 0 0 1-4 4H3"/>
                            </svg>
                        </button>

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

                    <div class="cam-btn-row">
                        <button type="button" id="btnCapture" class="btn btn-line">Ambil Foto</button>
                        <button type="button" id="btnRetake" class="btn btn-line" style="display:none;">Ambil Ulang</button>
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

                        <button type="button" id="btnSwitchCam" class="cam-switch-btn" title="Ganti kamera">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 2.1 21 6l-4 3.9"/>
                                <path d="M3 12v-1a4 4 0 0 1 4-4h14"/>
                                <path d="m7 21.9-4-3.9 4-3.9"/>
                                <path d="M21 12v1a4 4 0 0 1-4 4H3"/>
                            </svg>
                        </button>

                        <div class="cam-timestamp" id="camTimestamp"></div>
                        <div class="cam-geo">📍 Sudah absen masuk {{ $todayAttendance->check_in->format('H:i') }}</div>
                    </div>

                    <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" required>

                    <div class="cam-btn-row">
                        <button type="button" id="btnCapture" class="btn btn-line">Ambil Foto</button>
                        <button type="button" id="btnRetake" class="btn btn-line" style="display:none;">Ambil Ulang</button>
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

    <script>
        // ===== Pilih shift =====
        const shiftInput = document.getElementById('shiftInput');
        document.querySelectorAll('.shift-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                opt.parentElement.querySelectorAll('.shift-opt').forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                if (shiftInput) shiftInput.value = opt.dataset.shiftId;
            });
        });

        // ===== Kamera + GPS + Radar =====
        (function () {
            const video = document.getElementById('camVideo');
            const preview = document.getElementById('camPreview');
            const placeholder = document.getElementById('camPlaceholder');
            const camTimestamp = document.getElementById('camTimestamp');
            const camGeoLabel = document.getElementById('camGeoLabel');
            const btnCapture = document.getElementById('btnCapture');
            const btnRetake = document.getElementById('btnRetake');
            const btnSwitchCam = document.getElementById('btnSwitchCam');
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
            let currentFacing = 'user'; // default kamera depan (untuk foto selfie verifikasi wajah)

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

            async function startCamera(facing) {
                // Matikan stream lama dulu kalau ada (mis. saat switch kamera)
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }

                if (btnSwitchCam) btnSwitchCam.disabled = true;

                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: facing } },
                        audio: false,
                    });
                    video.srcObject = stream;
                    video.style.display = 'block';
                    placeholder.style.display = 'none';
                    currentFacing = facing;
                } catch (e) {
                    placeholder.style.display = 'flex';
                    placeholder.innerHTML = 'Kamera tidak tersedia / izin ditolak';
                    if (btnCapture) btnCapture.disabled = true;
                } finally {
                    if (btnSwitchCam) btnSwitchCam.disabled = false;
                }
            }

            if (btnSwitchCam) {
                btnSwitchCam.addEventListener('click', function () {
                    const next = currentFacing === 'user' ? 'environment' : 'user';
                    startCamera(next);
                });
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
                        if (btnSwitchCam) btnSwitchCam.style.display = 'none';

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
                    if (btnSwitchCam) btnSwitchCam.style.display = 'flex';
                    hasPhoto = false;
                    updateSubmitState();
                    photoInput.value = '';
                    startCamera(currentFacing);
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

            startCamera(currentFacing);
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