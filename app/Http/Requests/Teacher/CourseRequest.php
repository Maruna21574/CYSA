<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Difficulty;
use App\Models\Course;
use App\Rules\AllowedMaterialFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course
            ? $this->user()->can('update', $course)
            : $this->user()->can('create', Course::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->whereNull('school_id')
                    ->orWhere('school_id', $this->user()->school_id)),
            ],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'sequential_chapters' => ['boolean'],
            'cover' => ['nullable', 'max:5120', AllowedMaterialFile::imagesOnly()],
            'remove_cover' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => __('kategória'),
            'difficulty' => __('obtiažnosť'),
            'cover' => __('obrázok kurzu'),
        ];
    }
}
