<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = AttendanceSetting::current();
        $branch = Branch::first();

        return view('owner.pengaturan.edit', compact('settings', 'branch'));
    }

    public function updateLokasi(Request $request)
    {
        $data = $request->validate([
            'branch_name' => ['required', 'string', 'max:150'],
            'radius_meter' => ['required', 'integer', 'min:10', 'max:5000'],
            'koordinat' => ['required', 'string'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        $coords = array_map('trim', explode(',', $data['koordinat']));

        if (count($coords) !== 2 || ! is_numeric($coords[0]) || ! is_numeric($coords[1])) {
            return back()->withErrors(['koordinat' => 'Format koordinat harus "latitude, longitude", contoh: -7.2891, 112.7381'])->withInput();
        }

        [$lat, $lng] = $coords;

        DB::transaction(function () use ($data, $lat, $lng) {
            $branch = Branch::firstOrNew();
            $branch->name = $data['branch_name'];
            $branch->latitude = $lat;
            $branch->longitude = $lng;
            $branch->radius_meter = $data['radius_meter'];
            $branch->save();

            AttendanceSetting::current()->update([
                'late_tolerance_minutes' => $data['late_tolerance_minutes'],
            ]);
        });

        return back()->with('status', 'Titik & radius kantor berhasil disimpan.');
    }

    public function updateAturan(Request $request)
{
    $data = $request->validate([
        'late_deduction_per_minute' => [
            'required',
            'numeric',
            'min:0'
        ],

        'alpa_deduction_per_day' => [
            'required',
            'numeric',
            'min:0'
        ],

        'meal_rate' => [
            'required',
            'numeric',
            'min:0'
        ],

        'transport_rate' => [
            'required',
            'numeric',
            'min:0'
        ],

        'thr_start_year' => [
            'required',
            'integer',
            'min:1',
            'max:10'
        ],

        'out_of_radius_policy' => [
            'required',
            'in:ditinjau_manual,ditolak_otomatis'
        ],

        'photo_required' => [
            'sometimes',
            'boolean'
        ],
    ]);

    $data['photo_required'] = $request->boolean('photo_required');

    AttendanceSetting::current()->update($data);

    return back()->with(
        'status',
        'Aturan potongan, uang makan, uang bensin & kebijakan THR berhasil disimpan.'
    );
}
}