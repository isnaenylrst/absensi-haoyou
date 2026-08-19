<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with(['branch', 'user'])
            ->when($request->q, fn ($q) => $q->where('full_name', 'like', "%{$request->q}%"))
            ->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->employee_type, fn ($q) => $q->where('employee_type', $request->employee_type))
            ->when($request->status_akun, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('status_akun', $request->status_akun)))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get();

        return view('owner.karyawan.index', compact('employees', 'branches'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        return view('owner.karyawan.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $result = DB::transaction(function () use ($data) {
            $employee = Employee::create([
                ...$data,
                'employee_code' => $this->nextEmployeeCode(),
            ]);

            $username = $this->uniqueUsername($employee->full_name);
            $plainPassword = Str::password(10, symbols: false);

            User::create([
                'employee_id' => $employee->id,
                'username' => $username,
                'password_hash' => Hash::make($plainPassword),
                'role' => 'karyawan',
                'status_akun' => 'aktif',
            ]);

            return ['employee' => $employee, 'username' => $username, 'password' => $plainPassword];
        });

        return redirect()->route('karyawan.index')
            ->with('status', "Karyawan {$result['employee']->full_name} ditambahkan. Username: {$result['username']}")
            ->with('generated_password', $result['password']);
    }

    public function edit(Employee $karyawan)
    {
        $branches = Branch::orderBy('name')->get();
        return view('owner.karyawan.edit', ['employee' => $karyawan, 'branches' => $branches]);
    }

    public function update(Request $request, Employee $karyawan)
    {
        $data = $this->validated($request, $karyawan->id);
        $karyawan->update($data);

        return redirect()->route('karyawan.index')->with('status', 'Data karyawan diperbarui.');
    }

    public function destroy(Employee $karyawan)
    {
        $karyawan->delete();
        return back()->with('status', 'Karyawan dihapus.');
    }

    public function resetPassword(Employee $karyawan)
    {
        $user = $karyawan->user;
        if (! $user) {
            return back()->withErrors(['error' => 'Karyawan ini belum punya akun login.']);
        }

        $plainPassword = Str::password(10, symbols: false);
        $user->forceFill(['password_hash' => Hash::make($plainPassword)])->save();

        return back()
            ->with('status', "Password {$karyawan->full_name} berhasil direset.")
            ->with('generated_password', $plainPassword);
    }

    public function toggleStatus(Employee $karyawan)
    {
        $user = $karyawan->user;
        if (! $user) {
            return back()->withErrors(['error' => 'Karyawan ini belum punya akun login.']);
        }

        $user->status_akun = $user->status_akun === 'aktif' ? 'nonaktif' : 'aktif';
        $user->save();

        return back()->with('status', "Status akun {$karyawan->full_name} diubah menjadi {$user->status_akun}.");
    }

    public function exportCsv(Request $request)
    {
        $employees = Employee::with(['branch', 'user'])
            ->when($request->q, fn ($q) => $q->where('full_name', 'like', "%{$request->q}%"))
            ->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->employee_type, fn ($q) => $q->where('employee_type', $request->employee_type))
            ->when($request->status_akun, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('status_akun', $request->status_akun)))
            ->orderBy('full_name')
            ->get();

        $filename = 'karyawan_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Kode', 'Nama Lengkap', 'Jenis Kelamin', 'Kewarganegaraan', 'Tempat Lahir', 'Tanggal Lahir',
                'Status Pernikahan', 'Agama', 'Golongan Darah', 'Telepon', 'Email', 'Alamat',
                'Cabang', 'Jabatan', 'Tipe Karyawan', 'Tanggal Bergabung', 'Username', 'Status Akun',
            ]);

            foreach ($employees as $emp) {
                fputcsv($handle, [
                    $emp->employee_code,
                    $emp->full_name,
                    $emp->gender,
                    $emp->nationality,
                    $emp->birth_place,
                    optional($emp->birth_date)->format('Y-m-d'),
                    $emp->marital_status,
                    $emp->religion,
                    $emp->blood_type,
                    $emp->phone,
                    $emp->email,
                    $emp->address,
                    $emp->branch->name ?? '',
                    $emp->position,
                    $emp->employee_type === 'tetap' ? 'Tetap' : 'Part Time',
                    optional($emp->join_date)->format('Y-m-d'),
                    $emp->user->username ?? '',
                    $emp->user->status_akun ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        return view('owner.karyawan.import');
    }

    public function downloadTemplate()
    {
        $filename = 'template_import_karyawan.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'full_name', 'branch_name', 'gender', 'religion', 'blood_type',
                'phone', 'email', 'address', 'position', 'employee_type',
                'join_date', 'nik',
            ]);

            fputcsv($handle, [
                'Contoh Nama', 'Haoyou Educator', 'laki-laki', 'Islam', 'O',
                '08123456789', 'contoh@email.com', 'Alamat contoh', 'Teacher', 'tetap',
                '2026-01-01', '3500000000000000',
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        fgetcsv($handle);

        $created = 0;
        $skipped = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count(array_filter($row)) === 0) {
                continue;
            }

            [$fullName, $branchName, $gender, $religion, $bloodType, $phone, $email, $address, $position, $employeeType, $joinDate, $nik] = array_pad($row, 12, null);

            $fullName = trim((string) $fullName);
            $branchName = trim((string) $branchName);
            $employeeType = strtolower(trim((string) $employeeType));
            $joinDate = trim((string) $joinDate);

            if ($fullName === '' || $branchName === '' || $employeeType === '' || $joinDate === '') {
                $skipped[] = "Baris {$rowNum}: kolom wajib (nama/cabang/tipe/tanggal bergabung) kosong.";
                continue;
            }

            if (! in_array($employeeType, ['tetap', 'part_time'], true)) {
                $skipped[] = "Baris {$rowNum}: employee_type harus 'tetap' atau 'part_time', ditemukan '{$employeeType}'.";
                continue;
            }

            $branch = Branch::where('name', $branchName)->first();
            if (! $branch) {
                $skipped[] = "Baris {$rowNum}: cabang '{$branchName}' tidak ditemukan.";
                continue;
            }

            try {
                DB::transaction(function () use ($branch, $fullName, $gender, $religion, $bloodType, $phone, $email, $address, $position, $employeeType, $joinDate, $nik) {
                    $employee = Employee::create([
                        'branch_id' => $branch->id,
                        'employee_code' => $this->nextEmployeeCode(),
                        'full_name' => $fullName,
                        'gender' => $gender ?: null,
                        'religion' => $religion ?: null,
                        'blood_type' => $bloodType ?: null,
                        'phone' => $phone ?: null,
                        'email' => $email ?: null,
                        'address' => $address ?: null,
                        'position' => $position ?: null,
                        'employee_type' => $employeeType,
                        'join_date' => $joinDate,
                        'nik' => $nik ?: null,
                    ]);

                    $username = $this->uniqueUsername($employee->full_name);

                    User::create([
                        'employee_id' => $employee->id,
                        'username' => $username,
                        'password_hash' => Hash::make(Str::password(10, symbols: false)),
                        'role' => 'karyawan',
                        'status_akun' => 'aktif',
                    ]);
                });

                $created++;
            } catch (\Throwable $e) {
                $skipped[] = "Baris {$rowNum} ({$fullName}): gagal disimpan — " . $e->getMessage();
            }
        }

        fclose($handle);

        return redirect()->route('karyawan.index')
            ->with('status', "Import selesai: {$created} karyawan berhasil ditambahkan." . (count($skipped) ? ' ' . count($skipped) . ' baris dilewati.' : ''))
            ->with('import_skipped', $skipped);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['nullable', 'string', 'max:20'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'string', 'max:50'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'in:belum_menikah,menikah,cerai'],
            'last_education' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150', 'unique:employees,email' . ($ignoreId ? ",{$ignoreId}" : '')],
            'address' => ['nullable', 'string'],
            'position' => ['nullable', 'string', 'max:100'],
            'employee_type' => ['required', 'in:tetap,part_time'],
            'join_date' => ['required', 'date'],
            'nik' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function nextEmployeeCode(): string
    {
        $last = Employee::orderByDesc('id')->value('id') ?? 0;
        $next = $last + 1;

        while (Employee::where('employee_code', 'EMP' . str_pad($next, 4, '0', STR_PAD_LEFT))->exists()) {
            $next++;
        }

        return 'EMP' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function uniqueUsername(string $fullName): string
    {
        $base = Str::slug(explode(' ', trim($fullName))[0]);
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }
        return $username;
    }
}