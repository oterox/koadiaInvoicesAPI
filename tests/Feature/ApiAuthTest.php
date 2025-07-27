<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_endpoint_creates_user()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'token', 'user']);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_login_endpoint_returns_token()
    {
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
    }

    public function test_forgot_password_sends_email()
    {
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
    }

    public function test_reset_password_with_valid_token()
    {
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
        $this->assertTrue(Auth::attempt([
            'email' => 'test@example.com',
            'password' => 'newpassword123',
        ]));
    }

    public function test_logout_endpoint_revokes_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('newpassword123'),
        ]);
        $token = $user->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_register_fails_with_duplicate_email()
    {
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
    }

    public function test_login_fails_with_wrong_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('newpassword123'),
        ]);
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(401);
    }

    public function test_forgot_password_with_nonexistent_email_returns_success()
    {
        \Illuminate\Support\Facades\Notification::fake();
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'noexiste@example.com',
        ]);
        $response->assertStatus(422);
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_reset_password_with_invalid_token_fails()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $response = $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertStatus(422);
    }

    public function test_logout_fails_without_token()
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }

    public function test_logout_fails_with_invalid_token()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalidtoken')
            ->postJson('/api/logout');
        $response->assertStatus(401);
    }
}
