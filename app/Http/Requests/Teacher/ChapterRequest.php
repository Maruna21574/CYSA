<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('course_id', $this->route('course')->id)],
            'content' => ['nullable', 'string', 'max:500000'],
            'estimated_minutes' => ['nullable', 'integer', 'between:1,600'],
            'requires_previous' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'module_id' => __('modul'),
            'content' => __('obsah'),
            'estimated_minutes' => __('odhadovaný čas'),
        ];
    }
}
