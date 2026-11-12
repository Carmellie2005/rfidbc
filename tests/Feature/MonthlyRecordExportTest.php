<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Guard;
use App\Models\IncidentReport;
use App\Models\PatrolLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use ZipArchive;

class MonthlyRecordExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_can_download_only_its_selected_month_patrol_logs_as_csv(): void
    {
        $guard = $this->createGuard('BCP-EXPORT-01', 'RFID-EXPORT-01');
        $otherGuard = $this->createGuard('BCP-EXPORT-02', 'RFID-EXPORT-02');

        $included = $this->createPatrolLog(
            $guard,
            Carbon::parse('2026-11-08 17:30:00', config('app.timezone')),
            'CP-EXPORT-INCLUDED',
        );
        $this->createPatrolLog(
            $guard,
            Carbon::parse('2026-10-31 17:30:00', config('app.timezone')),
            'CP-EXPORT-OLD',
        );
        $this->createPatrolLog(
            $otherGuard,
            Carbon::parse('2026-11-08 17:45:00', config('app.timezone')),
            'CP-EXPORT-OTHER',
        );

        $response = $this
            ->actingAs($guard->user)
            ->get(route('patrol-logs.export', ['month' => '2026-11', 'format' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('patrol-logs-2026-11.csv', (string) $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Patrol Log No.', $csv);
        $this->assertStringContainsString('CP-EXPORT-INCLUDED', $csv);
        $this->assertStringNotContainsString('CP-EXPORT-OLD', $csv);
        $this->assertStringNotContainsString('CP-EXPORT-OTHER', $csv);
        $this->assertStringNotContainsString('RFID-EXPORT-01', $csv);
        $this->assertStringNotContainsString('Area Selfie Captured', $csv);
        $this->assertStringNotContainsString('Selfie Latitude', $csv);
        $this->assertStringContainsString('BCP-EXPORT-01', $csv);
        $this->assertNotSame('', (string) $included->id);
    }

    public function test_supervisor_can_download_incident_report_excel_with_photo_columns(): void
    {
        $supervisor = User::factory()->create(['role' => 'admin']);
        $guard = $this->createGuard('BCP-EXPORT-03', 'RFID-EXPORT-03');

        $incident = $this->createIncidentReport(
            $guard,
            Carbon::parse('2026-11-09 18:00:00', config('app.timezone')),
            'Broken gate observed during patrol.',
        );

        $response = $this
            ->actingAs($supervisor)
            ->get(route('incidents.export', ['month' => '2026-11', 'format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('incident-reports-2026-11.xlsx', (string) $response->headers->get('content-disposition'));

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($file->getPathname()) === true);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $styles = $zip->getFromName('xl/styles.xml');
        $zip->close();

        $this->assertNotFalse($sheet);
        $this->assertStringContainsString('Broken gate observed during patrol.', $sheet);
        $this->assertStringContainsString('Photo 1 File', $sheet);
        $this->assertStringContainsString('Report Source', $sheet);
        $this->assertStringContainsString('Guard Patrol Report', $sheet);
        $this->assertStringContainsString('Incident Report No.', $sheet);
        $this->assertStringContainsString('IR-'.str_pad((string) $incident->id, 6, '0', STR_PAD_LEFT), $sheet);
        $this->assertStringContainsString('<c r="A1" s="1"', $sheet);

        $this->assertNotFalse($styles);
        $this->assertStringContainsString('FF6B7280', $styles);
        $this->assertStringContainsString('<cols>', $sheet);
        $this->assertStringContainsString('customWidth="1"', $sheet);
    }

    public function test_export_controls_are_available_on_the_records_pages(): void
    {
        $guard = $this->createGuard('BCP-EXPORT-04', 'RFID-EXPORT-04');
        $supervisor = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($guard->user)
            ->get(route('patrol-logs.index'))
            ->assertOk()
            ->assertSee('Monthly Records')
            ->assertSee('Download CSV')
            ->assertSee('Download Excel');

        $this
            ->actingAs($supervisor)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee('Monthly Records')
            ->assertSee('Download CSV')
            ->assertSee('Download Excel');
    }

    private function createGuard(string $employeeNo, string $rfidUid): Guard
    {
        $user = User::factory()->create([
            'role' => 'guard',
            'username' => strtolower($employeeNo),
        ]);

        return Guard::create([
            'user_id' => $user->id,
            'employee_no' => $employeeNo,
            'name' => $user->name,
            'email' => $user->email,
            'rfid_uid' => $rfidUid,
            'shift' => 'Day Shift',
            'status' => 'active',
        ]);
    }

    private function createPatrolLog(Guard $guard, Carbon $scannedAt, string $checkpointCode): PatrolLog
    {
        $checkpoint = Checkpoint::create([
            'code' => $checkpointCode,
            'name' => 'Checkpoint '.$checkpointCode,
            'location' => 'Campus',
            'device_uid' => 'ESP32-'.$checkpointCode,
            'status' => 'active',
        ]);

        return PatrolLog::create([
            'guard_id' => $guard->id,
            'checkpoint_id' => $checkpoint->id,
            'rfid_uid' => $guard->rfid_uid,
            'checkpoint_code' => $checkpoint->code,
            'rfid_status' => 'valid',
            'status' => 'valid',
            'scanned_at' => $scannedAt,
        ]);
    }

    private function createIncidentReport(Guard $guard, Carbon $incidentAt, string $description): IncidentReport
    {
        $patrolLog = $this->createPatrolLog($guard, $incidentAt, 'CP-EXPORT-INCIDENT');

        return IncidentReport::create([
            'patrol_log_id' => $patrolLog->id,
            'guard_id' => $guard->id,
            'checkpoint_id' => $patrolLog->checkpoint_id,
            'title' => 'Security Concern',
            'incident_type' => 'Security Concern',
            'category' => 'Property Damage / Vandalism',
            'priority' => 'high',
            'severity' => 'high',
            'location' => 'Campus',
            'incident_at' => $incidentAt,
            'reported_at' => $incidentAt->copy()->addMinutes(5),
            'description' => $description,
            'status' => 'submitted',
        ]);
    }
}
