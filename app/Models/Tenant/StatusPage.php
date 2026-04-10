<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StatusPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'custom_domain',
        'is_public',
        'password_protected',
        'password_hash',
        'logo_url',
        'brand_color',
        'custom_css',
        'footer_text',
        'show_powered_by',
        'show_subscribers',
        'allow_subscriber_signup',
        'incident_retention_days',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'password_protected' => 'boolean',
        'show_powered_by' => 'boolean',
        'show_subscribers' => 'boolean',
        'allow_subscriber_signup' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (StatusPage $statusPage) {
            if (empty($statusPage->slug)) {
                $statusPage->slug = Str::slug($statusPage->title);
            }
        });
    }

    public function components()
    {
        return $this->belongsToMany(Component::class, 'component_status_page')->withTimestamps()->orderBy('sort_order');
    }

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }

    public function incidents()
    {
        return $this->hasManyThrough(Incident::class, Component::class, 'id', 'component_id', 'id', 'id');
    }

    public function recentIncidents(int $limit = 10)
    {
        return $this->hasManyThrough(Incident::class, Component::class, 'id', 'component_id', 'id', 'id')
            ->orderByDesc('created_at')
            ->limit($limit);
    }

    public function getPublicUrlAttribute(): string
    {
        if ($this->custom_domain) {
            return "https://{$this->custom_domain}";
        }

        $tenant = tenant('domain') ?? request()->getHost();

        return url("/status/{$this->slug}");
    }
}
