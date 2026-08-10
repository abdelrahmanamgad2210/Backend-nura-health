<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'user_id', 'guest_token', 'category', 'intent_product_id', 'status', 'current_step',
        'urgent_flag', 'urgent_reason', 'answers', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'urgent_flag' => 'boolean',
            'answers' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function intentProduct()
    {
        return $this->belongsTo(Product::class, 'intent_product_id');
    }

    public function clinicalCase()
    {
        return $this->hasOne(ClinicalCase::class);
    }
}
