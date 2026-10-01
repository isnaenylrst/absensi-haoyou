<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = AttendanceSetting::current();
        $branches = Branch::withCount('employees')->orderBy('name')->get();

        return view('owner.pengaturan.edit', compact('settings', 'branches'));
    }

    /**
     * Sekarang HANYA menyimpan toleransi keterlambatan (global, berlaku
     * untuk semua cabang). Data lokasi per-cabang ditangani method
     * storeBranch()/updateBranch() di bawah.
     */
    public function updateLokasi(Request $request)
    {
        $data = $request->validate([
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        AttendanceSetting::current()->update($data);

        return back()->with('status', 'Toleransi keterlambatan berhasil disimpan.');
    }

    public function storeBranch(Request $request)
    {
        $data = $this->validatedBranch($request);
        Branch::create($data);

        return back()->with('status', "Lokasi \"{$data['name']}\" berhasil ditambahkan.");
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        $data = $this->validatedBranch($request);
        $branch->update($data);

        return back()->with('status', "Lokasi \"{$branch->name}\" berhasil diperbarui.");
    }

    public function destroyBranch(Branch $branch)
    {
        if ($branch->employees()->exists()) {
            return back()->withErrors(['error' => "Tidak bisa hapus \"{$branch->name}\" karena masih punya karyawan."]);
        }

        $name = $branch->name;
        $branch->delete();

        return back()->with('status', "Lokasi \"{$name}\" dihapus.");
    }

    public function updateAturan(Request $request)
    {
        $data = $request->validate([
            'late_deduction_per_minute' => ['required', 'numeric', 'min:0'],
            'alpa_deduction_per_day' => ['required', 'numeric', 'min:0'],
            'meal_rate' => ['required', 'numeric', 'min:0'],
            'transport_rate' => ['required', 'numeric', 'min:0'],
            'thr_start_year' => ['required', 'integer', 'min:1', 'max:10'],
            'out_of_radius_policy' => ['required', 'in:ditinjau_manual,ditolak_otomatis'],
            'photo_required' => ['sometimes', 'boolean'],
        ]);

        $data['photo_required'] = $request->boolean('photo_required');

        AttendanceSetting::current()->update($data);

        return back()->with('status', 'Aturan potongan, uang makan, uang bensin & kebijakan THR berhasil disimpan.');
    }

    private function validatedBranch(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'radius_meter' => ['required', 'integer', 'min:10', 'max:5000'],
            'koordinat' => ['required', 'string'],
        ]);

        $coords = array_map('trim', explode(',', $data['koordinat']));

        if (count($coords) !== 2 || ! is_numeric($coords[0]) || ! is_numeric($coords[1])) {
            throw ValidationException::withMessages([
                'koordinat' => 'Format koordinat harus "latitude, longitude", contoh: -7.2891, 112.7381',
            ]);
        }

        return [
            'name' => $data['name'],
            'radius_meter' => $data['radius_meter'],
            'latitude' => $coords[0],
            'longitude' => $coords[1],
        ];
    }
}