<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Create and update of a user account. A school admin can only work inside their own school
 * and only with the roles UserRole::assignableBy() allows.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->targetUser();

        return $target
            ? $this->user()->can('update', $target)
            : $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $actor = $this->user();
        $assignable = UserRole::assignableBy($actor->role);

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->targetUser())],
            'role' => ['required', Rule::enum(UserRole::class)->only($assignable)],
            'school_id' => $actor->isSuperAdmin()
                ? [Rule::requiredIf(fn (): bool => $this->input('role') !== UserRole::SuperAdmin->value), 'nullable', 'integer', Rule::exists('schools', 'id')]
                : ['prohibited'],
            'password' => ['nullable', 'string', Password::defaults()],
            'send_invitation' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $target = $this->targetUser();

                if ($target?->is($this->user()) && $this->input('role') !== $target->role->value) {
                    $validator->errors()->add('role', __('Vlastnú rolu nemôžete zmeniť.'));
                }
            },
        ];
    }

    /**
     * Validated attributes ready for the UserManager, with the school decided on the server.
     *
     * @return array<string, mixed>
     */
    public function userData(): array
    {
        $data = $this->safe()->only(['first_name', 'last_name', 'email', 'role', 'password']);
        $role = UserRole::from($data['role']);

        $data['school_id'] = match (true) {
            $role === UserRole::SuperAdmin => null,
            $this->user()->isSuperAdmin() => (int) $this->validated('school_id'),
            default => $this->user()->school_id,
        };

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['send_invitation' => __('pozvánka')];
    }

    private function targetUser(): ?User
    {
        $user = $this->route('user');

        return $user instanceof User ? $user : null;
    }
}
