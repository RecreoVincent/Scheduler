<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course', 'code', 'name', 'subject_type', 'classification', 'year_level', 'semester', 'curriculum', 'units', 'instructor_id'])]
class Subject extends Model
{
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
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
