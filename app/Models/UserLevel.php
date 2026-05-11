<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    protected $table = 'user_level';
    
    // Disable standard timestamps as the legacy table uses 'date_created' instead
    public $timestamps = false;

    protected $fillable = [
        'level_name',
        'is_status',
        'created_by',
        'date_created',
        'access_rights',
    ];

    protected $casts = [
        'access_rights' => 'array',
        'date_created' => 'datetime',
    ];
}
