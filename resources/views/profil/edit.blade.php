<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — Haoyou Presence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f8fa;
            --paper: #ffffff;
            --gold: #ffbd08;
            --gold-deep: #DE8C0F;
            --text: #22262B;
            --text-dim: #6B7280;
            --text-faint: #9AA0A8;
            --line: #EDEEF0;
            --green: #2F8A5B;
            --green-soft: #E7F5EC;
            --rust: #D34D3C;
            --rust-soft: #FCEAE7;
            --shadow: 0 1px 2px rgba(20,20,20,.03), 0 10px 26px -16px rgba(20,20,20,.14);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); }

        .topbar {
            background: linear-gradient(90deg, var(--gold), #F2A21F);
            padding: 16px 30px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .topbar-left a {
            color: #fff; text-decoration: none; font-size: 13px; font-weight: 600;
            opacity: .85; display: flex; align-items: center; gap: 5px;
        }
        .topbar-left a:hover { opacity: 1; }
        .topbar-brand { color: #fff; font-weight: 700; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .badge {
            background: rgba(255,255,255,.25); color: #fff;
            font-size: 11px; font-weight: 700; padding: 4px 10px;
            border-radius: 20px; text-transform: capitalize;
        }
        .btn-logout {
            background: #fff; color: var(--gold-deep); border: none;
            padding: 8px 16px; border-radius: 9px;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 12.5px; cursor: pointer;
        }

        .content { padding: 30px; max-width: 640px; margin: 0 auto; }
        .page-title { font-size: 24px; font-weight: 700; margin-bottom: 22px; }

        .alert {
            border-radius: 10px; padding: 11px 14px; font-size: 12.5px; font-weight: 600;
            margin-bottom: 18px;
        }
        .alert-success { background: var(--green-soft); color: var(--green); border: 1px solid #CDEBD9; }
        .alert-error { background: var(--rust-soft); color: var(--rust); border: 1px solid #F5CFC8; }

        .card {
            background: var(--paper); border: 1px solid var(--line); border-radius: 16px;
            padding: 24px; box-shadow: var(--shadow); margin-bottom: 20px;
        }
        .card h2 { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .card .hint { font-size: 12.5px; color: var(--text-faint); margin-bottom: 18px; }

        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 13.5px; }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: var(--text-dim); }
        .info-row .value { font-weight: 600; }

        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-dim); margin-bottom: 6px; }
        .field input, .field textarea {
            width: 100%; padding: 11px 13px; border: 1px solid var(--line); border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 13.5px; color: var(--text);
            background: #FCFCFB; outline: none; transition: .15s;
        }
        .field input:focus, .field textarea:focus {
            border-color: var(--gold); background: #fff;
            box-shadow: 0 0 0 3px rgba(255, 189, 8, .18);
        }
        .field input:disabled, .field textarea:disabled {
            background: var(--line); color: var(--text-faint); cursor: not-allowed;
        }
        .field textarea { resize: vertical; min-height: 70px; }

        .btn-save {
            padding: 11px 22px; border: none; border-radius: 10px;
            background: var(--gold); color: #fff;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 13.5px;
            cursor: pointer; transition: .15s;
        }
        .btn-save:hover { background: var(--gold-deep); }

        .locked-note {
            font-size: 12px; color: var(--text-faint); background: var(--line);
            padding: 10px 12px; border-radius: 9px; margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <span class="topbar-brand">Haoyou Presence</span>
            <a href="{{ route('dashboard') }}">&larr; Kembali ke Beranda</a>
        </div>
        <div class="topbar-right">
            <span class="badge">{{ auth()->user()->role }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">Keluar</button>
            </form>
        </div>
    </div>

    <div class="content">
        <div class="page-title">Profil Saya</div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        {{-- ===== Data Pribadi (selalu read-only) ===== --}}
        <div class="card">
            <h2>Data Pribadi</h2>
            <p class="hint">Data kepegawaian hanya dapat diubah oleh Owner melalui menu Karyawan.</p>

            <div class="info-row">
                <span class="label">Nama Lengkap</span>
                <span class="value">{{ $employee->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Kode Karyawan</span>
                <span class="value">{{ $employee->employee_code }}</span>
            </div>
            <div class="info-row">
                <span class="label">Jabatan</span>
                <span class="value">{{ $employee->position ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Cabang</span>
                <span class="value">{{ $employee->branch->name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Tipe Karyawan</span>
                <span class="value">{{ $employee->employee_type === 'tetap' ? 'Tetap' : 'Part Time' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Tanggal Bergabung</span>
                <span class="value">{{ \Illuminate\Support\Carbon::parse($employee->join_date)->translatedFormat('d F Y') }}</span>
            </div>
        </div>

        {{-- ===== Kontak — editable HANYA untuk Owner ===== --}}
        <div class="card">
            <h2>Kontak</h2>
            <p class="hint">
                @if(auth()->user()->role === 'owner')
                    Perbarui informasi kontak Anda.
                @else
                    Untuk mengubah data kontak, hubungi Owner/HR.
                @endif
            </p>

            @if ($errors->has('phone') || $errors->has('email') || $errors->has('address'))
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('profil.update') }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="phone">Telepon</label>
                    <input id="phone" name="phone" value="{{ old('phone', $employee->phone) }}"
                           @if(auth()->user()->role !== 'owner') disabled @endif>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $employee->email) }}"
                           @if(auth()->user()->role !== 'owner') disabled @endif>
                </div>
                <div class="field">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address"
                              @if(auth()->user()->role !== 'owner') disabled @endif>{{ old('address', $employee->address) }}</textarea>
                </div>

                @if(auth()->user()->role === 'owner')
                    <button type="submit" class="btn-save">Simpan Kontak</button>
                @else
                    <div class="locked-note">Field di atas dikunci untuk role Karyawan.</div>
                @endif
            </form>
        </div>

        {{-- ===== Ganti Password — semua role ===== --}}
        <div class="card">
            <h2>Ganti Password</h2>
            <p class="hint">Gunakan password minimal 8 karakter.</p>

            @if ($errors->has('current_password') || $errors->has('password'))
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('profil.password') }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="current_password">Password Lama</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label for="password">Password Baru</label>
                    <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-save">Ubah Password</button>
            </form>
        </div>
    </div>
</body>
</html>