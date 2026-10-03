<?php

use App\Models\SamlAuthenticationRequest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => SamlAuthenticationRequest::query()
    ->where('expires_at', '<', now())
    ->delete())->hourly();
