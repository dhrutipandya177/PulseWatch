<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant;

return [
    'tenant_model' => Tenant::class,
    'domain_model' => Domain::class,

    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_route_action_prefix' => '',

    'central_domains' => [
        'pulsewatch.test',
        'localhost',
    ],

    'middleware' => [
        'central' => [
            'web',
        ],
        'tenant' => [
            'web',
            'InitializeTenancyByDomain',
            'PreventAccessFromCentralDomains',
        ],
    ],

    'routes' => [
        'path' => base_path('routes/tenant.php'),
        'middleware' => [],
        'namespace' => App\Http\Controllers\Tenant::class,
    ],

    'tenants' => [
        'migrate' => true,
        'seed' => false,
    ],

    'storage' => [
        'storage_factory' => Stancl\Tenancy\Storage\DatabaseStorage::class,
    ],

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\CacheTagsBootstrapper::class,
    ],
];
