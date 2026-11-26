<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Task scheduler. On the server ONE cron line runs this every minute:
|   * * * * * cd /path/to/jobdesk && php artisan schedule:run >> /dev/null 2>&1
| Check with:  php artisan schedule:list
|
| Why Schedule::call + Artisan::call instead of Schedule::command?
| Shared hosting (e.g. Hostinger) disables PHP's proc_open(), so the scheduler
| cannot start "php artisan ..." as a separate process. These closures run each
| command inside the same PHP process – works on shared hosting and everywhere else.
*/

Schedule::call(fn () => Artisan::call('jobs:remind-tomorrow'))
    ->name('jobs:remind-tomorrow')
    ->dailyAt(config('jobdesk.reminder_time'))
    ->withoutOverlapping();

// Public demo: restore clean sample data every night.
Schedule::call(fn () => Artisan::call('demo:reset'))
    ->name('demo:reset')
    ->dailyAt(config('jobdesk.demo_reset_time'))
    ->when(fn () => (bool) config('jobdesk.demo_mode'));

// Remove expired API tokens.
Schedule::call(fn () => Artisan::call('sanctum:prune-expired', ['--hours' => 24]))
    ->name('sanctum:prune-expired')
    ->daily();
