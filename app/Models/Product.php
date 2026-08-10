<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'slug', 'type', 'category', 'category_ar', 'name', 'name_ar',
        'short_description', 'short_description_ar', 'long_description', 'long_description_ar',
        'price', 'due_now', 'price_note', 'price_note_ar', 'badge', 'badge_ar',
        'consult_only', 'quiz_category', 'includes', 'includes_ar', 'flow', 'flow_ar',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'due_now' => 'decimal:2',
            'consult_only' => 'boolean',
            'includes' => 'array',
            'includes_ar' => 'array',
            'flow' => 'array',
            'flow_ar' => 'array',
        ];
    }

    /**
     * Returns this product's display fields in the given locale, falling
     * back to English wherever an Arabic translation is missing. Used by
     * ProductController rather than a locale-aware accessor, so API
     * responses stay explicit about which locale they resolved.
     */
    public function localized(string $locale): array
    {
        $attributes = $this->toArray();

        if ($locale !== 'ar') {
            return $attributes;
        }

        foreach (['category', 'name', 'short_description', 'long_description', 'price_note', 'badge', 'includes', 'flow'] as $field) {
            $arValue = $attributes["{$field}_ar"] ?? null;
            if ($arValue !== null && $arValue !== '' && $arValue !== []) {
                $attributes[$field] = $arValue;
            }
        }

        return $attributes;
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
