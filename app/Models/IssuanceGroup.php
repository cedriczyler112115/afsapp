<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuanceGroup extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_printed' => 'datetime',
    ];

    public function issuances()
    {
        return $this->hasMany(Issuance::class);
    }
}
