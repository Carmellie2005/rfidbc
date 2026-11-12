<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueRfidDeviceToken extends Command
{
    protected $signature = 'rfid:issue-device-token
                            {device_uid : The exact ESP32 device UID}
                            {--user= : User ID or email to own the token}
                            {--enrollment : Issue a token for the RFID enrollment reader}';

    protected $description = 'Issue a Sanctum bearer token for an ESP32 RFID device';

    public function handle(): int
    {
        $deviceUid = strtoupper(trim((string) $this->argument('device_uid')));

        if ($deviceUid === '') {
            $this->error('A device UID is required.');

            return self::FAILURE;
        }

        $owner = $this->resolveOwner();

        if (! $owner) {
            $this->error('No token owner was found. Create a supervisor account or pass --user.');

            return self::FAILURE;
        }

        $prefix = $this->option('enrollment') ? 'rfid-enrollment' : 'rfid-device';
        $abilities = $this->option('enrollment')
            ? ['device.enrollment']
            : ['device.scan', 'device.heartbeat'];
        $name = "{$prefix}:{$deviceUid}";

        $owner->tokens()->where('name', $name)->delete();

        $token = $owner->createToken($name, $abilities);

        $this->info("Token issued for {$deviceUid}.");
        $this->line('Copy this token into the matching ESP32 device configuration:');
        $this->line($token->plainTextToken);
        $this->warn('The full token is shown only now. Store it securely and never commit it to GitHub.');

        return self::SUCCESS;
    }

    private function resolveOwner(): ?User
    {
        if ($selector = $this->option('user')) {
            return User::query()
                ->where('id', $selector)
                ->orWhere('email', $selector)
                ->first();
        }

        return User::query()
            ->whereIn('role', ['admin', 'supervisor'])
            ->orderBy('id')
            ->first();
    }
}
