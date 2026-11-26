<?php

use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\User;

test('guests are sent to the login page', function () {
    $this->get('/customers')->assertRedirect('/login');
});

test('a user only sees their own customers', function () {
    $user = User::factory()->create();
    Customer::factory()->for($user)->create(['name' => 'Mine Alpha']);
    Customer::factory()->create(['name' => 'Someone Else Beta']);

    $this->actingAs($user)->get('/customers')
        ->assertOk()
        ->assertSee('Mine Alpha')
        ->assertDontSee('Someone Else Beta');
});

test('customers can be searched by name, phone or email', function () {
    $user = User::factory()->create();
    Customer::factory()->for($user)->create(['name' => 'Olivia Bennett', 'phone' => '(555) 010-1111']);
    Customer::factory()->for($user)->create(['name' => 'Marcus Reed', 'phone' => '(555) 010-2222']);

    $this->actingAs($user)->get('/customers?q=olivia')->assertSee('Olivia Bennett')->assertDontSee('Marcus Reed');
    $this->actingAs($user)->get('/customers?q=2222')->assertSee('Marcus Reed')->assertDontSee('Olivia Bennett');
});

test('a user can create a customer', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/customers', [
        'name' => 'Hannah Cole',
        'phone' => '(555) 010-7782',
        'email' => 'hannah@example.com',
        'address' => '1520 Birch St',
    ]);

    $customer = Customer::firstWhere('name', 'Hannah Cole');
    expect($customer)->not->toBeNull()
        ->and($customer->user_id)->toBe($user->id);
    $response->assertRedirect(route('customers.show', $customer));
});

test('creating a customer validates the input', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/customers/create')
        ->post('/customers', ['name' => '', 'email' => 'not-an-email', 'phone' => 'abc'])
        ->assertRedirect('/customers/create')
        ->assertSessionHasErrors(['name', 'email', 'phone']);

    expect(Customer::count())->toBe(0);
});

test('a user can view, update and delete their own customer', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();
    ServiceJob::factory()->for($customer)->count(2)->create();

    $this->actingAs($user)->get("/customers/{$customer->id}")->assertOk()->assertSee($customer->name);
    $this->actingAs($user)->get("/customers/{$customer->id}/edit")->assertOk();

    $this->actingAs($user)->patch("/customers/{$customer->id}", ['name' => 'Renamed Customer'])
        ->assertRedirect(route('customers.show', $customer));
    expect($customer->fresh()->name)->toBe('Renamed Customer');

    $this->actingAs($user)->delete("/customers/{$customer->id}")->assertRedirect('/customers');
    expect(Customer::find($customer->id))->toBeNull()
        ->and(ServiceJob::count())->toBe(0); // jobs removed by FK cascade
});

test('another user cannot see or change my customer (404)', function () {
    $owner = User::factory()->create();
    $customer = Customer::factory()->for($owner)->create(['name' => 'Private Person']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get("/customers/{$customer->id}")->assertNotFound()->assertDontSee('Private Person');
    $this->actingAs($intruder)->get("/customers/{$customer->id}/edit")->assertNotFound();
    $this->actingAs($intruder)->patch("/customers/{$customer->id}", ['name' => 'Hacked'])->assertNotFound();
    $this->actingAs($intruder)->delete("/customers/{$customer->id}")->assertNotFound();

    expect($customer->fresh()->name)->toBe('Private Person');
});

test('an intruder gets 404 even with invalid data (no validation leak)', function () {
    $customer = Customer::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/customers/{$customer->id}", ['name' => ''])
        ->assertNotFound();
});
