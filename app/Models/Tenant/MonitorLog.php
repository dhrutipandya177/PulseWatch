<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'status',
        'response_time_ms',
        'status_code',
        'error_message',
        'is_incident',
        'checked_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_incident' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
