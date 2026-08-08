<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'order_item_id', 'pharmacist_id', 'physician_verified', 'identity_match',
        'duplicate_fill_check', 'dur_review', 'counselling_required', 'status',
        'accepted_at', 'dispensed_at',
    ];

    protected function casts(): array
    {
        return [
            'physician_verified' => 'boolean',
            'identity_match' => 'boolean',
            'duplicate_fill_check' => 'boolean',
            'dur_review' => 'boolean',
            'counselling_required' => 'boolean',
            'accepted_at' => 'datetime',
            'dispensed_at' => 'datetime',
        ];
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function checklistComplete(): bool
    {
        return $this->physician_verified
            && $this->identity_match
            && $this->duplicate_fill_check
            && $this->dur_review
            && $this->counselling_required;
    }
}
