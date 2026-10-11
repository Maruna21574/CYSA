<?php

namespace App\Http\Requests\Admin;

use App\Enums\SchoolType;
use App\Http\Controllers\Admin\SchoolController;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School
            ? $this->user()->can('update', $school)
            : $this->user()->can('create', School::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name')))]);

        // In the organizations section the type is always "company" (the form has no type field).
        if (SchoolController::inCompanySection()) {
            $this->merge(['type' => SchoolType::Company->value]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('schools', 'slug')->ignore($this->route('school'))],
            'type' => ['required', Rule::enum(SchoolType::class)->only(SchoolController::inCompanySection() ? [SchoolType::Company] : SchoolType::schoolTypes())],
            'city' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => SchoolController::inCompanySection() ? __('názov organizácie') : __('názov školy'),
            'slug' => __('skratka v URL'),
            'type' => __('typ školy'),
            'city' => __('mesto'),
        ];
    }
}
