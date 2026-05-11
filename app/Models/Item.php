<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $primaryKey = 'item_id';

    // Disable standard timestamps if we only have date_created
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'item_name',
        'sku',
        'description',
        'unit_id',
        'current_quantity',
        'reorder_level',
        'create_by',
        'date_created',
        'is_status',
    ];

    protected $casts = [
        'date_created' => 'datetime',
    ];

    public function category()
    {
        // belongsTo(RelatedModel, foreignKey, ownerKey)
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id', 'id');
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'item_id', 'item_id');
    }

    public function returns()
    {
        return $this->hasMany(ItemReturn::class, 'item_id', 'item_id');
    }
}
