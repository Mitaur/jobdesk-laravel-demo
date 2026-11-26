<?php

namespace Database\Seeders;

use App\Enums\JobStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo account + 10 customers + 40 jobs. All names, phones, emails and
 * addresses are FAKE sample data (555 numbers, example.com emails).
 *
 * Written without Faker on purpose: the server installs with `composer install --no-dev`,
 * where Faker (a dev package) is not available. Factories are used by the tests only.
 */
class DemoDataSeeder extends Seeder
{
    private const CUSTOMERS = [
        ['Olivia Bennett', '(555) 010-2231', 'olivia.bennett@example.com', '412 Maple Ave, Springfield, IL 62704'],
        ['Marcus Reed', '(555) 010-4410', 'marcus.reed@example.com', '88 Oak Ridge Dr, Springfield, IL 62711'],
        ['Hannah Cole', '(555) 010-7782', 'hannah.cole@example.com', '1520 Birch St, Chatham, IL 62629'],
        ['Daniel Ortiz', '(555) 010-3398', 'daniel.ortiz@example.com', '9 Willow Ct, Springfield, IL 62702'],
        ['Grace Turner', '(555) 010-5564', 'grace.turner@example.com', '230 Lakeview Rd, Sherman, IL 62684'],
        ['Ethan Brooks', '(555) 010-6127', 'ethan.brooks@example.com', '77 Pine Hollow Ln, Springfield, IL 62707'],
        ['Sophia Patel', '(555) 010-8843', 'sophia.patel@example.com', '1045 Cedar Blvd, Rochester, IL 62563'],
        ['Liam Foster', '(555) 010-2905', null, '316 Elm St, Springfield, IL 62703'],
        ['Brightside Cafe (Mia Lopez)', '(555) 010-9012', 'hello@brightside.example.com', '50 Main St, Springfield, IL 62701'],
        ['Noah Kim', '(555) 010-4476', 'noah.kim@example.com', '612 Sunset Ter, Chatham, IL 62629'],
    ];

    private const TITLES = [
        'Kitchen faucet leak repair', 'AC tune-up (spring check)', 'Replace water heater (40 gal)',
        'Unclog bathroom drain', 'Furnace repair – no heat', 'Install new thermostat',
        'Running toilet repair', 'Duct cleaning – whole house', 'Garbage disposal replacement',
        'Outdoor hose bib repair', 'Heat pump service', 'Sump pump inspection',
        'Install bathroom vanity sink', 'AC repair – not cooling', 'Low water pressure – diagnose',
        'Annual HVAC maintenance plan', 'Shower valve repair', 'Mini-split install quote',
        'Burst pipe – emergency repair', 'Replace furnace filter + inspection',
    ];

    /**
     * 40 jobs: [status, day offset from today (null = no date), hour].
     * 10 quotes, 12 scheduled (3 of them tomorrow), 14 done, 4 cancelled.
     */
    private const PLAN = [
        ['quote', null, 0], ['quote', null, 0], ['quote', null, 0], ['quote', null, 0], ['quote', null, 0],
        ['quote', 3, 10], ['quote', 6, 14], ['quote', 10, 9], ['quote', 15, 11], ['quote', 21, 13],
        ['scheduled', 0, 15], ['scheduled', 1, 8], ['scheduled', 1, 11], ['scheduled', 1, 14],
        ['scheduled', 2, 9], ['scheduled', 2, 13], ['scheduled', 3, 10], ['scheduled', 4, 16],
        ['scheduled', 5, 9], ['scheduled', 7, 11], ['scheduled', 9, 10], ['scheduled', 12, 14],
        ['done', -1, 10], ['done', -1, 15], ['done', -2, 9], ['done', -3, 13], ['done', -4, 11],
        ['done', -6, 10], ['done', -8, 14], ['done', -11, 9], ['done', -14, 16], ['done', -18, 10],
        ['done', -22, 12], ['done', -27, 9], ['done', -33, 15], ['done', -40, 11],
        ['cancelled', -5, 10], ['cancelled', -9, 14], ['cancelled', 6, 9], ['cancelled', 11, 15],
    ];

    private const PRICES = [95, 129, 1450, 160, 220, 289, 140, 480, 365, 110, 240, 150, 420, 185, 175, 199, 310, 3200, 650, 89];

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => config('jobdesk.demo_email')],
            [
                'name' => 'Demo Owner',
                'password' => config('jobdesk.demo_password'), // hashed by the model cast
            ],
        );
        // forceFill: works both from `db:seed` (unguarded) and from the demo:reset command (guarded).
        $user->forceFill(['email_verified_at' => now()])->save();

        // Start clean for this user (idempotent re-seeding).
        $user->customers()->delete();

        $customers = collect(self::CUSTOMERS)->map(fn (array $c) => $user->customers()->create([
            'name' => $c[0], 'phone' => $c[1], 'email' => $c[2], 'address' => $c[3],
        ]))->values();

        $today = Carbon::today();

        foreach (self::PLAN as $i => [$status, $offset, $hour]) {
            $customer = $customers[$i % $customers->count()];
            $titleIndex = ($i * 7 + intdiv($i, 20) * 3) % count(self::TITLES); // 4 different titles per customer

            $customer->jobs()->create([
                'title' => self::TITLES[$titleIndex],
                'status' => JobStatus::from($status),
                'price' => $status === 'quote' && $i % 2 === 0 ? null : self::PRICES[$titleIndex],
                'scheduled_at' => $offset === null ? null : $today->copy()->addDays($offset)->setTime($hour, 0),
                'notes' => match ($status) {
                    'quote' => 'Sent estimate by email. Waiting for the customer to confirm.',
                    'scheduled' => 'Customer will be home. Gate code shared by phone.',
                    'done' => 'Completed. Paid on site.',
                    'cancelled' => 'Customer cancelled – rescheduling later.',
                },
            ]);
        }
    }
}
