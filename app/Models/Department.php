<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'program_name', 'logo_path', 'email', 'sort_order'])]
class Department extends Model
{
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function sections(): HasMany { return $this->hasMany(AcademicSection::class); }
    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
    public function rooms(): HasMany { return $this->hasMany(Room::class); }
}
