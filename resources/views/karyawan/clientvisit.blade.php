@extends('karyawan.dashboard')

@section('title', 'Kunjungan Klien | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/karyawan/kunjungan-klien.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
@endpush

@section('content')

<div class="crumb">Home <span>&rsaquo;</span> Kehadiran <span>&rsaquo;</span> <b>Kunjungan Klien</b></div>
<div class="page-head"><div class="page-title">Kunjungan Klien</div></div>

@if (session('success'))
    <div class="note-box note-box-green" style="margin-bottom:16px;">
        <i class="ti ti-circle-check" style="font-size:15px;"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <div>
            <div class="card-title">Catat Kunjungan Klien</div>
            <div class="card-sub">Untuk kunjungan privat / lokasi klien — ambil foto langsung di lokasi</div>
        </div>
    </div>

    <form action="{{ route('kunjungan-klien-saya.store') }}" method="POST" enctype="multipart/form-data" id="visitForm">
        @csrf
        <div class="form-row">
            <div class="field">
                <label>Nama Klien / Lokasi</label>
                <input type="text" name="client_name" placeholder="cth. Bimbel Privat Ananda" value="{{ old('client_name') }}" required>
                @error('client_name') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label>Jenis Kunjungan</label>
                <select name="visit_type" required>
                    <option value="Les Privat" @selected(old('visit_type') === 'Les Privat')>Les Privat</option>
                    <option value="Kunjungan Sales" @selected(old('visit_type') === 'Kunjungan Sales')>Kunjungan Sales</option>
                    <option value="Survei Lokasi" @selected(old('visit_type') === 'Survei Lokasi')>Survei Lokasi</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label style="display:flex; align-items:center; justify-content:space-between;">
                    <span>Alamat</span>
                    <span style="display:flex; gap:6px;">
                        <button type="button" id="btnAddrAuto" class="btn btn-sm btn-gold">
                            <i class="fa-solid fa-location-dot" style="font-size:11px;"></i> Deteksi Otomatis
                        </button>
                        <button type="button" id="btnAddrManual" class="btn btn-sm btn-line">Input Manual</button>
                    </span>
                </label>
                <input type="text" name="address" id="addressInput" placeholder="Alamat lokasi kunjungan" value="{{ old('address') }}" required>
                <div class="field-hint" id="addrStatus">Deteksi otomatis menggunakan lokasi GPS perangkat.</div>
                @error('address') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label>Catatan</label>
                <input type="text" name="notes" placeholder="Opsional" value="{{ old('notes') }}">
            </div>
        </div>

        <input type="hidden" name="latitude" id="latInput">
        <input type="hidden" name="longitude" id="lngInput">
        <input type="hidden" name="accuracy_m" id="accInput">

        <div class="field">
            <label>Foto Lokasi (wajib diambil langsung dari kamera)</label>

            <div class="cam-frame" id="camFrame">
                <video id="camVideo" autoplay playsinline></video>
                <img id="camPreview" style="display:none;">
                <div id="camPlaceholder" class="cam-placeholder" style="display:none;">
                    <i class="ti ti-camera-off" style="font-size:26px;"></i>
                    Kamera tidak tersedia / izin ditolak
                </div>
            </div>
            <canvas id="camCanvas" style="display:none;"></canvas>

            <div class="cam-actions">
                <button type="button" id="btnCapture" class="btn btn-gold btn-sm">
                    <i class="ti ti-camera" style="font-size:14px;"></i> Ambil Foto
                </button>
                <button type="button" id="btnRetake" class="btn btn-line btn-sm" style="display:none;">
                    <i class="ti ti-refresh" style="font-size:14px;"></i> Ambil Ulang
                </button>
            </div>

            <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" required>
            @error('photo') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-gold btn-block" style="margin-top:16px;" id="btnSubmit" disabled>
            Simpan Kunjungan
        </button>
    </form>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <div class="card-title">Riwayat Kunjungan Saya</div>
            <div class="card-sub">Log kunjungan klien yang sudah kamu catat</div>
        </div>
        <div class="quota-chip">
            Menampilkan <b>{{ $visits->count() }}</b> dari <b>{{ $visits->total() }}</b> data
        </div>
    </div>

    <div class="grid grid-3">
        @forelse ($visits as $visit)
            <div class="visit-card">
                <div class="visit-photo">
                    <img src="{{ $visit->photo_url }}" alt="{{ $visit->client_name }}">
                    <div class="visit-pin">
                        <i class="ti ti-map-pin" style="font-size:11px;"></i>
                        {{ $visit->accuracy_m ? 'Ketepatan lokasi ±' . round($visit->accuracy_m) . ' m': 'Lokasi manual' }}
                    </div>
                </div>
                <div class="visit-body">
                    <div class="visit-name">{{ $visit->client_name }}</div>
                    <div class="visit-meta">{{ $visit->visit_type }} · {{ $visit->visited_at->translatedFormat('d M, H:i') }}</div>
                    <div style="margin-top:8px;">
                        @if ($visit->review_status === 'perlu_ditinjau')
                            <span class="badge badge-rust">&#9888; Perlu Ditinjau</span>
                        @else
                            <span class="badge badge-green">&#10003; Wajar</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p>Belum ada kunjungan tercatat.</p>
        @endforelse
    </div>

    @if ($visits->hasPages())
        <div class="pagination-bar">
            @if ($visits->onFirstPage())
                <span class="btn btn-line btn-sm btn-disabled">&larr; Sebelumnya</span>
            @else
                <a href="{{ $visits->previousPageUrl() }}" class="btn btn-line btn-sm">&larr; Sebelumnya</a>
            @endif

            <span class="pagination-info">Halaman {{ $visits->currentPage() }} dari {{ $visits->lastPage() }}</span>

            @if ($visits->hasMorePages())
                <a href="{{ $visits->nextPageUrl() }}" class="btn btn-line btn-sm">Selanjutnya &rarr;</a>
            @else
                <span class="btn btn-line btn-sm btn-disabled">Selanjutnya &rarr;</span>
            @endif
        </div>
    @endif
</div>

<script>
(function () {
    const form = document.getElementById('visitForm');
    const latInput = document.getElementById('latInput');
    const lngInput = document.getElementById('lngInput');
    const accInput = document.getElementById('accInput');
    const addressInput = document.getElementById('addressInput');
    const addrStatus = document.getElementById('addrStatus');
    const btnAddrAuto = document.getElementById('btnAddrAuto');
    const btnAddrManual = document.getElementById('btnAddrManual');

    const video = document.getElementById('camVideo');
    const preview = document.getElementById('camPreview');
    const placeholder = document.getElementById('camPlaceholder');
    const canvas = document.getElementById('camCanvas');
    const btnCapture = document.getElementById('btnCapture');
    const btnRetake = document.getElementById('btnRetake');
    const photoInput = document.getElementById('photoInput');
    const btnSubmit = document.getElementById('btnSubmit');

    let stream = null;

    function useManualAddress() {
        addressInput.readOnly = false;
        addressInput.value = '';
        latInput.value = '';
        lngInput.value = '';
        accInput.value = '';

        btnAddrManual.classList.add('btn-gold');
        btnAddrManual.classList.remove('btn-line');

        btnAddrAuto.classList.remove('btn-gold');
        btnAddrAuto.classList.add('btn-line');

        addrStatus.textContent =
            'Mode manual aktif. Masukkan alamat lokasi kunjungan.';
        addressInput.focus();
    }

    function setAutoMode() {
        addressInput.readOnly = true;

        btnAddrAuto.classList.add('btn-gold');
        btnAddrAuto.classList.remove('btn-line');

        btnAddrManual.classList.remove('btn-gold');
        btnAddrManual.classList.add('btn-line');
    }

    function detectLocation() {
        setAutoMode();
        btnAddrAuto.disabled = true;
        addrStatus.textContent = 'Mendeteksi lokasi GPS...';

        if (!navigator.geolocation) {
            btnAddrAuto.disabled = false;
            useManualAddress();
            addrStatus.textContent = 'GPS tidak tersedia. Gunakan input manual.';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async function (position) {
                const { latitude, longitude, accuracy } = position.coords;

                latInput.value = latitude;
                lngInput.value = longitude;
                accInput.value = Math.round(accuracy);

                addressInput.value = 'Mengambil alamat jalan...';

                try {
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
                        {
                            headers: {
                                'Accept-Language': 'id',
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Alamat tidak ditemukan');
                    }

                    const data = await response.json();
                    const address = data.address || {};

                    const street = [
                        address.road,
                        address.house_number,
                    ].filter(Boolean).join(' ');

                    const area = [
                        address.village ||
                        address.suburb ||
                        address.neighbourhood,
                        address.town ||
                        address.city ||
                        address.municipality,
                        address.state,
                    ].filter(Boolean).join(', ');

                    addressInput.value =
                        street || area
                            ? [street, area].filter(Boolean).join(', ')
                            : data.display_name;

                    addrStatus.textContent =
                        `Alamat terdeteksi. Ketepatan GPS ±${Math.round(accuracy)} m`;
                } catch (error) {
                    addressInput.value =
                        `Koordinat: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;

                    addrStatus.textContent =
                        'Alamat jalan tidak ditemukan. Silakan lengkapi manual.';
                }

                btnAddrAuto.disabled = false;
            },
            function (error) {
                btnAddrAuto.disabled = false;
                useManualAddress();

                const messages = {
                    1: 'Izin lokasi ditolak. Izinkan lokasi pada browser.',
                    2: 'Lokasi tidak tersedia. Gunakan input manual.',
                    3: 'Waktu deteksi habis. Coba lagi atau gunakan manual.',
                };

                addrStatus.textContent =
                    messages[error.code] || 'Lokasi gagal dideteksi.';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    }

    btnAddrAuto.addEventListener('click', detectLocation);
    btnAddrManual.addEventListener('click', useManualAddress);

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                },
                audio: false,
            });

            video.srcObject = stream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
            btnCapture.disabled = false;
        } catch (error) {
            video.style.display = 'none';
            placeholder.style.display = 'flex';
            btnCapture.disabled = true;
        }
    }

    btnCapture.addEventListener('click', function () {
        if (!video.videoWidth || !video.videoHeight) return;

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        canvas.getContext('2d').drawImage(
            video,
            0,
            0,
            canvas.width,
            canvas.height
        );

        canvas.toBlob(function (blob) {
            const file = new File(
                [blob],
                `kunjungan-${Date.now()}.jpg`,
                { type: 'image/jpeg' }
            );

            const transfer = new DataTransfer();
            transfer.items.add(file);
            photoInput.files = transfer.files;

            preview.src = URL.createObjectURL(blob);
            preview.style.display = 'block';
            video.style.display = 'none';
            btnCapture.style.display = 'none';
            btnRetake.style.display = 'inline-flex';
            btnSubmit.disabled = false;

            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }, 'image/jpeg', 0.85);
    });

    btnRetake.addEventListener('click', function () {
        preview.src = '';
        preview.style.display = 'none';
        btnRetake.style.display = 'none';
        btnCapture.style.display = 'inline-flex';
        btnSubmit.disabled = true;
        photoInput.value = '';

        startCamera();
    });

    form.addEventListener('submit', function (event) {
        if (!addressInput.value.trim()) {
            event.preventDefault();
            alert('Alamat wajib diisi.');
            addressInput.focus();
            return;
        }

        if (!photoInput.files.length) {
            event.preventDefault();
            alert('Foto lokasi wajib diambil.');
        }
    });

    useManualAddress();
    startCamera();
})();
</script>
@endsection