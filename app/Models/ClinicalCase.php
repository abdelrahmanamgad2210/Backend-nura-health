<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalCase extends Model
{
    protected $table = 'clinical_cases';

    protected $fillable = [
        'assessment_id', 'patient_id', 'category', 'risk_flag',
        'ai_draft_summary', 'status', 'assigned_clinician_id',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function assignedClinician()
    {
        return $this->belongsTo(User::class, 'assigned_clinician_id');
    }

    public function safetySignals()
    {
        return $this->hasMany(SafetySignal::class);
    }

    public function decisions()
    {
        return $this->hasMany(ClinicalDecision::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
