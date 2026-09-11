<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course', 'academic_year', 'semester', 'majors_sent_at', 'majors_sent_by', 'minors_sent_back_at', 'minors_sent_back_by'])]
class ScheduleHandoff extends Model
{
    protected function casts(): array
    {
        return [
            'majors_sent_at' => 'datetime',
            'minors_sent_back_at' => 'datetime',
        ];
    }

    public function majorsSentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'majors_sent_by');
    }

    public function minorsSentBackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'minors_sent_back_by');
    }

    public function scopeForDepartment(Builder $query, string $code): Builder
    {
        return $query->where('course', strtoupper($code));
    }
}
