<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentSource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'source_type',
        'name',
        'is_active',
    ];
}
