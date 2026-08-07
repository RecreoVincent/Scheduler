<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ms365StudentAccount extends Model
{
    protected $fillable = ['email','display_name','first_name','last_name','object_id','license','is_blocked','soft_deleted_at','ms365_created_at','last_imported_at'];

    protected function casts(): array
    {
        return ['is_blocked'=>'boolean','soft_deleted_at'=>'datetime','ms365_created_at'=>'datetime','last_imported_at'=>'datetime'];
    }
}
