<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'label', 'sort_order'])]
class AcademicTerm extends Model
{
    public function periods(): HasMany { return $this->hasMany(AcademicPeriod::class); }
    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
}
