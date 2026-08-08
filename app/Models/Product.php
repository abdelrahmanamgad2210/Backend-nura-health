<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'slug', 'type', 'category', 'name', 'short_description', 'long_description',
        'price', 'due_now', 'price_note', 'badge', 'consult_only', 'quiz_category',
        'includes', 'flow',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'due_now' => 'decimal:2',
            'consult_only' => 'boolean',
            'includes' => 'array',
            'flow' => 'array',
        ];
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
