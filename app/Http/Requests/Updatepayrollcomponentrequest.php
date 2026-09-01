<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'base_salary' => ['required', 'numeric', 'min:0'],
            'meal_rate' => ['required', 'numeric', 'min:0'],
            'transport_rate' => ['required', 'numeric', 'min:0'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'allowance' => ['required', 'numeric', 'min:0'],
            'thr_active' => ['nullable', 'boolean'],
        ];
    }
}