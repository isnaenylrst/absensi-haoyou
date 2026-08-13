<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — HAOYOU PRESENCE</title>
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
            --line: #EDEEF0;
            --shadow: 0 1px 2px rgba(20,20,20,.03), 0 10px 26px -16px rgba(20,20,20,.14);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .topbar {
            background: linear-gradient(90deg, var(--gold), #F2A21F);
            padding: 16px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-left { color: #fff; font-weight: 700; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .badge {
            background: rgba(255,255,255,.25);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: capitalize;
        }
        .btn-logout {
            background: #fff;
            color: var(--gold-deep);
            border: none;
            padding: 8px 16px;
            border-radius: 9px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 12.5px;
            cursor: pointer;
        }
        .content { padding: 30px; }
        .card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            max-width: 420px;
        }
        .card h2 { font-size: 18px; margin-bottom: 6px; }
        .card p { font-size: 13.5px; color: var(--text-dim); margin-top: 4px; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">Haoyou Presence</div>
        <div class="topbar-right">
            <span class="badge">{{ $user->role }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">Keluar</button>
            </form>
        </div>
    </div>

    <div class="content">
        <div class="card">
            <h2>Selamat datang, {{ $user->employee->full_name }}</h2>
            <p>Login sebagai <b>{{ $user->username }}</b> ({{ $user->role }})</p>
            <p>Halaman Beranda lengkap akan dibangun di bagian Presensi/Payroll.</p>
        </div>
    </div>
</body>
</html>