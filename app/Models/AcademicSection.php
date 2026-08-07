<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademicPeriod;
use App\Models\Concerns\BelongsToDepartment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course', 'department_id', 'name', 'year_level', 'academic_year', 'semester', 'academic_period_id'])]
class AcademicSection extends Model
{
    use BelongsToAcademicPeriod, BelongsToDepartment;

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'section_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
