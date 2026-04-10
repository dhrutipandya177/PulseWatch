<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'is_confirmed',
        'confirmed_at',
        'status_page_id',
        'component_subscriptions',
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        'component_subscriptions' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscriber $subscriber) {
            if (empty($subscriber->token)) {
                $subscriber->token = Str::random(64);
            }
        });
    }

    public function statusPage()
    {
        return $this->belongsTo(StatusPage::class);
    }

    public function confirm(): bool
    {
        if ($this->is_confirmed) {
            return false;
        }

        $this->update([
            'is_confirmed' => true,
            'confirmed_at' => now(),
        ]);

        return true;
    }

    public function isSubscribedToComponent(int $componentId): bool
    {
        if (empty($this->component_subscriptions)) {
            return true;
        }

        return in_array($componentId, $this->component_subscriptions);
    }
}
