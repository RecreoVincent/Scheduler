<?php

namespace App\Models\Concerns;

use App\Models\AcademicPeriod;
use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAcademicPeriod
{
    public static function bootBelongsToAcademicPeriod(): void
    {
        static::saving(function ($model): void {
            if (($model->isDirty('academic_year') || $model->isDirty('semester')) && filled($model->academic_year) && filled($model->semester)) {
                $termId = AcademicTerm::query()->where('code', $model->semester)->value('id');
                if ($termId) {
                    $model->academic_period_id = AcademicPeriod::query()->firstOrCreate([
                        'academic_year' => $model->academic_year,
                        'academic_term_id' => $termId,
                    ])->id;
                }
            }
        });
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function scopeForAcademicPeriod(Builder $query, string $academicYear, string $semester): Builder
    {
        return $query->whereHas('academicPeriod', function (Builder $period) use ($academicYear, $semester): void {
            $period->where('academic_year', $academicYear)
                ->whereHas('term', fn (Builder $term) => $term->where('code', $semester));
        });
    }
}
