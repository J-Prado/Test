<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Flow A — Login / Auth
|--------------------------------------------------------------------------
| - Register with email + password
| - Login with valid AND invalid credentials
| - Access a protected resource without a token
*/

it('registers a new user with email and password', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Nueva Paciente',
        'email' => 'nueva@opcionyo.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    $this->assertDatabaseHas('users', ['email' => 'nueva@opcionyo.test']);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'dup@opcionyo.test']);

    $this->postJson('/api/register', [
        'name' => 'Otra',
        'email' => 'dup@opcionyo.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

it('logs in with valid credentials', function () {
    User::factory()->create([
        'email' => 'valid@opcionyo.test',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => 'valid@opcionyo.test',
        'password' => 'password123',
    ])->assertOk()->assertJsonStructure(['user', 'token']);
});

it('rejects login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'valid@opcionyo.test',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => 'valid@opcionyo.test',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

it('blocks access to a protected resource without a token', function () {
    $this->getJson('/api/user')->assertUnauthorized(); // 401
});

it('allows access to a protected resource with a valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

it('throttles repeated failed logins (brute-force protection)', function () {
    User::factory()->create(['email' => 'target@opcionyo.test', 'password' => 'password123']);

    // Route allows 6 attempts/min; the 7th must be blocked with 429.
    foreach (range(1, 6) as $attempt) {
        $this->postJson('/api/login', [
            'email' => 'target@opcionyo.test',
            'password' => 'nope',
        ]);
    }

    $this->postJson('/api/login', [
        'email' => 'target@opcionyo.test',
        'password' => 'nope',
    ])->assertStatus(429);
});
