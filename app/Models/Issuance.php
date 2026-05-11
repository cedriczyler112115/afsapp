<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issuance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_issued' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itemUnits()
    {
        return $this->hasMany(ItemUnit::class);
    }

    public function issuanceGroup()
    {
        return $this->belongsTo(IssuanceGroup::class);
    }
}
