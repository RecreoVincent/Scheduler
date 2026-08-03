<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course', 'name', 'room_type', 'capacity'])]
class Room extends Model
{
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
