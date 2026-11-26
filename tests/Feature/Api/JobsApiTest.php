<?php

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'owner@example.com', 'password' => 'secret-pass-123']);
    $this->customer = Customer::factory()->for($this->user)->create(['name' => 'Olivia Bennett']);
});

// ---------- tokens ----------

test('a token is issued for correct credentials', function () {
    $response = $this->postJson('/api/tokens', [
        'email' => 'owner@example.com', 'password' => 'secret-pass-123', 'device_name' => 'tests',
    ]);

    $response->assertCreated()->assertJsonStructure(['token', 'token_type', 'expires_at']);
    expect($this->user->tokens()->count())->toBe(1);

    // the token really works
    $this->withToken($response->json('token'))->getJson('/api/jobs')->assertOk();
});

test('wrong credentials give a 422 JSON error', function () {
    $this->postJson('/api/tokens', [
        'email' => 'owner@example.com', 'password' => 'wrong', 'device_name' => 'tests',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('a token can be revoked', function () {
    $token = $this->user->createToken('tests', ['jobs:read', 'jobs:write'])->plainTextToken;

    $this->withToken($token)->deleteJson('/api/tokens/current')->assertNoContent();
    expect($this->user->tokens()->count())->toBe(0);
});

test('the jobs API requires a token', function () {
    $this->getJson('/api/jobs')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    $this->postJson('/api/jobs', [])->assertUnauthorized();
    $this->patchJson('/api/jobs/1/status', ['status' => 'done'])->assertUnauthorized();
});

test('a read-only token cannot create jobs (403)', function () {
    Sanctum::actingAs($this->user, ['jobs:read']);

    $this->getJson('/api/jobs')->assertOk();
    $this->postJson('/api/jobs', [
        'customer_id' => $this->customer->id, 'title' => 'x', 'status' => 'quote',
    ])->assertForbidden()->assertJson(['message' => 'This action is not allowed.']);
});

// ---------- GET /api/jobs ----------

test('jobs list is paginated and only shows my jobs', function () {
    Sanctum::actingAs($this->user, ['jobs:read']);
    ServiceJob::factory()->for($this->customer)->count(12)->create();
    ServiceJob::factory()->count(3)->create(['title' => 'Not mine']);

    $response = $this->getJson('/api/jobs');

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonStructure([
            'data' => [['id', 'title', 'status', 'status_label', 'price', 'scheduled_at', 'notes', 'customer' => ['id', 'name'], 'links']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'total'],
        ]);

    expect(collect($response->json('data'))->pluck('title'))->not->toContain('Not mine')
        ->and($response->json('meta'))->not->toHaveKey('links'); // no HTML pager labels in the API
});

test('jobs list supports ?search= and ?status=', function () {
    Sanctum::actingAs($this->user, ['jobs:read']);
    ServiceJob::factory()->for($this->customer)->scheduled()->create(['title' => 'Replace water heater']);
    ServiceJob::factory()->for($this->customer)->create(['title' => 'Water heater quote', 'status' => JobStatus::Quote]);
    ServiceJob::factory()->for($this->customer)->scheduled()->create(['title' => 'AC tune-up']);

    $this->getJson('/api/jobs?search=heater&status=scheduled')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Replace water heater');

    // search by customer name, and the next-page link keeps the filters
    $response = $this->getJson('/api/jobs?search=olivia&per_page=1');
    $response->assertJsonPath('meta.total', 3);
    expect($response->json('links.next'))->toContain('search=olivia')->toContain('per_page=1');
});

test('invalid list filters give a 422', function () {
    Sanctum::actingAs($this->user, ['jobs:read']);

    $this->getJson('/api/jobs?status=paid&per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'per_page']);
});

// ---------- GET /api/jobs/{id} ----------

test('I can read my own job', function () {
    Sanctum::actingAs($this->user, ['jobs:read']);
    $job = ServiceJob::factory()->for($this->customer)->create(['title' => 'Mine', 'price' => 99.5]);

    $this->getJson("/api/jobs/{$job->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $job->id)
        ->assertJsonPath('data.price', 99.5)
        ->assertJsonPath('data.customer.name', 'Olivia Bennett');
});

test('another user\'s job returns a clean 404', function () {
    Sanctum::actingAs($this->user, ['jobs:read', 'jobs:write']);
    $foreign = ServiceJob::factory()->create();

    $this->getJson("/api/jobs/{$foreign->id}")->assertNotFound()->assertExactJson(['message' => 'Resource not found.']);
    $this->patchJson("/api/jobs/{$foreign->id}/status", ['status' => 'done'])->assertNotFound();
    $this->getJson('/api/jobs/999999')->assertNotFound()->assertExactJson(['message' => 'Resource not found.']);

    expect($foreign->fresh()->status)->toBe(JobStatus::Quote);
});

// ---------- POST /api/jobs ----------

test('I can create a job through the API', function () {
    Sanctum::actingAs($this->user, ['jobs:write']);

    $response = $this->postJson('/api/jobs', [
        'customer_id' => $this->customer->id,
        'title' => 'Install new thermostat',
        'status' => 'scheduled',
        'price' => 289,
        'scheduled_at' => '2026-12-02 09:00:00',
    ]);

    $response->assertCreated()
        ->assertHeader('Location')
        ->assertJsonPath('data.title', 'Install new thermostat')
        ->assertJsonPath('data.status', 'scheduled')
        ->assertJsonPath('data.customer.id', $this->customer->id);

    expect(ServiceJob::count())->toBe(1);
});

test('creating a job validates the input with clean JSON errors', function () {
    Sanctum::actingAs($this->user, ['jobs:write']);
    $foreignCustomer = Customer::factory()->create();

    $this->postJson('/api/jobs', [
        'customer_id' => $foreignCustomer->id,
        'title' => '',
        'status' => 'paid',
        'price' => 'abc',
    ])->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors'])
        ->assertJsonValidationErrors(['customer_id', 'title', 'status', 'price']);

    expect(ServiceJob::count())->toBe(0);
});

// ---------- PATCH /api/jobs/{id}/status ----------

test('I can update the status of my job', function () {
    Sanctum::actingAs($this->user, ['jobs:write']);
    $job = ServiceJob::factory()->for($this->customer)->scheduled()->create();

    $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'done'])
        ->assertOk()
        ->assertJsonPath('data.status', 'done');

    expect($job->fresh()->status)->toBe(JobStatus::Done);
});

test('status update validates the value', function () {
    Sanctum::actingAs($this->user, ['jobs:write']);
    $job = ServiceJob::factory()->for($this->customer)->create(); // quote, no date

    $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'paid'])
        ->assertUnprocessable()->assertJsonValidationErrors('status');

    $this->patchJson("/api/jobs/{$job->id}/status", [])
        ->assertUnprocessable()->assertJsonValidationErrors('status');

    // cannot become "scheduled" without a date
    $this->patchJson("/api/jobs/{$job->id}/status", ['status' => 'scheduled'])
        ->assertUnprocessable()->assertJsonValidationErrors('status');
});
