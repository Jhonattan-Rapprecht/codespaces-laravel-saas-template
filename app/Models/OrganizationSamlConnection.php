<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSamlConnection extends Model
{
    protected $fillable = [
        'idp_entity_id',
        'sso_url',
        'x509_certificate',
        'email_attribute',
        'name_attribute',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'x509_certificate' => 'encrypted',
            'enabled' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
