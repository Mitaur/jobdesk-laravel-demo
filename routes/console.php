<?php

use Illuminate\Support\Facades\Schedule;

/*
| Task scheduler. On the server ONE cron line runs this every minute:
|   * * * * * cd /path/to/jobdesk && php artisan schedule:run >> /dev/null 2>&1
| Check with:  php artisan schedule:list
*/

Schedule::command('jobs:remind-tomorrow')
    ->dailyAt(config('jobdesk.reminder_time'))
    ->withoutOverlapping();

// Public demo: restore clean sample data every night.
Schedule::command('demo:reset')
    ->dailyAt(config('jobdesk.demo_reset_time'))
    ->when(fn () => (bool) config('jobdesk.demo_mode'));

// Remove expired API tokens.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
