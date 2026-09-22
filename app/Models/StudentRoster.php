<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_id', 'full_name', 'section', 'course', 'department_id', 'imported_at'])]
class StudentRoster extends Model
{
    use BelongsToDepartment;

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }
}
