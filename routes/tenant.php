<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\LoginController;
use App\Http\Controllers\Tenant\ModuleController;
use App\Http\Controllers\Tenant\SamlController;
use App\Http\Controllers\Tenant\TeamController;
use App\Http\Middleware\InitializeOrganizationTenancy;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;

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

$middleware[] = ScopeSessions::class;

Route::prefix($prefix)->middleware($middleware)->group(function () {
    Route::get('/', DashboardController::class)
        ->middleware(App\Http\Middleware\AuthenticateTenantUser::class)
        ->name('tenant.dashboard');

    Route::get('/login', [LoginController::class, 'create'])->name('tenant.login');
    Route::post('/login', [LoginController::class, 'store'])->name('tenant.login.store');
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->middleware(App\Http\Middleware\AuthenticateTenantUser::class)
        ->name('tenant.logout');
    Route::get('/modules/{slug}', [ModuleController::class, 'show'])
        ->middleware(App\Http\Middleware\AuthenticateTenantUser::class)
        ->name('tenant.modules.show');

    Route::middleware([App\Http\Middleware\AuthenticateTenantUser::class, App\Http\Middleware\EnsureTenantAdmin::class])
        ->prefix('team')
        ->group(function () {
            Route::post('/users', [TeamController::class, 'store'])->name('tenant.team.store');
            Route::patch('/users/{user}/role', [TeamController::class, 'updateRole'])->whereNumber('user')->name('tenant.team.role');
            Route::put('/users/{user}/password', [TeamController::class, 'updatePassword'])->whereNumber('user')->name('tenant.team.password');
        });

    Route::get('/saml/login', [SamlController::class, 'login'])->name('tenant.saml.login');
    Route::post('/saml/acs', [SamlController::class, 'assertionConsumer'])->name('tenant.saml.acs');
    Route::get('/saml/metadata', [SamlController::class, 'metadata'])->name('tenant.saml.metadata');
});
