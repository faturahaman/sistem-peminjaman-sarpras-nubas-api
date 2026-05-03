<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $classId = $this->route('class')?->id;

        return [
            'grade' => [
                'sometimes',
                'integer',
                'in:10,11,12,13',
                \Illuminate\Validation\Rule::unique('classes')
                    ->ignore($classId)
                    ->where(function ($query) {
                        return $query
                            ->where('major', $this->input('major'))
                            ->where('rombel', $this->input('rombel'));
                    }),
            ],
            'major'  => ['sometimes', 'string', 'max:100'],
            'rombel' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'grade.in'     => 'Tingkat kelas harus 10, 11, 12, atau 13.',
            'grade.unique' => 'Kombinasi kelas, jurusan, dan rombel sudah ada.',
            'rombel.min'   => 'Rombel minimal bernilai 1.',
        ];
    }
}
