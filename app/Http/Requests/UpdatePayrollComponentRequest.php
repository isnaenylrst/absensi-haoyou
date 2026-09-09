<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        /*
        |--------------------------------------------------------------------------
        | KARYAWAN PART TIME
        |--------------------------------------------------------------------------
        | Part time hanya membutuhkan:
        |
        | - Fee Mengajar
        | - Uang Makan
        | - Uang Bensin
        |
        | Tidak membutuhkan:
        | - Hourly Rate
        | - Bonus
        | - THR
        */
        if (
            $this->route('employee')
            && $this->route('employee')->employee_type === 'part_time'
        ) {
            return [
                'base_salary' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'meal_rate' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'transport_rate' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | KARYAWAN TETAP
        |--------------------------------------------------------------------------
        | Karyawan tetap tetap menggunakan komponen lengkap.
        */
        return [
            'base_salary' => [
                'required',
                'numeric',
                'min:0',
            ],

            'meal_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'transport_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'hourly_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'allowance' => [
                'required',
                'numeric',
                'min:0',
            ],

            'thr_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}