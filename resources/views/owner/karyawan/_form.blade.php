@push('styles')
<style>
    .form-card { background: #fff; border: 1px solid #EDEEF0; border-radius: 14px; padding: 24px; max-width: 760px; }
    .form-section-title {
        font-size: 12.5px; font-weight: 700; color: #DE8C0F; text-transform: uppercase;
        letter-spacing: .04em; margin: 22px 0 12px; padding-top: 14px; border-top: 1px solid #F1F2F3;
    }
    .form-section-title:first-child { margin-top: 0; padding-top: 0; border-top: none; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .field { margin-bottom: 16px; }
    .field label { display: block; font-size: 12.5px; font-weight: 600; color: #6B7280; margin-bottom: 6px; }
    .field input, .field select, .field textarea {
        width: 100%; padding: 11px 13px; border: 1px solid #EDEEF0; border-radius: 10px;
        font-family: 'Poppins', sans-serif; font-size: 13.5px; outline: none;
    }
    .field input:focus, .field select:focus, .field textarea:focus {
        border-color: #ffbd08; box-shadow: 0 0 0 3px rgba(255,189,8,.18);
    }
    .hint { font-size: 12px; color: #9AA0A8; margin: 12px 0 18px; }
    .btn-gold {
        background: #ffbd08; color: #fff; border: none; padding: 11px 22px;
        border-radius: 10px; font-family: 'Poppins', sans-serif; font-weight: 700;
        font-size: 13.5px; cursor: pointer; margin-top: 8px;
    }
    .btn-gold:hover { background: #DE8C0F; }
</style>
@endpush

<div class="form-section-title">Data Pribadi</div>

<div class="form-row">
    <div class="field">
        <label>Nama Lengkap</label>
        <input name="full_name" value="{{ old('full_name', $employee->full_name ?? '') }}" required>
    </div>
    <div class="field">
        <label>NIK</label>
        <input name="nik" value="{{ old('nik', $employee->nik ?? '') }}">
    </div>
</div>

<div class="form-row-3">
    <div class="field">
        <label>Jenis Kelamin</label>
        <select name="gender">
            <option value="">-- Pilih --</option>
            <option value="laki-laki" @selected(old('gender', $employee->gender ?? '')==='laki-laki')>Laki-laki</option>
            <option value="perempuan" @selected(old('gender', $employee->gender ?? '')==='perempuan')>Perempuan</option>
        </select>
    </div>
    <div class="field">
        <label>Kewarganegaraan</label>
        <input name="nationality" value="{{ old('nationality', $employee->nationality ?? 'Indonesia') }}">
    </div>
    <div class="field">
        <label>Status Pernikahan</label>
        <select name="marital_status">
            <option value="">-- Pilih --</option>
            <option value="belum_menikah" @selected(old('marital_status', $employee->marital_status ?? '')==='belum_menikah')>Belum Menikah</option>
            <option value="menikah" @selected(old('marital_status', $employee->marital_status ?? '')==='menikah')>Menikah</option>
            <option value="cerai" @selected(old('marital_status', $employee->marital_status ?? '')==='cerai')>Cerai</option>
        </select>
    </div>
</div>

<div class="form-row-3">
    <div class="field">
        <label>Tempat Lahir</label>
        <input name="birth_place" value="{{ old('birth_place', $employee->birth_place ?? '') }}">
    </div>
    <div class="field">
        <label>Tanggal Lahir</label>
        <input type="date" name="birth_date" value="{{ old('birth_date', optional($employee->birth_date ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="field">
        <label>Golongan Darah</label>
        <select name="blood_type">
            <option value="">-- Pilih --</option>
            @foreach(['A','B','AB','O'] as $bt)
                <option value="{{ $bt }}" @selected(old('blood_type', $employee->blood_type ?? '')===$bt)>{{ $bt }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-row">
    <div class="field">
        <label>Agama</label>
        <select name="religion">
            <option value="">-- Pilih --</option>
            @foreach(['Islam','Kristen','Kristen Protestan','Katolik','Hindu','Buddha','Konghucu'] as $r)
                <option value="{{ $r }}" @selected(old('religion', $employee->religion ?? '')===$r)>{{ $r }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Pendidikan Terakhir</label>
        <input name="last_education" value="{{ old('last_education', $employee->last_education ?? '') }}">
    </div>
</div>

<div class="form-section-title">Kontak</div>

<div class="form-row">
    <div class="field">
        <label>Telepon</label>
        <input name="phone" value="{{ old('phone', $employee->phone ?? '') }}">
    </div>
    <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $employee->email ?? '') }}">
    </div>
</div>

<div class="field">
    <label>Alamat</label>
    <textarea name="address" rows="2">{{ old('address', $employee->address ?? '') }}</textarea>
</div>

<div class="form-section-title">Kepegawaian</div>

<div class="form-row">
    <div class="field">
        <label>Cabang</label>
        <select name="branch_id" required>
            <option value="">-- Pilih Cabang --</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id', $employee->branch_id ?? null) == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Jabatan</label>
        <input name="position" value="{{ old('position', $employee->position ?? '') }}">
    </div>
</div>

<div class="form-row">
    <div class="field">
        <label>Tipe Karyawan</label>
        <select name="employee_type" required>
            <option value="tetap" @selected(old('employee_type', $employee->employee_type ?? '')==='tetap')>Tetap</option>
            <option value="part_time" @selected(old('employee_type', $employee->employee_type ?? '')==='part_time')>Part Time</option>
        </select>
    </div>
    <div class="field">
        <label>Tanggal Bergabung</label>
        <input type="date" name="join_date" value="{{ old('join_date', optional($employee->join_date ?? null)->format('Y-m-d') ?? date('Y-m-d')) }}" required>
    </div>
</div>

@if(! isset($employee))
    <p class="hint">Akun login (username + password sementara) akan dibuat otomatis setelah karyawan disimpan.</p>
@endif

<button type="submit" class="btn-gold">{{ isset($employee) ? 'Simpan Perubahan' : 'Simpan Karyawan' }}</button>