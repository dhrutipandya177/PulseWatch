<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Monitor extends Model
{
    use HasFactory;

    const TYPE_HTTP = 'http';
    const TYPE_PING = 'ping';
    const TYPE_PORT = 'port';
    const TYPE_SSL = 'ssl';

    protected $fillable = [
        'name',
        'url',
        'method',
        'type',
        'check_interval_seconds',
        'timeout_seconds',
        'expected_status_code',
        'headers',
        'body',
        'expected_content',
        'follow_redirects',
        'verify_ssl',
        'is_active',
        'last_checked_at',
        'last_status',
        'last_response_time_ms',
        'last_status_code',
        'last_error_message',
        'last_error_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'body' => 'array',
        'expected_content' => 'array',
        'follow_redirects' => 'boolean',
        'verify_ssl' => 'boolean',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_status' => 'boolean',
        'last_response_time_ms' => 'integer',
        'last_error_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(MonitorLog::class)->orderByDesc('checked_at');
    }

    public function recentLogs(int $limit = 100)
    {
        return $this->logs()->limit($limit);
    }

    public function component()
    {
        return $this->hasOne(Component::class);
    }

    public function sslCertificate()
    {
        return $this->hasOne(SslCertificate::class);
    }

    public function uptimePercentage(int $days = 30): float
    {
        $since = Carbon::now()->subDays($days);

        $total = $this->logs()->where('checked_at', '>=', $since)->count();
        if ($total === 0) {
            return 100.0;
        }

        $up = $this->logs()->where('checked_at', '>=', $since)->where('status', true)->count();

        return round(($up / $total) * 100, 2);
    }

    public function averageResponseTime(int $days = 7): ?float
    {
        $since = Carbon::now()->subDays($days);

        return $this->logs()
            ->where('checked_at', '>=', $since)
            ->where('status', true)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');
    }

    public function latestIncident()
    {
        return $this->component?->incidents()->latest()->first();
    }

    public function getUptimeBadgeAttribute(): string
    {
        $uptime = $this->uptimePercentage();

        if ($uptime >= 99.9) {
            return 'bg-green-500';
        }

        if ($uptime >= 99) {
            return 'bg-yellow-500';
        }

        return 'bg-red-500';
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->last_status === true) {
            return 'bg-green-100 text-green-800';
        }

        if ($this->last_status === false) {
            return 'bg-red-100 text-red-800';
        }

        return 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->last_status === true) {
            return 'Up';
        }

        if ($this->last_status === false) {
            return 'Down';
        }

        return 'Pending';
    }
}
