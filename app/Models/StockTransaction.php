<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $fillable = [
        'item_id',
        'unit_id',
        'type',
        'date_created',
        'created_by',
    ];

    public $timestamps = false;

    protected $casts = [
        'date_created' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(ItemUnit::class, 'unit_id', 'id');
    }
}
