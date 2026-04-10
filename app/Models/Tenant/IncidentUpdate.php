<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentUpdate extends Model
{
    use HasFactory;

    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_IDENTIFIED = 'identified';
    const STATUS_MONITORING = 'monitoring';
    const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'incident_id',
        'status',
        'message',
        'user_id',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\Central\User::class);
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
}
