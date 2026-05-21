<?php

use App\Services\SeatLockService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('seats:release-expired-locks', function (SeatLockService $seatLockService): int {
    $seatLockService->releaseExpiredLocks();
    $this->info('Expired seat locks released.');

    return self::SUCCESS;
})->purpose('Release expired seat locks');

Schedule::command('seats:release-expired-locks')->everyMinute();
