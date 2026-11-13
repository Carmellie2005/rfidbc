<?php

namespace App\Support;

use App\Models\ChecklistProofPhoto;
use App\Models\IncidentEvidence;
use App\Models\IncidentReportImage;
use App\Models\PatrolLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StorageMonitor
{
    public const WARNING_PERCENT = 80;

    /**
     * Return a storage summary for the supervisor dashboard.
     *
     * The application keeps media files on the public disk and also keeps a
     * database copy for recovery when a file is unavailable. The server disk
     * values are reported separately so the two storage locations are not
     * confused with one another.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $entries = $this->entries(false);
        $disk = Storage::disk('public');
        $uniquePaths = $entries
            ->pluck('path')
            ->filter()
            ->unique()
            ->values();

        $storedFileBytes = $uniquePaths->sum(fn (string $path): int => $this->fileSize($disk, $path));
        $databaseCopyBytes = $this->databaseCopyBytes();
        $missingFileCount = $entries
            ->filter(fn (array $entry): bool => filled($entry['path'] ?? null) && ! $this->diskHasFile($disk, $entry['path']))
            ->count();
        $server = $this->serverDiskSummary($disk);

        return [
            'imageCount' => $entries->count(),
            'storedFileCount' => $uniquePaths->count(),
            'storedFileBytes' => $storedFileBytes,
            'databaseCopyBytes' => $databaseCopyBytes,
            'trackedMediaBytes' => $storedFileBytes + $databaseCopyBytes,
            'missingFileCount' => $missingFileCount,
            'lastMediaAt' => $entries
                ->pluck('captured_at')
                ->filter()
                ->sortDesc()
                ->first(),
            'categories' => [
                [
                    'label' => 'Patrol selfies',
                    'count' => $entries->where('category', 'patrol_selfie')->count(),
                ],
                [
                    'label' => 'Incident photos',
                    'count' => $entries->where('category', 'incident_photo')->count(),
                ],
                [
                    'label' => 'Saved incident evidence',
                    'count' => $entries->where('category', 'incident_evidence')->count(),
                ],
                [
                    'label' => 'Checklist proof photos',
                    'count' => $entries->where('category', 'checklist_proof')->count(),
                ],
            ],
            ...$server,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function entries(bool $includeData = true): Collection
    {
        $entries = collect();

        PatrolLog::query()
            ->where(function ($query): void {
                $query
                    ->whereNotNull('area_selfie_path')
                    ->orWhereNotNull('area_selfie_image_data');
            })
            ->get(array_filter([
                'id',
                'area_selfie_path',
                'area_selfie_mime_type',
                $includeData ? 'area_selfie_image_data' : null,
                'area_selfie_captured_at',
                'scanned_at',
            ]))
            ->each(function (PatrolLog $patrolLog) use ($entries, $includeData): void {
                $entries->push([
                    'category' => 'patrol_selfie',
                    'label' => 'Patrol selfie',
                    'record_type' => 'patrol_log',
                    'record_id' => $patrolLog->id,
                    'path' => $patrolLog->area_selfie_path,
                    'original_name' => $patrolLog->area_selfie_path ? basename($patrolLog->area_selfie_path) : null,
                    'mime_type' => $patrolLog->area_selfie_mime_type,
                    'image_data' => $includeData ? $patrolLog->area_selfie_image_data : null,
                    'captured_at' => $patrolLog->area_selfie_captured_at ?? $patrolLog->scanned_at,
                ]);
            });

        IncidentReportImage::query()
            ->get(array_filter([
                'id',
                'incident_report_id',
                'image_path',
                'original_name',
                'mime_type',
                $includeData ? 'image_data' : null,
                'source',
                'created_at',
            ]))
            ->each(function (IncidentReportImage $image) use ($entries, $includeData): void {
                $entries->push([
                    'category' => 'incident_photo',
                    'label' => 'Incident photo',
                    'record_type' => 'incident_report',
                    'record_id' => $image->incident_report_id,
                    'path' => $image->image_path,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'image_data' => $includeData ? $image->image_data : null,
                    'source' => $image->source,
                    'captured_at' => $image->created_at,
                ]);
            });

        IncidentEvidence::query()
            ->get(array_filter([
                'id',
                'image_path',
                'original_name',
                'mime_type',
                $includeData ? 'image_data' : null,
                'captured_at',
            ]))
            ->each(function (IncidentEvidence $image) use ($entries, $includeData): void {
                $entries->push([
                    'category' => 'incident_evidence',
                    'label' => 'Saved incident evidence',
                    'record_type' => 'incident_evidence',
                    'record_id' => $image->id,
                    'path' => $image->image_path,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'image_data' => $includeData ? $image->image_data : null,
                    'captured_at' => $image->captured_at,
                ]);
            });

        ChecklistProofPhoto::query()
            ->get(array_filter([
                'id',
                'patrol_log_id',
                'image_path',
                'original_name',
                'mime_type',
                $includeData ? 'image_data' : null,
                'created_at',
            ]))
            ->each(function (ChecklistProofPhoto $image) use ($entries, $includeData): void {
                $entries->push([
                    'category' => 'checklist_proof',
                    'label' => 'Checklist proof photo',
                    'record_type' => 'checklist_proof',
                    'record_id' => $image->patrol_log_id,
                    'path' => $image->image_path,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'image_data' => $includeData ? $image->image_data : null,
                    'captured_at' => $image->created_at,
                ]);
            });

        return $entries;
    }

    private function databaseCopyBytes(): int
    {
        $patrolBytes = PatrolLog::query()
            ->where(function ($query): void {
                $query
                    ->whereNotNull('area_selfie_path')
                    ->orWhereNotNull('area_selfie_image_data');
            })
            ->selectRaw("COALESCE(SUM(LENGTH(COALESCE(area_selfie_image_data, ''))), 0) AS total")
            ->value('total');
        $incidentBytes = IncidentReportImage::query()
            ->selectRaw("COALESCE(SUM(LENGTH(COALESCE(image_data, ''))), 0) AS total")
            ->value('total');
        $evidenceBytes = IncidentEvidence::query()
            ->selectRaw("COALESCE(SUM(LENGTH(COALESCE(image_data, ''))), 0) AS total")
            ->value('total');
        $checklistBytes = ChecklistProofPhoto::query()
            ->selectRaw("COALESCE(SUM(LENGTH(COALESCE(image_data, ''))), 0) AS total")
            ->value('total');

        return (int) $patrolBytes + (int) $incidentBytes + (int) $evidenceBytes + (int) $checklistBytes;
    }

    public function contentsFor(array $entry): ?string
    {
        $disk = Storage::disk('public');
        $path = $entry['path'] ?? null;

        if (filled($path) && $this->diskHasFile($disk, $path)) {
            try {
                return $disk->get($path);
            } catch (\Throwable) {
                // Fall through to the database copy when the file cannot be read.
            }
        }

        $imageData = $entry['image_data'] ?? null;

        if (! is_string($imageData) || $imageData === '') {
            return null;
        }

        $decoded = base64_decode($imageData, true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * @return array<string, int|float|null|string>
     */
    private function serverDiskSummary(mixed $disk): array
    {
        try {
            $root = $disk->path('');
        } catch (\Throwable) {
            return [
                'serverTotalBytes' => null,
                'serverFreeBytes' => null,
                'serverUsedBytes' => null,
                'serverUsedPercent' => null,
                'storageStatus' => 'unavailable',
            ];
        }

        if (! is_string($root) || ! is_dir($root)) {
            return [
                'serverTotalBytes' => null,
                'serverFreeBytes' => null,
                'serverUsedBytes' => null,
                'serverUsedPercent' => null,
                'storageStatus' => 'unavailable',
            ];
        }

        $total = @disk_total_space($root);
        $free = @disk_free_space($root);

        if (! is_numeric($total) || ! is_numeric($free) || (float) $total <= 0) {
            return [
                'serverTotalBytes' => null,
                'serverFreeBytes' => null,
                'serverUsedBytes' => null,
                'serverUsedPercent' => null,
                'storageStatus' => 'unavailable',
            ];
        }

        $total = (int) $total;
        $free = max(0, min($total, (int) $free));
        $used = max(0, $total - $free);
        $percent = round(($used / $total) * 100, 1);

        return [
            'serverTotalBytes' => $total,
            'serverFreeBytes' => $free,
            'serverUsedBytes' => $used,
            'serverUsedPercent' => $percent,
            'storageStatus' => $percent >= self::WARNING_PERCENT ? 'warning' : 'healthy',
        ];
    }

    private function diskHasFile(mixed $disk, ?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        try {
            return $disk->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    private function fileSize(mixed $disk, string $path): int
    {
        if (! $this->diskHasFile($disk, $path)) {
            return 0;
        }

        try {
            return max(0, (int) $disk->size($path));
        } catch (\Throwable) {
            return 0;
        }
    }
}
