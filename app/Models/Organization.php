<?php

namespace App\Models;

use App\Tenancy\OrganizationDatabaseConfig;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant;

class Organization extends Tenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use SoftDeletes;

    protected $table = 'organizations';

    protected $casts = [
        'data' => 'array',
        'database_password' => 'encrypted',
        'deleted_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'database_host',
            'database_name',
            'database_username',
            'database_password',
            'status',
            'data',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    public function samlConnection(): HasOne
    {
        return $this->hasOne(OrganizationSamlConnection::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'organization_modules')
            ->withPivot('enabled')
            ->withTimestamps();
    }

    public function database(): OrganizationDatabaseConfig
    {
        return new OrganizationDatabaseConfig($this);
    }
}
