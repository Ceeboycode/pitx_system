<?php

use App\Services\Company\CompanyStatusService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(CompanyStatusService::class)->markExpiredDocumentsAndSync();
})->hourly()->name('companies:mark-expired-documents');
