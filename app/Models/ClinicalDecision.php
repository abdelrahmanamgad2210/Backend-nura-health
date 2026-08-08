<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * A clinical decision is a permanent audit record. Corrections must be
 * represented by a new decision, never an edit — enforced here as a
 * safety net in addition to never registering a PATCH/DELETE route.
 */
class ClinicalDecision extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'clinical_case_id', 'clinician_id', 'outcome', 'rationale', 'esigned_name', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Clinical decisions are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Clinical decisions are immutable and cannot be deleted.');
        });
    }

    public function clinicalCase()
    {
        return $this->belongsTo(ClinicalCase::class);
    }

    public function clinician()
    {
        return $this->belongsTo(User::class, 'clinician_id');
    }
}
