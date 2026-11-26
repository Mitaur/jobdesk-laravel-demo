<?php

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

// ---------- scheduler ----------

test('jobs:remind-tomorrow logs only tomorrow\'s scheduled jobs', function () {
    Carbon::setTestNow('2026-11-26 07:00:00');
    $customer = Customer::factory()->create(['name' => 'Hannah Cole']);
    ServiceJob::factory()->for($customer)->scheduled(Carbon::parse('2026-11-27 10:00'))->create(['title' => 'Tomorrow job']);
    ServiceJob::factory()->for($customer)->scheduled(Carbon::parse('2026-11-28 10:00'))->create(['title' => 'Later job']);
    ServiceJob::factory()->for($customer)->create([
        'title' => 'Cancelled tomorrow', 'status' => JobStatus::Cancelled, 'scheduled_at' => '2026-11-27 12:00',
    ]);

    Log::spy();

    $this->artisan('jobs:remind-tomorrow')
        ->expectsOutputToContain('Tomorrow job')
        ->expectsOutputToContain('1 job(s) scheduled for 2026-11-27.')
        ->doesntExpectOutputToContain('Later job')
        ->doesntExpectOutputToContain('Cancelled tomorrow')
        ->assertSuccessful();

    Log::shouldHaveReceived('info')
        ->withArgs(fn ($message) => str_contains($message, 'Reminder: job #') && str_contains($message, 'Tomorrow job'))
        ->once();

    Carbon::setTestNow();
});

test('jobs:remind-tomorrow rejects a bad --date', function () {
    $this->artisan('jobs:remind-tomorrow', ['--date' => '27/11/2026'])->assertFailed();
});

test('the reminder runs daily from the scheduler', function () {
    $events = collect(app(Schedule::class)->events());

    $reminder = $events->first(fn ($e) => str_contains($e->command ?? '', 'jobs:remind-tomorrow'));

    expect($reminder)->not->toBeNull()
        ->and($reminder->expression)->toBe('0 7 * * *');
});

test('the daily log channel keeps 7 days of files', function () {
    expect(config('logging.channels.daily.driver'))->toBe('daily')
        ->and((int) config('logging.channels.daily.max_files'))->toBe(7);
});

// ---------- public demo protection ----------

test('the shared demo account cannot change its password or delete itself', function () {
    config(['jobdesk.demo_mode' => true]);
    $demo = User::factory()->create(['email' => config('jobdesk.demo_email'), 'password' => 'jobdesk-demo']);

    $this->actingAs($demo)->from('/profile')->put('/password', [
        'current_password' => 'jobdesk-demo', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
    ])->assertSessionHasErrorsIn('updatePassword', 'password');

    $this->actingAs($demo)->from('/profile')->patch('/profile', ['name' => 'X', 'email' => 'evil@example.com'])
        ->assertSessionHasErrors('email');

    $this->actingAs($demo)->from('/profile')->delete('/profile', ['password' => 'jobdesk-demo'])
        ->assertSessionHasErrorsIn('userDeletion', 'password');

    auth()->logout(); // forgot-password is a guest page
    $this->post('/forgot-password', ['email' => config('jobdesk.demo_email')])->assertSessionHasErrors('email');

    $demo->refresh();
    expect($demo->email)->toBe(config('jobdesk.demo_email'))
        ->and(password_verify('jobdesk-demo', $demo->password))->toBeTrue();
});

test('demo:reset restores the sample data and removes visitor accounts', function () {
    config(['jobdesk.demo_mode' => true]);
    User::factory()->count(2)->create();
    Customer::factory()->count(3)->create();

    $this->artisan('demo:reset')->assertSuccessful();

    expect(User::count())->toBe(1)
        ->and(User::first()->email)->toBe(config('jobdesk.demo_email'))
        ->and(Customer::count())->toBe(10)
        ->and(ServiceJob::count())->toBe(40);
});

test('demo:reset refuses to run when demo mode is off', function () {
    config(['jobdesk.demo_mode' => false]);
    $user = User::factory()->create();

    $this->artisan('demo:reset')->assertFailed();
    expect(User::find($user->id))->not->toBeNull();
});
