<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafetySignal extends Model
{
    protected $fillable = ['clinical_case_id', 'label', 'severity', 'reference'];

    public function clinicalCase()
    {
        return $this->belongsTo(ClinicalCase::class);
    }
}
