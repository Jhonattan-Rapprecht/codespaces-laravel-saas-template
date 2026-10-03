<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class InitializeOrganizationTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = config('tenancy.resolution') === 'subdomain'
            ? $this->subdomain($request)
            : $request->segment(1) === 't'
                ? $request->segment(2)
                : $request->header('X-Tenant');

        abort_unless(is_string($slug) && $slug !== '', 404);

        $organization = Organization::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        app(Tenancy::class)->initialize($organization);

        return $next($request);
    }

    private function subdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $centralHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $centralHost || ! str_ends_with($host, '.'.$centralHost)) {
            return null;
        }

        return Str::beforeLast($host, '.'.$centralHost);
    }
}
