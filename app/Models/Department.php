<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'program_name', 'logo_path', 'email', 'sort_order', 'default_unit_limit_full_time', 'default_unit_limit_industry_part_time', 'default_unit_limit_flexible_part_time', 'semester_first_enabled', 'semester_second_enabled', 'semester_summer_enabled'])]
class Department extends Model
{
    protected function casts(): array
    {
        return [
            'semester_first_enabled' => 'boolean',
            'semester_second_enabled' => 'boolean',
            'semester_summer_enabled' => 'boolean',
        ];
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function sections(): HasMany { return $this->hasMany(AcademicSection::class); }
    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
    public function rooms(): HasMany { return $this->hasMany(Room::class); }

    /** @return array<int, string> */
    public function enabledSemesterCodes(): array
    {
        return collect([
            '1st' => $this->semester_first_enabled,
            '2nd' => $this->semester_second_enabled,
            'Summer' => $this->semester_summer_enabled,
        ])->filter()->keys()->all();
    }
}
