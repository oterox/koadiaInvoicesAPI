<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

uses(RefreshDatabase::class);

test('register endpoint creates user', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    
    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'token', 'user']);
    
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('login endpoint returns token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('newpassword123'),
    ]);
    
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'token', 'user']);
});

test('forgot password sends email', function () {
    \Illuminate\Support\Facades\Notification::fake();
    
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'test@example.com',
    ]);
    
    $response->assertStatus(200)
        ->assertJson(['success' => true]);
    
    \Illuminate\Support\Facades\Notification::assertSentTo(
        [$user], \Illuminate\Auth\Notifications\ResetPassword::class
    );
});

test('reset password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    $token = Password::createToken($user);
    
    $response = $this->postJson('/api/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    
    $response->assertStatus(200)
        ->assertJson(['success' => true]);
    
    expect(Auth::attempt([
        'email' => 'test@example.com',
        'password' => 'newpassword123',
    ]))->toBeTrue();
});

test('logout endpoint revokes token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('newpassword123'),
    ]);
    
    $token = $user->createToken('test')->plainTextToken;
    
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/logout');
    
    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('register fails with duplicate email', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    $response = $this->postJson('/api/register', [
        'name' => 'Test User 2',
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    
    $response->assertStatus(422);
});

test('login fails with wrong password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('newpassword123'),
    ]);
    
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);
    
    $response->assertStatus(401);
});

test('forgot password with nonexistent email returns success', function () {
    \Illuminate\Support\Facades\Notification::fake();
    
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'noexiste@example.com',
    ]);
    
    $response->assertStatus(422);
    \Illuminate\Support\Facades\Notification::assertNothingSent();
});

test('reset password with invalid token fails', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    $response = $this->postJson('/api/reset-password', [
        'email' => 'test@example.com',
        'token' => 'invalid-token',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    
    $response->assertStatus(422);
});

test('logout fails without token', function () {
    $response = $this->postJson('/api/logout');
    $response->assertStatus(401);
});

test('logout fails with invalid token', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalidtoken')
        ->postJson('/api/logout');
    
    $response->assertStatus(401);
});
