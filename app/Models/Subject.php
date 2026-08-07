<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course', 'department_id', 'code', 'name', 'subject_type', 'classification', 'year_level', 'semester', 'academic_term_id', 'curriculum', 'units'])]
class Subject extends Model
{
    use BelongsToDepartment;

    protected static function booted(): void
    {
        static::saving(function (Subject $subject): void {
            if ($subject->isDirty('semester') && filled($subject->semester)) {
                $subject->academic_term_id = AcademicTerm::query()->where('code', $subject->semester)->value('id');
            }
        });
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_instructor', 'subject_id', 'instructor_id')
            ->withPivot('priority')
            ->withTimestamps()
            ->orderBy('subject_instructor.priority')
            ->orderBy('subject_instructor.id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
