<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Siapapun yang login (karyawan/owner) boleh mengajukan izin untuk
        // dirinya sendiri - employee_id diambil dari user login, bukan input.
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'leave_type' => ['required', Rule::in(['sakit', 'cuti_tahunan', 'izin_pribadi', 'dinas_luar'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // maks 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type.required' => 'Jenis izin wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'attachment.mimes' => 'Lampiran harus berupa file JPG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran lampiran maksimal 5MB.',
        ];
    }
}