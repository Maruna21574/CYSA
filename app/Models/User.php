<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['school_id', 'first_name', 'last_name', 'email', 'password', 'role', 'is_active', 'email_notifications'])]
#[Hidden(['password', 'remember_token', 'research_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
        'email_notifications' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->research_code ??= static::generateResearchCode();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'email_notifications' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsToMany<Classroom, $this>
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Full name, used across the UI and in notifications.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * E-mails are compared case-insensitively, so they are always stored in lower case.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value): string => Str::lower(trim($value)));
    }

    public function initials(): string
    {
        return Str::upper(Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1));
    }

    /**
     * A user may sign in only while both the account and its school are active.
     */
    public function canSignIn(): bool
    {
        return $this->is_active && ($this->school === null || $this->school->is_active);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function canTeach(): bool
    {
        return $this->role->canTeach();
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Users the given user may manage: everyone for a super admin, teachers and students
     * of their own school for a school admin, nobody otherwise.
     *
     * @param  Builder<User>  $query
     */
    public function scopeManageableBy(Builder $query, User $actor): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->role !== UserRole::SchoolAdmin) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('school_id', $actor->school_id)
            ->whereIn('role', array_map(fn (UserRole $role): string => $role->value, UserRole::assignableBy($actor->role)));
    }

    /**
     * Every word of the term has to match the first name, last name or e-mail.
     *
     * @param  Builder<User>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        foreach (preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';

            $query->where(fn (Builder $query) => $query
                ->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like));
        }
    }

    /**
     * Random pseudonym used instead of the name in research exports.
     */
    public static function generateResearchCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (static::withTrashed()->where('research_code', $code)->exists());

        return $code;
    }
}
