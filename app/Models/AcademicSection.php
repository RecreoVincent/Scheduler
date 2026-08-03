<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course', 'name', 'year_level', 'academic_year', 'semester'])]
class AcademicSection extends Model
{
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'section_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
