<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SamlAuthenticationRequest extends Model
{
    use CentralConnection;

    protected $primaryKey = 'state_hash';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'state_hash',
        'organization_id',
        'request_id',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
