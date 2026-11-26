<?php

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\ServiceJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Finds tomorrow's scheduled jobs and writes a reminder line to the log.
 * (A real app would send an SMS/email here – the demo only logs.)
 * Runs daily from the scheduler: see routes/console.php.
 */
class RemindTomorrowJobs extends Command
{
    protected $signature = 'jobs:remind-tomorrow
                            {--date= : Check this date (Y-m-d) instead of tomorrow}';

    protected $description = 'Log a reminder for every job scheduled for tomorrow';

    public function handle(): int
    {
        try {
            $day = $this->option('date')
                ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay()
                : Carbon::tomorrow();
        } catch (\Throwable) {
            $this->error('Invalid --date. Use the format Y-m-d, e.g. 2026-12-01.');

            return self::INVALID;
        }

        $jobs = ServiceJob::query()
            ->with('customer')
            ->where('status', JobStatus::Scheduled)
            ->whereBetween('scheduled_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($jobs as $job) {
            $line = sprintf(
                'Reminder: job #%d "%s" for %s at %s',
                $job->id,
                $job->title,
                $job->customer->name,
                $job->scheduled_at->format('H:i'),
            );

            Log::info('[jobs:remind-tomorrow] '.$line, [
                'job_id' => $job->id,
                'customer_id' => $job->customer_id,
                'user_id' => $job->customer->user_id,
            ]);
            $this->line($line);
        }

        $summary = sprintf('%d job(s) scheduled for %s.', $jobs->count(), $day->toDateString());
        Log::info('[jobs:remind-tomorrow] Done: '.$summary);
        $this->info($summary);

        return self::SUCCESS;
    }
}
