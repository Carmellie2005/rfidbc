<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function rfidDeviceHeaders(string $deviceUid, array $abilities = ['device.scan', 'device.heartbeat'], ?User $owner = null): array
    {
        $owner ??= User::factory()->create(['role' => 'admin']);

        return [
            'Authorization' => 'Bearer '.$owner
                ->createToken('rfid-device:'.strtoupper($deviceUid), $abilities)
                ->plainTextToken,
        ];
    }

    protected function rfidEnrollmentHeaders(string $deviceUid, ?User $owner = null): array
    {
        $owner ??= User::factory()->create(['role' => 'admin']);

        return [
            'Authorization' => 'Bearer '.$owner
                ->createToken('rfid-enrollment:'.strtoupper($deviceUid), ['device.enrollment'])
                ->plainTextToken,
        ];
    }
}
