<?php

use App\Http\Controllers\SuperAdmin\AuthenticatedSessionController;
use App\Http\Controllers\SuperAdmin\OrganizationController;
use App\Http\Controllers\SuperAdmin\SamlConnectionController;
use App\Http\Middleware\AuthenticateSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');

    Route::middleware(AuthenticateSuperAdmin::class)->group(function (): void {
        Route::get('/', [OrganizationController::class, 'index'])->name('dashboard');
        Route::patch('/organizations/{organization}/status', [OrganizationController::class, 'updateStatus'])->name('organizations.status');
        Route::get('/organizations/{organization}/saml', [SamlConnectionController::class, 'edit'])->name('organizations.saml.edit');
        Route::put('/organizations/{organization}/saml', [SamlConnectionController::class, 'update'])->name('organizations.saml.update');
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
