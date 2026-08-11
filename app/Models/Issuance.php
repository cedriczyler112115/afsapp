<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issuance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_issued' => 'datetime',
        'received_at' => 'datetime',
        'damage_photos_path' => 'array',
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

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function damageTransactions()
    {
        return $this->hasMany(StockTransaction::class)->where('type', 'DAMAGED');
    }

    public function supplyRequestItem()
    {
        return $this->belongsTo(SupplyRequestItem::class);
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
