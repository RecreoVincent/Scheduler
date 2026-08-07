<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course', 'department_id', 'name', 'room_type', 'capacity'])]
class Room extends Model
{
    use BelongsToDepartment;

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
