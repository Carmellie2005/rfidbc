<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Guard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfid_scan_requires_a_sanctum_token(): void
    {
        $response = $this->postJson(route('api.rfid-scan'), [
            'rfid_uid' => 'F33C8D37',
            'device_uid' => 'ESP32-IT-01',
        ]);

        $response->assertUnauthorized();
    }

    public function test_device_token_is_bound_to_the_declared_device_uid(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $headers = $this->rfidDeviceHeaders('ESP32-IT-01', ['device.scan'], $user);

        $response = $this->postJson(route('api.rfid-scan'), [
            'rfid_uid' => 'F33C8D37',
            'device_uid' => 'ESP32-MPC-01',
        ], $headers);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'The API token does not belong to this RFID device.',
            ]);
    }

    public function test_valid_device_token_can_submit_a_scan(): void
    {
        $guardUser = User::factory()->create([
            'role' => 'guard',
            'must_change_password' => false,
        ]);

        Guard::create([
            'user_id' => $guardUser->id,
            'employee_no' => 'SG-001',
            'name' => 'Test Guard',
            'rfid_uid' => 'F33C8D37',
            'status' => 'active',
        ]);

        Checkpoint::create([
            'code' => 'CP-IT-01',
            'name' => 'IT Building',
            'location' => 'IT Building',
            'device_uid' => 'ESP32-IT-01',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('api.rfid-scan'), [
            'rfid_uid' => 'F33C8D37',
            'device_uid' => 'ESP32-IT-01',
        ], $this->rfidDeviceHeaders('ESP32-IT-01', ['device.scan']));

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'pending_selfie');
    }
}
