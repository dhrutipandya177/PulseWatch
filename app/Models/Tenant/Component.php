<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Component extends Model
{
    use HasFactory;

    const STATUS_OPERATIONAL = 'operational';
    const STATUS_DEGRADED = 'degraded';
    const STATUS_OUTAGE = 'outage';
    const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'group_name',
        'sort_order',
        'show_uptime_percentage',
        'monitor_id',
    ];

    protected $casts = [
        'show_uptime_percentage' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Component $component) {
            if (empty($component->slug)) {
                $component->slug = Str::slug($component->name);
            }
        });
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function statusPages()
    {
        return $this->belongsToMany(StatusPage::class, 'component_status_page')->withTimestamps();
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function activeIncidents()
    {
        return $this->incidents()->whereIn('status', ['investigating', 'identified', 'monitoring']);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPERATIONAL => 'bg-green-100 text-green-800',
            self::STATUS_DEGRADED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_OUTAGE => 'bg-red-100 text-red-800',
            self::STATUS_MAINTENANCE => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPERATIONAL => 'Operational',
            self::STATUS_DEGRADED => 'Degraded Performance',
            self::STATUS_OUTAGE => 'Major Outage',
            self::STATUS_MAINTENANCE => 'Under Maintenance',
            default => $this->status,
        };
    }
}
