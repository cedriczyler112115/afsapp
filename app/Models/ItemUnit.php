<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemUnit extends Model
{
    protected $fillable = [
        'item_id',
        'serial',
        'full_code',
        'qr_code',
        'status',
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

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'item_unit_id');
    }

    public function returns()
    {
        return $this->hasMany(ItemReturn::class, 'item_unit_id');
    }
}
