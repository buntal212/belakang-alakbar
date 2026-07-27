<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_access_protected_route_and_logout(): void
    {
        $user = User::create([
            'kode' => 'X00X',
            'username' => 'admin.test',
            'name' => 'Admin Test',
            'password' => Hash::make('rahasia-test'),
        ]);

        $login = $this->postJson('/api/v1/login', [
            'username' => 'admin.test',
            'password' => 'rahasia-test',
        ]);

        $login
            ->assertOk()
            ->assertJsonStructure(['user', 'menus', 'token'])
            ->assertJsonMissingPath('user.password');

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/test')
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logout berhasil']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/test')
            ->assertUnauthorized();
    }

    public function test_business_route_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/master/user/getlist')
            ->assertUnauthorized();
    }

    public function test_broadcast_test_requires_authentication(): void
    {
        $this->getJson('/api/v1/broadcast-test?pemilik=BENDAHARA')
            ->assertUnauthorized();
    }
}
