<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    protected $table = 'unit_of_measures';

    protected $primaryKey = 'id';

    protected $fillable = ['unit_name', 'unit_code', 'unit_type', 'description', 'is_active'];

    public $timestamps = true;
}
