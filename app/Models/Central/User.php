<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Billable, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_name',
        'phone',
        'current_plan_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'trial_ends_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function isTenantOwner(): bool
    {
        return $this->role === 'tenant_owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSubscribedToPlan(string $planSlug): bool
    {
        return $this->subscribed('default', $planSlug) || $this->onTrial('default');
    }
}
