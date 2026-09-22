<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subject_id', 'from_department', 'to_department', 'subject_code', 'subject_name', 'subject_type', 'units', 'endorsed_by', 'scheduled_by', 'scheduled_at'])]
class SubjectEndorsement extends Model
{
    protected function casts(): array
    {
        return [
            'units' => 'float',
            'scheduled_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function endorsedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_by');
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
