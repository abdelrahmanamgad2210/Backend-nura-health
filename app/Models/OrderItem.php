<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_slug', 'product_name', 'product_type',
        'unit_price', 'due_now_amount', 'clinical_case_id', 'fulfilment_status',
        'authorized_at', 'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'due_now_amount' => 'decimal:2',
            'authorized_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function clinicalCase()
    {
        return $this->belongsTo(ClinicalCase::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function isClinical(): bool
    {
        return $this->product_type === 'clinical';
    }
}
