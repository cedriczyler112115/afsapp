<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyRequestItem extends Model
{
    protected $guarded = [];

    public function supplyRequest()
    {
        return $this->belongsTo(SupplyRequest::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function issuances()
    {
        return $this->hasMany(Issuance::class);
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, (int) ($this->approved_quantity ?? 0) - (int) $this->issued_quantity);
    }

    public function getUnreceivedQuantityAttribute(): int
    {
        return max(0, (int) $this->issued_quantity - (int) $this->received_quantity);
    }
}
