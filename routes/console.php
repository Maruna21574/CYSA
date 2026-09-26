<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
| On shared hosting (Websupport) one cron entry runs everything:
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
*/

// Queued jobs (e-mails, notifications) without a permanently running worker.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('quiz:expire-attempts')->everyMinute()->withoutOverlapping();
