<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'issuer',
        'subject',
        'valid_from',
        'valid_until',
        'days_remaining',
        'serial_number',
        'signature_algorithm',
        'is_valid',
        'last_checked_at',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_valid' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->days_remaining !== null && $this->days_remaining <= $days;
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }
}
