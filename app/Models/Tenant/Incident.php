<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_IDENTIFIED = 'identified';
    const STATUS_MONITORING = 'monitoring';
    const STATUS_RESOLVED = 'resolved';

    const SEVERITY_NONE = 'none';
    const SEVERITY_MINOR = 'minor';
    const SEVERITY_MAJOR = 'major';
    const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'title',
        'description',
        'status',
        'severity',
        'component_id',
        'notify_subscribers',
        'resolved_at',
    ];

    protected $casts = [
        'notify_subscribers' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function component()
    {
        return $this->belongsTo(Component::class);
    }

    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class)->orderByDesc('created_at');
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_INVESTIGATING => 'bg-red-100 text-red-800',
            self::STATUS_IDENTIFIED => 'bg-orange-100 text-orange-800',
            self::STATUS_MONITORING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_RESOLVED => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_INVESTIGATING => 'Investigating',
            self::STATUS_IDENTIFIED => 'Identified',
            self::STATUS_MONITORING => 'Monitoring',
            self::STATUS_RESOLVED => 'Resolved',
            default => ucfirst($this->status),
        };
    }

    public function getSeverityBadgeAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'bg-red-600 text-white',
            self::SEVERITY_MAJOR => 'bg-red-400 text-white',
            self::SEVERITY_MINOR => 'bg-yellow-400 text-gray-900',
            default => 'bg-gray-200 text-gray-800',
        };
    }
}
