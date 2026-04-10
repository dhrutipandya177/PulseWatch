<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationChannel extends Model
{
    use HasFactory;

    const TYPE_EMAIL = 'email';
    const TYPE_SLACK = 'slack';
    const TYPE_WEBHOOK = 'webhook';
    const TYPE_SMS = 'sms';

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
