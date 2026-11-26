<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Public demo only: wipe all accounts/data and restore the sample data.
 * Scheduled nightly when DEMO_MODE=true, so visitors always see a clean demo.
 */
class ResetDemoData extends Command
{
    protected $signature = 'demo:reset {--force : Run even when DEMO_MODE is off}';

    protected $description = 'Delete all users and data, then re-seed the demo account (demo site only)';

    public function handle(): int
    {
        if (! config('jobdesk.demo_mode') && ! $this->option('force')) {
            $this->error('DEMO_MODE is off – refusing to delete data. Use --force if you really mean it.');

            return self::FAILURE;
        }

        DB::transaction(function () {
            DB::table('personal_access_tokens')->where('tokenable_type', User::class)->delete();
            DB::table('sessions')->delete();
            DB::table('password_reset_tokens')->delete();
            User::query()->delete(); // customers + jobs go with them (FK cascade)

            (new DemoDataSeeder)->run();
        });

        Log::info('[demo:reset] Demo data restored.');
        $this->info('Demo data restored.');

        return self::SUCCESS;
    }
}
