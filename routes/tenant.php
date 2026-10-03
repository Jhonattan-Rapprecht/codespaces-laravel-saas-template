<?php

declare(strict_types=1);

use App\Http\Middleware\InitializeOrganizationTenancy;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

$pathResolution = config('tenancy.resolution') === 'path';
$prefix = $pathResolution ? 't/{organization}' : null;
$middleware = [
    'web',
    InitializeOrganizationTenancy::class,
];

if (! $pathResolution) {
    $middleware[] = PreventAccessFromCentralDomains::class;
}

Route::prefix($prefix)->middleware($middleware)->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application. The id of the current tenant is '.tenant('id');
    });
});
