<?php

namespace Tests\Feature;

use App\Events\SaldoUpdated;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtimeAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('broadcasting.default', 'reverb');
        config()->set('broadcasting.connections.reverb.key', 'test-reverb-key');
        config()->set('broadcasting.connections.reverb.secret', 'test-reverb-secret');
        config()->set('broadcasting.connections.reverb.app_id', 'test-reverb-app');

        Broadcast::channel('saldo.{jabatan}', function ($user, $jabatan) {
            return $user->canAccessSaldoJabatan($jabatan);
        });
    }

    public function test_saldo_event_uses_a_private_channel(): void
    {
        $event = new SaldoUpdated([
            'pemilik' => 'J000006',
            'data' => [],
        ]);

        $channel = $event->broadcastOn()[0];

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-saldo.J000006', $channel->name);
    }

    public function test_user_cannot_read_or_subscribe_to_another_jabatan_saldo(): void
    {
        $user = $this->makeUser(['jabatan' => 'J000006']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/saldo/get-list?jabatan=J000007')
            ->assertForbidden();

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-saldo.J000007',
        ])->assertForbidden();
    }

    public function test_owner_and_superadmin_can_authorize_their_saldo_channel(): void
    {
        $owner = $this->makeUser(['jabatan' => 'J000006']);

        Sanctum::actingAs($owner);

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-saldo.J000006',
        ])->assertOk()->assertJsonStructure(['auth']);

        $superadmin = $this->makeUser(['kode' => 'X00X']);

        Sanctum::actingAs($superadmin);

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-saldo.J000007',
        ])->assertOk()->assertJsonStructure(['auth']);
    }

    private function makeUser(array $attributes): User
    {
        $user = new User();
        $user->forceFill($attributes);

        return $user;
    }
}
