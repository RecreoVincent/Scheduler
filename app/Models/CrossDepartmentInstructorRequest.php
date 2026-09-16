<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['subject_id', 'requesting_department', 'requested_department', 'requested_by', 'status', 'assigned_instructor_id', 'fulfilled_by', 'fulfilled_at'])]
class CrossDepartmentInstructorRequest extends Model
{
    protected function casts(): array
    {
        return ['fulfilled_at' => 'datetime'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedInstructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_instructor_id');
    }

    public function assignedInstructors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'cross_department_instructor_request_assignments',
            'instructor_request_id',
            'instructor_id',
        )
            ->withPivot('priority')
            ->withTimestamps()
            ->orderBy('cross_department_instructor_request_assignments.priority');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }
}
