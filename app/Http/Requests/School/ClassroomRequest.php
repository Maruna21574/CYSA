<?php

namespace App\Http\Requests\School;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classroom = $this->route('classroom');

        return $classroom instanceof Classroom
            ? $this->user()->can('update', $classroom)
            : $this->user()->can('create', Classroom::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'school_year' => str_replace(' ', '', (string) $this->input('school_year')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('classrooms', 'name')
                    ->where('school_id', $this->user()->school_id)
                    ->where('school_year', $this->input('school_year'))
                    ->ignore($this->route('classroom')),
            ],
            'grade_level' => ['nullable', 'integer', 'between:1,13'],
            'school_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('school_year')) {
                    return;
                }

                [$start, $end] = array_map('intval', explode('/', $this->input('school_year')));

                if ($end !== $start + 1) {
                    $validator->errors()->add('school_year', __('Školský rok musí byť v tvare 2026/2027.'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => __('názov triedy'), 'grade_level' => __('ročník'), 'school_year' => __('školský rok')];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => __('Trieda s týmto názvom v danom školskom roku už existuje.')];
    }
}
