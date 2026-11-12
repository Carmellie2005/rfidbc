<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Checkpoint;
use App\Models\Guard;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupervisorIncidentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_supervisor_can_create_a_report_with_optional_evidence_photo(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-11-12 12:00:00', config('app.timezone')));

        $supervisor = User::factory()->create(['role' => 'admin']);
        $checkpoint = Checkpoint::create([
            'code' => 'CP-SUP-01',
            'name' => 'Main Gate',
            'location' => 'Campus entrance',
            'device_uid' => 'ESP32-SUP-01',
            'status' => 'active',
        ]);

        $this
            ->actingAs($supervisor)
            ->get(route('incidents.create'))
            ->assertOk()
            ->assertSee('New Supervisor Report')
            ->assertSee('Upload evidence photos');

        $this
            ->actingAs($supervisor)
            ->post(route('incidents.store'), [
                'category' => 'Facility Issue',
                'priority' => 'normal',
                'location' => 'Main Gate entrance',
                'checkpoint_id' => $checkpoint->id,
                'incident_at' => '2026-11-12T10:30',
                'description' => 'Supervisor found a damaged gate latch that was not included in the guard report.',
                'incident_camera_images' => [UploadedFile::fake()->image('gate-latch.jpg', 320, 240)],
            ])
            ->assertRedirect(route('incidents.index'))
            ->assertSessionHas('status', 'Supervisor report created successfully.');

        $incident = IncidentReport::with('images')->latest('id')->firstOrFail();

        $this->assertNull($incident->guard_id);
        $this->assertNull($incident->patrol_log_id);
        $this->assertSame($checkpoint->id, $incident->checkpoint_id);
        $this->assertSame('Facility Issue', $incident->category);
        $this->assertSame('normal', $incident->priority);
        $this->assertCount(1, $incident->images);
        $this->assertSame('supervisor_upload', $incident->images->first()->source);
        Storage::disk('public')->assertExists($incident->images->first()->image_path);
        $this->assertTrue(AuditLog::where('action', 'incident_supervisor_created')->exists());
    }

    public function test_guard_cannot_open_or_submit_a_supervisor_report(): void
    {
        $guardUser = User::factory()->create(['role' => 'guard']);

        $this
            ->actingAs($guardUser)
            ->get(route('incidents.create'))
            ->assertForbidden();

        $this
            ->actingAs($guardUser)
            ->post(route('incidents.store'), [])
            ->assertForbidden();
    }

    public function test_incident_list_can_filter_supervisor_reports(): void
    {
        $supervisor = User::factory()->create(['role' => 'admin']);
        $guard = Guard::create([
            'employee_no' => 'BCP-SUP-01',
            'name' => 'Patrol Guard',
            'email' => 'patrol.guard@example.com',
            'rfid_uid' => 'RFID-SUP-01',
            'status' => 'active',
        ]);

        $supervisorReport = $this->createIncidentReport([
            'description' => 'Supervisor-only report description.',
        ]);
        $guardReport = $this->createIncidentReport([
            'guard_id' => $guard->id,
            'patrol_log_id' => null,
            'description' => 'Guard patrol report description.',
        ]);

        $this
            ->actingAs($supervisor)
            ->get(route('incidents.index', ['source' => 'supervisor']))
            ->assertOk()
            ->assertSee($supervisorReport->description)
            ->assertDontSee($guardReport->description);
    }

    private function createIncidentReport(array $overrides = []): IncidentReport
    {
        return IncidentReport::create([
            'patrol_log_id' => null,
            'guard_id' => null,
            'checkpoint_id' => null,
            'title' => 'Supervisor Report',
            'incident_type' => 'Facility Issue',
            'category' => 'Facility Issue',
            'priority' => 'normal',
            'severity' => 'medium',
            'location' => 'Campus',
            'incident_at' => now(),
            'occurred_at' => now(),
            'reported_at' => now(),
            'description' => 'Supervisor report description.',
            'status' => 'submitted',
            ...$overrides,
        ]);
    }
}
