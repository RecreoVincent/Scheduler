<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year', 'academic_term_id'])]
class AcademicPeriod extends Model
{
    public function term(): BelongsTo { return $this->belongsTo(AcademicTerm::class, 'academic_term_id'); }
    public function sections(): HasMany { return $this->hasMany(AcademicSection::class); }
    public function schedules(): HasMany { return $this->hasMany(ClassSchedule::class); }
}
