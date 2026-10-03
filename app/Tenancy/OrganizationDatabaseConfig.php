<?php

namespace App\Tenancy;

use Illuminate\Support\Str;
use Stancl\Tenancy\DatabaseConfig;

class OrganizationDatabaseConfig extends DatabaseConfig
{
    public function getName(): ?string
    {
        return $this->tenant->database_name;
    }

    public function getUsername(): ?string
    {
        return $this->tenant->database_username;
    }

    public function getPassword(): ?string
    {
        return $this->tenant->database_password;
    }

    public function makeCredentials(): void
    {
        if (! $this->tenant->database_username) {
            $this->tenant->database_username = 'tenant_'.Str::lower(Str::random(20));
        }

        if (! $this->tenant->database_password) {
            $this->tenant->database_password = Str::password(32);
        }

        $this->tenant->save();
    }

    public function tenantConfig(): array
    {
        return [
            'host' => $this->tenant->database_host,
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
        ];
    }
}
