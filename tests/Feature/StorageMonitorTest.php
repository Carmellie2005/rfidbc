<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Guard;
use App\Models\PatrolLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_view_storage_summary_and_download_inventory(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create([
            'name' => 'Storage Supervisor',
            'role' => 'admin',
            'username' => 'storage-supervisor',
        ]);
        $guardUser = User::factory()->create(['role' => 'guard']);
        $guard = Guard::create([
            'user_id' => $guardUser->id,
            'employee_no' => 'SG-STORAGE',
            'name' => 'Storage Guard',
            'rfid_uid' => 'RFID-STORAGE',
            'shift' => 'Day Shift',
            'status' => 'active',
        ]);
        $checkpoint = Checkpoint::create([
            'code' => 'CP-STORAGE',
            'name' => 'Storage Checkpoint',
            'location' => 'Campus',
            'device_uid' => 'ESP32-STORAGE',
            'status' => 'active',
        ]);
        $path = 'patrol-area-selfies/storage-test.jpg';
        $contents = str_repeat('image', 40);

        Storage::disk('public')->put($path, $contents);
        PatrolLog::create([
            'guard_id' => $guard->id,
            'checkpoint_id' => $checkpoint->id,
            'rfid_uid' => $guard->rfid_uid,
            'checkpoint_code' => $checkpoint->code,
            'rfid_status' => 'valid',
            'status' => 'valid',
            'area_selfie_path' => $path,
            'area_selfie_mime_type' => 'image/jpeg',
            'area_selfie_image_data' => base64_encode($contents),
            'area_selfie_captured_at' => now(),
            'scanned_at' => now(),
        ]);

        $this->actingAs($supervisor)
            ->get(route('storage-monitor.index'))
            ->assertOk()
            ->assertSee('Storage &amp; Backup Monitor', false)
            ->assertSee('Stored pictures')
            ->assertSee('media backup only');

        $inventoryResponse = $this->actingAs($supervisor)
            ->get(route('storage-monitor.inventory'));

        $inventoryResponse
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('Patrol selfie', $inventoryResponse->streamedContent());
        $this->assertStringContainsString($path, $inventoryResponse->streamedContent());
    }

    public function test_media_backup_can_be_downloaded_when_zip_support_is_available(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZIP support is not enabled in this PHP environment.');
        }

        Storage::fake('public');

        $supervisor = User::factory()->create([
            'role' => 'admin',
            'username' => 'backup-supervisor',
        ]);
        $guardUser = User::factory()->create(['role' => 'guard']);
        $guard = Guard::create([
            'user_id' => $guardUser->id,
            'employee_no' => 'SG-BACKUP',
            'name' => 'Backup Guard',
            'rfid_uid' => 'RFID-BACKUP',
            'shift' => 'Day Shift',
            'status' => 'active',
        ]);
        $checkpoint = Checkpoint::create([
            'code' => 'CP-BACKUP',
            'name' => 'Backup Checkpoint',
            'location' => 'Campus',
            'device_uid' => 'ESP32-BACKUP',
            'status' => 'active',
        ]);
        $path = 'patrol-area-selfies/backup-test.jpg';

        Storage::disk('public')->put($path, 'backup image');
        PatrolLog::create([
            'guard_id' => $guard->id,
            'checkpoint_id' => $checkpoint->id,
            'rfid_uid' => $guard->rfid_uid,
            'checkpoint_code' => $checkpoint->code,
            'rfid_status' => 'valid',
            'status' => 'valid',
            'area_selfie_path' => $path,
            'area_selfie_mime_type' => 'image/jpeg',
            'area_selfie_captured_at' => now(),
            'scanned_at' => now(),
        ]);

        $this->actingAs($supervisor)
            ->get(route('storage-monitor.media-backup'))
            ->assertDownload();
    }
}
