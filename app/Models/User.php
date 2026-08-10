<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function isClinician(): bool
    {
        return $this->role === 'clinician';
    }

    public function isPharmacist(): bool
    {
        return $this->role === 'pharmacist';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function clinicalDecisions()
    {
        return $this->hasMany(ClinicalDecision::class, 'clinician_id');
    }

    public function prescriptionsHandled()
    {
        return $this->hasMany(Prescription::class, 'pharmacist_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
