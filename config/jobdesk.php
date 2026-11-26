<?php

return [
    /*
    | Public demo settings. In demo mode the landing page shows the demo login,
    | the demo account cannot change its email/password or delete itself, and
    | `demo:reset` (scheduled nightly) restores the sample data.
    | The demo password is intentionally public – it is printed on the landing page.
    */
    'demo_mode' => (bool) env('DEMO_MODE', true),
    'demo_email' => env('DEMO_EMAIL', 'demo@jobdesk.test'),
    'demo_password' => env('DEMO_PASSWORD', 'jobdesk-demo'),

    // Time (server timezone) for the daily scheduled commands.
    'reminder_time' => env('JOBS_REMINDER_TIME', '07:00'),
    'demo_reset_time' => env('DEMO_RESET_TIME', '03:00'),
];
