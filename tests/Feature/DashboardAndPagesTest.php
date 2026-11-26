<?php

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Support\Carbon;

test('landing page shows the demo badge and the demo login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Demo')
        ->assertSee(config('jobdesk.demo_email'))
        ->assertSee(config('jobdesk.demo_password'));
});

test('dashboard shows counts per status and this week\'s jobs', function () {
    Carbon::setTestNow('2026-11-25 12:00:00'); // a Wednesday

    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();
    ServiceJob::factory()->for($customer)->count(2)->create(['status' => JobStatus::Quote]);
    ServiceJob::factory()->for($customer)->scheduled(Carbon::parse('2026-11-27 09:00'))->create(['title' => 'Friday visit']);
    ServiceJob::factory()->for($customer)->scheduled(Carbon::parse('2026-12-10 09:00'))->create(['title' => 'Next month visit']);
    ServiceJob::factory()->for($customer)->status(JobStatus::Done)->create();

    // Another user's jobs must not be counted.
    ServiceJob::factory()->count(5)->create(['status' => JobStatus::Quote]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('Friday visit')
        ->assertDontSee('Next month visit');

    expect($response->viewData('counts')->all())->toBe([
        'quote' => 2, 'scheduled' => 2, 'done' => 1, 'cancelled' => 0,
    ]);

    Carbon::setTestNow();
});

test('unknown pages show the custom 404 page', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee("We couldn't find that page", false);
});

test('the demo seeder creates 1 demo user, 10 customers and 40 jobs', function () {
    $this->seed();

    $demo = User::firstWhere('email', config('jobdesk.demo_email'));

    expect($demo)->not->toBeNull()
        ->and($demo->customers()->count())->toBe(10)
        ->and($demo->serviceJobs()->count())->toBe(40);

    // seeding twice must not duplicate data
    $this->seed();
    expect(User::count())->toBe(1)->and(ServiceJob::count())->toBe(40);
});

test('HSTS header is sent only over HTTPS', function () {
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
});
