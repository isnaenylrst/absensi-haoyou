<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\ClientVisit;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClientVisitController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $visits = ClientVisit::where('employee_id', $user->employee_id)
            ->orderByDesc('visited_at')
            ->paginate(9)
            ->withQueryString();

        return view('karyawan.clientvisit', compact('visits'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'visit_type' => ['required', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['required', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'photo.required' => 'Foto lokasi wajib diambil sebelum menyimpan kunjungan.',
            'client_name.required' => 'Nama klien / lokasi wajib diisi.',
            'address.required' => 'Alamat wajib diisi (otomatis atau manual).',
        ]);

        $photoPath = $this->storePhoto($request->file('photo'), $user->employee_id);

        ClientVisit::create([
            'employee_id' => $user->employee_id,
            'client_name' => $data['client_name'],
            'address' => $data['address'],
            'visit_type' => $data['visit_type'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy_m' => $data['accuracy_m'] ?? null,
            'photo_url' => $photoPath,
            'notes' => $data['notes'] ?? null,
            'visited_at' => now(),
            'review_status' => $this->determineReviewStatus(
                $data['accuracy_m'] ?? null,
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
            ),
        ]);

        return redirect()
            ->route('kunjungan-klien-saya')
            ->with('success', 'Kunjungan klien berhasil disimpan.');
    }

    private function determineReviewStatus(?float $accuracyM, ?float $lat, ?float $lng): string
    {
        $maxAccuracy = config('attendance.client_visit.max_accuracy_m', 50);

        if (is_null($lat) || is_null($lng) || is_null($accuracyM)) {
            return 'perlu_ditinjau';
        }

        if ($accuracyM > $maxAccuracy) {
            return 'perlu_ditinjau';
        }

        return 'wajar';
    }

    private function storePhoto($file, int $employeeId): string
    {
        $filename = now()->format('Y-m-d_His') . '_' . str()->random(6) . '.' . $file->extension();

        Storage::disk('public')->putFileAs(
            "client-visit-photos/{$employeeId}",
            $file,
            $filename
        );

        return "client-visit-photos/{$employeeId}/{$filename}";
    }
}