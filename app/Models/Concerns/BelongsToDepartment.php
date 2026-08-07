<?php

namespace App\Models\Concerns;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToDepartment
{
    public static function bootBelongsToDepartment(): void
    {
        static::saving(function ($model): void {
            if ($model->isDirty('course') && filled($model->course)) {
                $model->department_id = Department::query()
                    ->where('code', strtoupper((string) $model->course))->value('id');
            }

            if ($model->isDirty('department_id') && $model->department_id && blank($model->course)) {
                $model->course = Department::query()->whereKey($model->department_id)->value('code');
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeForDepartment(Builder $query, string $code): Builder
    {
        return $query->whereHas('department', fn (Builder $department) => $department->where('code', strtoupper($code)));
    }
}
