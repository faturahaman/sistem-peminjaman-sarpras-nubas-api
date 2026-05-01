<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'units'      => ['required', 'array', 'min:1'],
            'units.*'    => ['integer', 'exists:units,id'],
            'due_time'   => ['required', 'date', 'after:now'],
            'notes'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'units.required' => 'Minimal satu unit harus dipilih',
            'units.min'      => 'Minimal satu unit harus dipilih',
            'due_time.after' => 'Waktu pengembalian harus di masa depan',
        ];
    }
}
