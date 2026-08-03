<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'password', 'role', 'course', 'year_level', 'academic_section_id', 'employment_type', 'outside_work_end_time', 'teaching_unit_limit', 'unit_limit_note', 'unit_limit_updated_at', 'account_status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'instructor_id');
    }

    public function academicSection(): BelongsTo
    {
        return $this->belongsTo(AcademicSection::class);
    }

    public function defaultTeachingUnitLimit(): int
    {
        return match ($this->employment_type) {
            'full_time' => 30,
            'industry_part_time', 'flexible_part_time', 'part_time' => 15,
            null => 20,
            default => 15,
        };
    }

    public function effectiveTeachingUnitLimit(): int
    {
        return $this->teaching_unit_limit ?? $this->defaultTeachingUnitLimit();
    }

    /**
     * Provide a backwards-compatible full name without storing a duplicate column.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
                $this->suffix,
            ], fn (?string $part): bool => filled($part))),
            set: fn (string $value): array => $this->splitName($value),
        );
    }

    /**
     * Convert legacy full-name input from registration and profile forms.
     *
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string, suffix: ?string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $suffix = null;

        if (count($parts) > 1 && in_array(strtolower(rtrim((string) end($parts), '.')), ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)) {
            $suffix = array_pop($parts);
        }

        $firstName = array_shift($parts);
        $lastName = count($parts) > 0 ? array_pop($parts) : null;
        $middleName = count($parts) > 0 ? implode(' ', $parts) : null;

        return [
            'first_name' => $firstName ?: null,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'suffix' => $suffix,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'teaching_unit_limit' => 'integer',
            'unit_limit_updated_at' => 'datetime',
        ];
    }
}
