<?php

namespace App\Models;

use App\Tenancy\OrganizationDatabaseConfig;
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

    public function database(): OrganizationDatabaseConfig
    {
        return new OrganizationDatabaseConfig($this);
    }
}
