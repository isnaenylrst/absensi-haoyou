<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Haoyou Presence</title>
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
            --rust: #D34D3C;
            --rust-soft: #FCEAE7;
            --shadow: 0 1px 2px rgba(20,20,20,.03), 0 20px 40px -20px rgba(20,20,20,.18);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
        }

        .login-wrap { width: 100%; max-width: 400px; padding: 24px; }

        .brand { text-align: center; margin-bottom: 28px; }
        .brand-name { font-size: 22px; font-weight: 700; color: var(--text); }
        .brand-sub { font-size: 10.5px; color: var(--text-faint); letter-spacing: .08em; text-transform: uppercase; margin-top: 3px; }

        .login-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 34px 30px;
            box-shadow: var(--shadow);
        }

        .login-title { font-size: 19px; font-weight: 700; margin-bottom: 4px; }
        .login-desc { font-size: 13px; color: var(--text-dim); margin-bottom: 24px; }

        .alert-error {
            background: var(--rust-soft); color: var(--rust);
            border: 1px solid #F5CFC8; border-radius: 10px;
            padding: 11px 14px; font-size: 12.5px; font-weight: 600;
            margin-bottom: 18px;
        }

        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-dim); margin-bottom: 6px; }
        .field input {
            width: 100%; padding: 12px 14px;
            border: 1px solid var(--line); border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 13.5px; color: var(--text);
            background: #FCFCFB; outline: none; transition: .15s;
        }
        .field input:focus {
            border-color: var(--gold); background: #fff;
            box-shadow: 0 0 0 3px rgba(255, 189, 8, .18);
        }

        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 42px; }
        .toggle-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px;
            color: var(--text-faint); display: flex; align-items: center;
        }
        .toggle-eye:hover { color: var(--text-dim); }
        .toggle-eye svg { width: 18px; height: 18px; }

        .remember-row {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 18px; font-size: 12.5px; color: var(--text-dim);
        }
        .remember-row input[type="checkbox"] {
            width: 15px; height: 15px; accent-color: var(--gold); cursor: pointer;
        }
        .remember-row label { cursor: pointer; }

        .btn-login {
            width: 100%; padding: 13px; border: none; border-radius: 10px;
            background: var(--gold); color: #fff;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 14px;
            cursor: pointer; margin-top: 6px; transition: .15s;
            box-shadow: 0 6px 14px rgba(255, 189, 8, .35);
        }
        .btn-login:hover { background: var(--gold-deep); }

        .login-foot { text-align: center; font-size: 11.5px; color: var(--text-faint); margin-top: 22px; }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="brand">
            <div class="brand-name">Haoyou Presence</div>
            <div class="brand-sub">Sistem Presensi &amp; Payroll</div>
        </div>

        <div class="login-card">
            <div class="login-title">Masuk ke akun Anda</div>
            <div class="login-desc">Gunakan username dan password Anda.</div>

            @if ($errors->any())
                <div class="alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" id="loginForm">
                @csrf
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" autofocus required autocomplete="username">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input id="password" name="password" type="password" required autocomplete="current-password">
                        <button type="button" class="toggle-eye" id="togglePassword" aria-label="Lihat password">
                            <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.62 21.62 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.62 21.62 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <input type="checkbox" id="rememberUsername" name="remember_username">
                    <label for="rememberUsername">Ingat username saya di perangkat ini</label>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>
        </div>

        <div class="login-foot">&copy; {{ date('Y') }} Haoyou Presence. Semua hak dilindungi.</div>
    </div>

    <script>
        // Toggle lihat/sembunyikan password.
        // Kondisi awal: password disembunyikan -> ikon mata TERTUTUP.
        // Setelah diklik: password terlihat -> ikon mata TERBUKA.
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        const eyeOpenPath = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        const eyeClosedPath = '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.62 21.62 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.62 21.62 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';

        toggleBtn.addEventListener('click', () => {
            const willBecomeVisible = passwordInput.type === 'password';
            passwordInput.type = willBecomeVisible ? 'text' : 'password';
            eyeIcon.innerHTML = willBecomeVisible ? eyeOpenPath : eyeClosedPath;
        });

        // Ingat username saja (BUKAN password) — password sengaja diserahkan
        // ke Password Manager bawaan browser, yang jauh lebih aman/terenkripsi
        // dibanding menyimpan password mentah lewat localStorage.
        const usernameInput = document.getElementById('username');
        const rememberCheckbox = document.getElementById('rememberUsername');
        const STORAGE_KEY = 'absenly_remembered_username';

        const savedUsername = localStorage.getItem(STORAGE_KEY);
        if (savedUsername) {
            usernameInput.value = savedUsername;
            rememberCheckbox.checked = true;
            document.getElementById('password').focus();
        }

        document.getElementById('loginForm').addEventListener('submit', () => {
            if (rememberCheckbox.checked) {
                localStorage.setItem(STORAGE_KEY, usernameInput.value);
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        });
    </script>
</body>
</html>