<?php

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;

function ownerWithCustomer(): array
{
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create(['name' => 'Olivia Bennett']);

    return [$user, $customer];
}

test('guests cannot open the jobs pages', function () {
    $this->get('/jobs')->assertRedirect('/login');
    $this->get('/jobs/create')->assertRedirect('/login');
});

test('a user can create a job for their own customer', function () {
    [$user, $customer] = ownerWithCustomer();

    $this->actingAs($user)->get('/jobs/create')->assertOk()->assertSee('Olivia Bennett');

    $response = $this->actingAs($user)->post('/jobs', [
        'customer_id' => $customer->id,
        'title' => 'Fix leaking faucet',
        'status' => 'scheduled',
        'price' => '149.99',
        'scheduled_at' => '2026-12-01T10:30',
        'notes' => 'Gate code 1234',
    ]);

    $job = ServiceJob::firstWhere('title', 'Fix leaking faucet');
    expect($job)->not->toBeNull()
        ->and($job->status)->toBe(JobStatus::Scheduled)
        ->and($job->price)->toBe('149.99')
        ->and($job->scheduled_at->format('Y-m-d H:i'))->toBe('2026-12-01 10:30');
    $response->assertRedirect(route('jobs.show', $job));
});

test('the job form shows validation errors', function () {
    [$user] = ownerWithCustomer();

    $this->actingAs($user)->from('/jobs/create')
        ->post('/jobs', ['title' => '', 'status' => 'paid', 'price' => '-5'])
        ->assertRedirect('/jobs/create')
        ->assertSessionHasErrors(['customer_id', 'title', 'status', 'price']);

    // and the errors are printed on the form after the redirect
    $this->actingAs($user)->from('/jobs/create')->followingRedirects()
        ->post('/jobs', ['title' => '', 'status' => 'paid', 'price' => '-5'])
        ->assertOk()
        ->assertSee('Please fix the 4 errors below.')
        ->assertSee('The title field is required.');
});

test('a scheduled job needs a date', function () {
    [$user, $customer] = ownerWithCustomer();

    $this->actingAs($user)->post('/jobs', [
        'customer_id' => $customer->id, 'title' => 'No date', 'status' => 'scheduled',
    ])->assertSessionHasErrors(['scheduled_at']);
});

test('a job cannot be attached to another user\'s customer', function () {
    [$user] = ownerWithCustomer();
    $foreignCustomer = Customer::factory()->create();

    $this->actingAs($user)->post('/jobs', [
        'customer_id' => $foreignCustomer->id, 'title' => 'Sneaky', 'status' => 'quote',
    ])->assertSessionHasErrors(['customer_id']);

    expect(ServiceJob::count())->toBe(0);
});

test('a user can view, update and delete their own job', function () {
    [$user, $customer] = ownerWithCustomer();
    $job = ServiceJob::factory()->for($customer)->create(['title' => 'Old title']);

    $this->actingAs($user)->get("/jobs/{$job->id}")->assertOk()->assertSee('Old title')->assertSee('Olivia Bennett');
    $this->actingAs($user)->get("/jobs/{$job->id}/edit")->assertOk();

    $this->actingAs($user)->patch("/jobs/{$job->id}", [
        'customer_id' => $customer->id, 'title' => 'New title', 'status' => 'done', 'price' => 200,
    ])->assertRedirect(route('jobs.show', $job));

    expect($job->fresh())
        ->title->toBe('New title')
        ->status->toBe(JobStatus::Done);

    $this->actingAs($user)->delete("/jobs/{$job->id}")->assertRedirect('/jobs');
    expect(ServiceJob::find($job->id))->toBeNull();
});

test('another user is blocked from my job with a 404', function () {
    [, $customer] = ownerWithCustomer();
    $job = ServiceJob::factory()->for($customer)->create(['title' => 'Top secret job']);
    $intruder = User::factory()->create();
    Customer::factory()->for($intruder)->create();

    $this->actingAs($intruder)->get("/jobs/{$job->id}")->assertNotFound()->assertDontSee('Top secret job');
    $this->actingAs($intruder)->get("/jobs/{$job->id}/edit")->assertNotFound();
    $this->actingAs($intruder)->patch("/jobs/{$job->id}", ['title' => ''])->assertNotFound();
    $this->actingAs($intruder)->delete("/jobs/{$job->id}")->assertNotFound();
    $this->actingAs($intruder)->get('/jobs')->assertDontSee('Top secret job');

    expect(ServiceJob::find($job->id))->not->toBeNull();
});

test('jobs can be searched by title and by customer name', function () {
    [$user, $customer] = ownerWithCustomer();
    $other = Customer::factory()->for($user)->create(['name' => 'Marcus Reed']);
    ServiceJob::factory()->for($customer)->create(['title' => 'Replace water heater']);
    ServiceJob::factory()->for($other)->create(['title' => 'AC tune-up']);

    $this->actingAs($user)->get('/jobs?q=heater')
        ->assertOk()->assertSee('Replace water heater')->assertDontSee('AC tune-up');

    $this->actingAs($user)->get('/jobs?q=marcus')
        ->assertOk()->assertSee('AC tune-up')->assertDontSee('Replace water heater');
});

test('jobs can be filtered by status and sorted by date', function () {
    [$user, $customer] = ownerWithCustomer();
    ServiceJob::factory()->for($customer)->scheduled(now()->addDays(1))->create(['title' => 'Early visit']);
    ServiceJob::factory()->for($customer)->scheduled(now()->addDays(5))->create(['title' => 'Late visit']);
    ServiceJob::factory()->for($customer)->status(JobStatus::Done)->create(['title' => 'Finished thing']);

    $this->actingAs($user)->get('/jobs?status=scheduled&sort=asc')
        ->assertOk()
        ->assertDontSee('Finished thing')
        ->assertSeeInOrder(['Early visit', 'Late visit']);

    $this->actingAs($user)->get('/jobs?status=scheduled&sort=desc')
        ->assertSeeInOrder(['Late visit', 'Early visit']);
});

test('jobs are paginated 10 per page and the links keep the search', function () {
    [$user, $customer] = ownerWithCustomer();
    ServiceJob::factory()->for($customer)->count(13)->create(['status' => JobStatus::Quote]);

    $response = $this->actingAs($user)->get('/jobs?q=Olivia&status=quote');

    $response->assertOk();
    expect($response->viewData('jobs')->count())->toBe(10)
        ->and($response->viewData('jobs')->total())->toBe(13);

    $response->assertSee('page 1 of 2');

    // page-2 link keeps q and status
    $response->assertSee('q=Olivia', false)->assertSee('status=quote', false)->assertSee('page=2', false);

    $page2 = $this->actingAs($user)->get('/jobs?q=Olivia&status=quote&page=2');
    expect($page2->viewData('jobs')->count())->toBe(3);
});

test('invalid filter values are rejected', function () {
    [$user] = ownerWithCustomer();

    $this->actingAs($user)->get('/jobs?status=hacked')->assertSessionHasErrors('status');
});
