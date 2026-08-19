@extends('owner.dashboard')

@section('title', 'Kunjungan Klien | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/owner/kunjungan-klien.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css">
    <style>
        .addr-cell {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')

      <!-- ============ KUNJUNGAN KLIEN (Owner only) ============ -->
        <div class="crumb">Home <span>&rsaquo;</span> Kehadiran <span>&rsaquo;</span> <b>Kunjungan Klien</b></div>
        <div class="page-head"><div class="page-title">Kunjungan Klien</div></div>

        <form method="GET" action="{{ route('kunjungan-klien') }}" class="toolbar">
          <div class="toolbar-left">
            <div class="search-box">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9AA0A8" stroke-width="2">
                <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
              </svg>
              <input type="text" name="q" placeholder="Cari nama klien / karyawan..." value="{{ $filters['q'] ?? '' }}">
            </div>

            <input type="date" name="tanggal" class="field-input-inline"
                   value="{{ $filters['tanggal'] ?? '' }}">

            <select name="branch_id" class="field-input-inline">
              <option value="">Semua Cabang</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
                  {{ $branch->name }}
                </option>
              @endforeach
            </select>

            <select name="visit_type" class="field-input-inline">
              <option value="">Semua Jenis</option>
              @foreach ($visitTypes as $type)
                <option value="{{ $type }}" @selected(($filters['visit_type'] ?? null) === $type)>
                  {{ $type }}
                </option>
              @endforeach
            </select>

            <select name="review_status" class="field-input-inline">
              <option value="">Semua Status</option>
              <option value="wajar" @selected(($filters['review_status'] ?? null) === 'wajar')>Wajar</option>
              <option value="perlu_ditinjau" @selected(($filters['review_status'] ?? null) === 'perlu_ditinjau')>Perlu Ditinjau</option>
            </select>

            <button type="submit" class="btn btn-line btn-sm">Terapkan</button>
            @if(!empty(array_filter($filters ?? [])))
              <a href="{{ route('kunjungan-klien') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
          </div>

          <div class="quota-chip">
            Menampilkan <b>{{ $visits->count() }}</b> dari <b>{{ $visits->total() }}</b> data
          </div>
        </form>

        <div class="card">
          <div class="card-head">
            <div>
              <div class="card-title">Riwayat Kunjungan Karyawan</div>
              <div class="card-sub">Log seluruh kunjungan klien oleh tim lapangan &amp; sales</div>
            </div>
          </div>

          <div class="table-wrap">
            <table>
              <tr>
                <th>Karyawan</th>
                <th>Klien / Lokasi</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Alamat yang Dikunjungi</th>
                {{-- <th>Foto</th> --}}
                <th>Status</th>
                <th>Aksi</th>
              </tr>

              @forelse ($visits as $visit)
                @php
                  $employee = $visit->employee;
                  $isReview = $visit->review_status === 'perlu_ditinjau';

                  $initials = $employee->initials();
                  $avatarColors = ['#8B5CF6', '#2E6FDB', '#E8863A', '#D34D9C', '#2F8A5B', '#D34D3C'];
                  $avatarColor = $avatarColors[$employee->id % count($avatarColors)];
                @endphp
                <tr>
                  <td class="row-name">
                    <div class="avatar-dot" style="background:{{ $avatarColor }};">{{ $initials }}</div>
                    {{ $employee->full_name }}
                  </td>
                  <td>{{ $visit->client_name }}</td>
                  <td><span class="badge badge-gray">{{ $visit->visit_type }}</span></td>
                  <td class="mono">{{ $visit->visited_at->translatedFormat('d M, H:i') }}</td>
                  <td class="addr-cell" title="{{ $visit->address }}">
                    {{ \Illuminate\Support\Str::limit($visit->address, 40) }}
                  </td>
                  {{-- <td>
                    @if ($visit->photo_url)
                      <div class="photo-thumb" onclick="openKunjunganModal({{ $visit->id }})">
                        <img src="{{ $visit->photo_url }}" alt="Foto kunjungan {{ $visit->client_name }}"
                             onerror="this.closest('.photo-thumb').classList.add('photo-error')">
                      </div>
                    @else
                      <span class="badge badge-rust">Tidak Ada</span>
                    @endif
                  </td> --}}
                  <td>
                    @if ($isReview)
                      <span class="badge badge-rust">&#9888; Perlu Ditinjau</span>
                    @else
                      <span class="badge badge-green">&#10003; Wajar</span>
                    @endif
                  </td>
                  <td>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openKunjunganModal({{ $visit->id }})">
                      Detail
                    </button>
                  </td>
                </tr>

                <template id="modal-data-{{ $visit->id }}">
                    <button type="button" class="modal-close-btn" onclick="closeKunjunganModal()">
                        <i class="ti ti-x"></i>
                    </button>

                    <div class="modal-photo-panel">
                        @if ($visit->photo_url)
                        <img src="{{ $visit->photo_url }}" alt="Foto kunjungan"
                            onerror="this.closest('.modal-photo-panel').innerHTML = '<div class=&quot;modal-photo-empty&quot;><div class=&quot;modal-photo-empty-inner&quot;><i class=&quot;ti ti-photo-off&quot; style=&quot;font-size:26px;&quot;></i>Foto gagal dimuat</div></div>' + this.closest('.modal-photo-panel').querySelector('.modal-photo-type').outerHTML">
                        @else
                        <div class="modal-photo-empty">
                            <div class="modal-photo-empty-inner">
                            <i class="ti ti-photo" style="font-size:26px;"></i>
                            Tidak ada foto
                            </div>
                        </div>
                        @endif
                        <span class="modal-photo-type">{{ $visit->visit_type }}</span>
                    </div>

                    <div class="modal-content">
                        <div class="modal-content-head">
                        <div class="modal-employee-block">
                            <div class="avatar-dot" style="background:{{ $avatarColor }}; width:44px; height:44px; font-size:14px; border-radius:12px;">{{ $initials }}</div>
                            <div>
                            <div class="modal-employee-name">{{ $employee->full_name }}</div>
                            <div class="modal-employee-sub">{{ $employee->position ?? '-' }} &middot; {{ $employee->branch?->name ?? '-' }}</div>
                            </div>
                        </div>
                        </div>

                        <div class="modal-status-row" data-visit-id="{{ $visit->id }}">
                        @if ($isReview)
                            <span class="modal-status-pill review"><span class="modal-status-dot"></span> Perlu Ditinjau</span>
                        @else
                            <span class="modal-status-pill wajar"><span class="modal-status-dot"></span> Wajar</span>
                        @endif
                        </div>

                        <div class="modal-meta-row">
                        <span class="modal-meta-chip">Akurasi {{ $visit->accuracy_m !== null ? number_format($visit->accuracy_m, 0) . ' m' : '—' }}</span>
                        <span class="modal-meta-chip">{{ $visit->visited_at->translatedFormat('d M, H:i') }}</span>
                        </div>

                        <div class="modal-block">
                        <div class="modal-block-label">Klien / lokasi</div>
                        <p class="modal-block-title">{{ $visit->client_name }}</p>
                        <p>{{ $visit->address }}</p>
                        </div>

                        @if ($visit->notes)
                        <div class="modal-block notes">
                            <div class="modal-block-label">Catatan</div>
                            <p>{{ $visit->notes }}</p>
                        </div>
                        @endif

                        <div class="modal-actions">
                        @if ($visit->latitude && $visit->longitude)
                            <a href="https://www.google.com/maps?q={{ $visit->latitude }},{{ $visit->longitude }}" target="_blank" class="modal-action-btn line">
                            <i class="ti ti-map-pin" style="font-size:15px;"></i> Lihat peta
                            </a>
                        @else
                            <button type="button" class="modal-action-btn line" disabled>
                            <i class="ti ti-map-pin" style="font-size:15px;"></i> Lihat peta
                            </button>
                        @endif

                        <button type="button" class="modal-action-btn gold" data-toggle-status
                                data-current="{{ $isReview ? 'perlu_ditinjau' : 'wajar' }}">
                            @if ($isReview)
                            <i class="ti ti-check" style="font-size:15px;"></i> Tandai wajar
                            @else
                            <i class="ti ti-alert-triangle" style="font-size:15px;"></i> Tandai perlu ditinjau
                            @endif
                        </button>
                        </div>
                    </div>
                    </template>
              @empty
                <tr>
                  <td colspan="8" class="empty-state">Tidak ada data kunjungan klien untuk filter ini.</td>
                </tr>
              @endforelse
            </table>
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

        <!-- ===== MODAL DETAIL =====
             PENTING: sebelumnya ada wrapper .modal-head + .modal-body di sini.
             Itu bikin struktur .modal-box jadi 3 level (head + body) padahal
             CSS mengharapkan .modal-box langsung berisi .modal-photo-panel +
             .modal-content sebagai sibling (flex row). Makanya modal-box
             langsung dijadikan target innerHTML di bawah ini. -->
        <div class="modal-overlay" id="kunjunganModalOverlay" onclick="closeKunjunganModal(event)">
          <div class="modal-box" id="kunjunganModalBody" onclick="event.stopPropagation()"></div>
        </div>


<script>
  // ===== Modal detail kunjungan =====
  function openKunjunganModal(id) {
    const source = document.getElementById('modal-data-' + id);
    if (!source) return;
    document.getElementById('kunjunganModalBody').innerHTML = source.innerHTML;
    document.getElementById('kunjunganModalOverlay').classList.add('show');
  }
  function closeKunjunganModal(e) {
    document.getElementById('kunjunganModalOverlay').classList.remove('show');
  }

  // ===== Toast notifikasi =====
  let toastTimer;
  function showToast(msg){
    let toast = document.getElementById('appToast');
    if(!toast){
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

  // ===== Status selector di modal =====
  document.getElementById('kunjunganModalBody').addEventListener('click', function (e) {
    const btn = e.target.closest('[data-toggle-status]');
    if (!btn) return;

    const modalRoot = document.getElementById('kunjunganModalBody');
    const visitId = modalRoot.querySelector('.modal-status-row').dataset.visitId;
    const current = btn.dataset.current;
    const newStatus = current === 'wajar' ? 'perlu_ditinjau' : 'wajar';

    btn.classList.add('saving');

    fetch(`/kunjungan-klien/${visitId}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ review_status: newStatus })
    })
      .then(res => { if (!res.ok) throw new Error(); return res.json(); })
      .then(() => {
        btn.dataset.current = newStatus;
        btn.classList.remove('saving');

        const pillWrap = modalRoot.querySelector('.modal-status-row');
        pillWrap.innerHTML = newStatus === 'wajar'
          ? '<span class="modal-status-pill wajar"><span class="modal-status-dot"></span> Wajar</span>'
          : '<span class="modal-status-pill review"><span class="modal-status-dot"></span> Perlu Ditinjau</span>';

        btn.innerHTML = newStatus === 'wajar'
          ? '<i class="ti ti-alert-triangle" style="font-size:15px;"></i> Tandai perlu ditinjau'
          : '<i class="ti ti-check" style="font-size:15px;"></i> Tandai wajar';

        const rowBadge = document.querySelector(`[onclick="openKunjunganModal(${visitId})"]`)
          ?.closest('tr')?.querySelector('td:nth-last-child(2) .badge');
        if (rowBadge) {
          rowBadge.className = 'badge ' + (newStatus === 'wajar' ? 'badge-green' : 'badge-rust');
          rowBadge.innerHTML = newStatus === 'wajar' ? '&#10003; Wajar' : '&#9888; Perlu Ditinjau';
        }

        showToast('Status kunjungan diperbarui');
      })
      .catch(() => {
        btn.classList.remove('saving');
        showToast('Gagal memperbarui status');
      });
  });
</script>

@endsection