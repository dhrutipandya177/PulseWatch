<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'currency',
        'interval',
        'trial_days',
        'max_monitors',
        'check_interval_seconds',
        'has_status_page',
        'has_custom_domain',
        'has_team_members',
        'max_team_members',
        'has_email_notifications',
        'has_sms_notifications',
        'has_slack_notifications',
        'has_webhook_notifications',
        'has_ssl_monitoring',
        'data_retention_days',
        'is_active',
        'stripe_price_id',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'trial_days' => 'integer',
        'max_monitors' => 'integer',
        'check_interval_seconds' => 'integer',
        'has_status_page' => 'boolean',
        'has_custom_domain' => 'boolean',
        'has_team_members' => 'boolean',
        'max_team_members' => 'integer',
        'has_email_notifications' => 'boolean',
        'has_sms_notifications' => 'boolean',
        'has_slack_notifications' => 'boolean',
        'has_webhook_notifications' => 'boolean',
        'has_ssl_monitoring' => 'boolean',
        'data_retention_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getPriceAttribute(): string
    {
        return number_format($this->price_cents / 100, 2);
    }

    public function getFormattedPriceAttribute(): string
    {
        return "$" . $this->price . "/" . $this->interval;
    }

    protected static function booted(): void
    {
        static::creating(function (Plan $plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'current_plan_id');
    }
}
