<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'borrow_date' => 'datetime',
        'expected_return_date' => 'datetime',
    ];

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function itemUnit()
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returns()
    {
        return $this->hasMany(ItemReturn::class, 'borrowing_id');
    }
}
