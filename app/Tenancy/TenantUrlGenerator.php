<?php

namespace App\Tenancy;

use App\Models\Organization;

class TenantUrlGenerator
{
    public function to(Organization $organization, string $path = ''): string
    {
        $appUrl = parse_url((string) config('app.url'));
        $scheme = $appUrl['scheme'] ?? 'https';
        $host = $appUrl['host'] ?? 'localhost';
        $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';

        if (config('tenancy.resolution') === 'subdomain') {
            $host = $organization->slug.'.'.$host;
            $prefix = '';
        } else {
            $prefix = '/t/'.rawurlencode($organization->slug);
        }

        return $scheme.'://'.$host.$port.$prefix.'/'.ltrim($path, '/');
    }
}
