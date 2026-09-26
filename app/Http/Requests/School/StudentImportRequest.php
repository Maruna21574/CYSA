<?php

namespace App\Http\Requests\School;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:1024', 'mimes:csv,txt', 'extensions:csv,txt'],
            'classroom_id' => [
                'nullable', 'integer',
                Rule::exists('classrooms', 'id')->where('school_id', $this->user()->school_id)->whereNull('deleted_at'),
            ],
            'send_invitation' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['file' => __('súbor'), 'classroom_id' => __('trieda')];
    }
}
