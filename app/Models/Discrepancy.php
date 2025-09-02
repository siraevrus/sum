<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discrepancy extends Model
{
    protected $fillable = [
        'product_in_transit_id',
        'user_id',
        'reason',
        'old_quantity',
        'new_quantity',
        'old_color',
        'new_color',
        'old_size',
        'new_size',
        'old_weight',
        'new_weight',
    ];

    public function productInTransit()
    {
        return $this->belongsTo(ProductInTransit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
