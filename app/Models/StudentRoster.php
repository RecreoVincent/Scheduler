<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRoster extends Model
{
    protected $fillable = ['student_id', 'full_name', 'section', 'imported_at'];

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }
}
