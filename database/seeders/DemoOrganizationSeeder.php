<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class DemoOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'demo'],
            [
                'id' => 'demo',
                'name' => 'Demo Organization',
                'database_host' => config('database.connections.'.config('database.default').'.host'),
                'database_name' => 'tenant_demo',
                'status' => 'active',
                'initial_admin_name' => 'Demo Administrator',
                'initial_admin_email' => env('DEMO_ADMIN_EMAIL', 'admin@demo.test'),
                'initial_admin_password' => env('DEMO_ADMIN_PASSWORD', 'password'),
            ],
        );

        Domain::query()->firstOrCreate([
            'domain' => 'demo.'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'tenant_id' => $organization->getTenantKey(),
        ]);
    }
}
