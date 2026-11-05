@extends('owner.dashboard')

@section('title', 'Detail Riwayat | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/jadwal-kerja.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .rd-actions { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin:14px 0 18px; }
        .rd-actions .rd-spacer { flex:1; }
        .rd-select { padding:8px 12px; font:inherit; font-size:13px; color:inherit; background:transparent; border:1px solid rgba(128,128,128,.3); border-radius:10px; }
        .rd-summary { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        @media (max-width:900px) { .rd-summary { grid-template-columns:repeat(2, minmax(0,1fr)); } }
        /* Hari Minggu / tanggal merah */
        .jadwal-approval-table tr.row-libur td { background:rgba(220,38,38,.1); color:#dc2626; }
    </style>
@endpush

@section('content')

    <div class="crumb">
      Home <span>›</span> Kehadiran <span>›</span>
      <a href="{{ route('jadwal-kerja', ['bulan' => $bulanTerpilih, 'tahun' => $tahunTerpilih]) }}">Jadwal Kerja</a>
      <span>›</span> <b>Detail Riwayat</b>
    </div>
    <div class="page-head"><div class="page-title">{{ $r->nama }}</div></div>

    <div class="quota-chip">
      {{ $r->cabang }} &middot; {{ ucfirst($r->tipe) }} &middot;
      periode <b>{{ $daftarBulan[$bulanTerpilih] }} {{ $tahunTerpilih }}</b>
    </div>

    <div class="rd-actions">
      <a href="{{ route('jadwal-kerja', ['bulan' => $bulanTerpilih, 'tahun' => $tahunTerpilih]) }}" class="btn btn-line btn-sm">
        <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>Kembali
      </a>

      <span class="rd-spacer"></span>

      <form method="GET" action="{{ route('owner.riwayat.detail', $r->id) }}" id="rdFilterForm" style="display:flex; gap:8px;">
        <select name="bulan" class="rd-select">
          @foreach ($daftarBulan as $angka => $nama)
            <option value="{{ $angka }}" @selected($bulanTerpilih === $angka)>{{ $nama }}</option>
          @endforeach
        </select>
        <select name="tahun" class="rd-select">
          @foreach ($daftarTahun as $tahunOpt)
            <option value="{{ $tahunOpt }}" @selected($tahunTerpilih === $tahunOpt)>{{ $tahunOpt }}</option>
          @endforeach
        </select>
      </form>
    </div>

    <div class="card" style="margin-bottom:18px; padding:14px 18px;">
      <div style="font-size:12px; font-weight:600; opacity:.7; margin-bottom:8px;">Tanggal merah {{ $daftarBulan[$bulanTerpilih] }} {{ $tahunTerpilih }}</div>
      @if ($tanggalMerah->isEmpty())
        <div style="font-size:13px; opacity:.7;">Tidak ada tanggal merah tersimpan untuk bulan ini.</div>
      @else
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
          @foreach ($tanggalMerah as $libur)
            <span style="padding:6px 12px; font-size:12px; font-weight:600; border-radius:999px; background:rgba(220,38,38,.1); color:#dc2626;">
              {{ $libur->tanggal->locale('id')->translatedFormat('d M') }} &middot; {{ $libur->keterangan }}
            </span>
          @endforeach
        </div>
      @endif
    </div>

    <div class="rd-summary">
      <div class="pb-summary-chip ok"><div class="pb-num">{{ $r->hadir }}</div><div class="pb-label">Hadir</div></div>
      <div class="pb-summary-chip"><div class="pb-num">{{ $r->telat }}</div><div class="pb-label">Telat</div></div>
      <div class="pb-summary-chip"><div class="pb-num">{{ $r->tidak_checkout }}</div><div class="pb-label">Tidak Checkout</div></div>
      <div class="pb-summary-chip cuti"><div class="pb-num">{{ $r->cuti }}</div><div class="pb-label">Cuti</div></div>
      <div class="pb-summary-chip"><div class="pb-num">{{ $r->alpa }}</div><div class="pb-label">Alpa</div></div>
    </div>

    <div class="card">
      <div class="table-wrap">
        <table class="jadwal-approval-table">
          <tr>
            <th>Tanggal</th>
            <th>Jadwal</th>
            <th>Masuk</th>
            <th>Keluar</th>
            <th>Status</th>
          </tr>
          @forelse ($r->detail as $d)
            <tr class="{{ $d->libur ? 'row-libur' : '' }}">
              <td class="mono" {!! $d->libur ? 'style="background:rgba(220,38,38,.1);color:#dc2626;"' : '' !!}>{{ $d->tanggal->locale('id')->translatedFormat('l, d M Y') }}</td>
              <td {!! $d->libur ? 'style="background:rgba(220,38,38,.1);color:#dc2626;"' : '' !!}>{{ $d->jadwal }}</td>
              <td class="mono" {!! $d->libur ? 'style="background:rgba(220,38,38,.1);color:#dc2626;"' : '' !!}>{{ $d->jam_masuk ? \Carbon\Carbon::parse($d->jam_masuk)->format('H:i') : '—' }}</td>
              <td class="mono" {!! $d->libur ? 'style="background:rgba(220,38,38,.1);color:#dc2626;"' : '' !!}>{{ $d->jam_keluar ? \Carbon\Carbon::parse($d->jam_keluar)->format('H:i') : '—' }}</td>
              <td {!! $d->libur ? 'style="background:rgba(220,38,38,.1);color:#dc2626;"' : '' !!}>
                {{ $d->status ? ucfirst(str_replace('_', ' ', $d->status)) : 'Libur' }}
                @if($d->libur) &middot; {{ $d->libur }} @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="empty-state">Belum ada record presensi bulan ini.</td></tr>
          @endforelse
        </table>
      </div>
    </div>

<script>
  const rdFilterForm = document.getElementById('rdFilterForm');
  rdFilterForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', () => rdFilterForm.requestSubmit());
  });
</script>

@endsection