<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate --seed  →  demo account + 10 customers + 40 jobs.
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
