<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\StorageMonitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class StorageMonitorController extends Controller
{
    public function __construct(private readonly StorageMonitor $storageMonitor)
    {
    }

    public function index(): View
    {
        return view('system.storage.index', [
            'storage' => $this->storageMonitor->summary(),
        ]);
    }

    public function downloadInventory(): StreamedResponse
    {
        $entries = $this->storageMonitor->entries();

        AuditLogger::record('storage_inventory_exported', 'Storage media inventory exported by supervisor.', null, [
            'image_count' => $entries->count(),
        ]);

        return response()->streamDownload(function () use ($entries): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Media Type',
                'Record Type',
                'Record ID',
                'Original Name',
                'Stored Path',
                'MIME Type',
                'Captured / Created At',
                'File Size (bytes)',
                'Database Copy (bytes)',
                'File Available',
            ]);

            foreach ($entries as $entry) {
                $path = $entry['path'] ?? null;
                $fileAvailable = filled($path) && $this->publicFileExists($path);

                fputcsv($handle, [
                    $entry['label'] ?? '',
                    $entry['record_type'] ?? '',
                    $entry['record_id'] ?? '',
                    $entry['original_name'] ?? '',
                    $path ?? '',
                    $entry['mime_type'] ?? '',
                    $this->dateValue($entry['captured_at'] ?? null),
                    $fileAvailable ? $this->publicFileSize($path) : 0,
                    strlen((string) ($entry['image_data'] ?? '')),
                    $fileAvailable ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, 'slsu-bc-patrol-storage-inventory-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadMediaBackup(): BinaryFileResponse|RedirectResponse
    {
        if (! class_exists(ZipArchive::class)) {
            return back()->with('error', 'Media backup is unavailable because ZIP support is not enabled on the server.');
        }

        $temporaryPath = tempnam(storage_path('app'), 'slsu-bc-media-backup-');

        if ($temporaryPath === false) {
            return back()->with('error', 'The media backup could not be prepared. Please try again.');
        }

        $archive = new ZipArchive();
        $opened = $archive->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            @unlink($temporaryPath);

            return back()->with('error', 'The media backup could not be created. Please try again.');
        }

        $manifestHandle = fopen('php://temp', 'w+b');
        fputcsv($manifestHandle, [
            'Media Type',
            'Record Type',
            'Record ID',
            'Original Name',
            'Archive Path',
            'Captured / Created At',
            'Source',
        ]);

        $added = 0;

        foreach ($this->storageMonitor->entries() as $index => $entry) {
            $contents = $this->storageMonitor->contentsFor($entry);

            if ($contents === null) {
                continue;
            }

            $archivePath = $this->archivePath($entry, $index);
            $archive->addFromString($archivePath, $contents);
            fputcsv($manifestHandle, [
                $entry['label'] ?? '',
                $entry['record_type'] ?? '',
                $entry['record_id'] ?? '',
                $entry['original_name'] ?? '',
                $archivePath,
                $this->dateValue($entry['captured_at'] ?? null),
                $entry['source'] ?? 'system storage',
            ]);
            $added++;
        }

        rewind($manifestHandle);
        $manifest = stream_get_contents($manifestHandle) ?: '';
        fclose($manifestHandle);
        $archive->addFromString('manifest.csv', $manifest);
        $archive->close();

        AuditLogger::record('media_backup_downloaded', 'Stored patrol and incident media backup downloaded by supervisor.', null, [
            'image_count' => $added,
        ]);

        return response()
            ->download($temporaryPath, 'slsu-bc-patrol-media-backup-'.now()->format('Y-m-d').'.zip', [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }

    private function publicFileExists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    private function publicFileSize(?string $path): int
    {
        if (! $this->publicFileExists($path)) {
            return 0;
        }

        try {
            return max(0, (int) \Illuminate\Support\Facades\Storage::disk('public')->size($path));
        } catch (\Throwable) {
            return 0;
        }
    }

    private function archivePath(array $entry, int $index): string
    {
        $source = Str::slug((string) ($entry['label'] ?? 'media')) ?: 'media';
        $name = basename((string) ($entry['original_name'] ?? ''));

        if ($name === '' || $name === '.' || $name === DIRECTORY_SEPARATOR) {
            $name = basename((string) ($entry['path'] ?? 'media-'.$index.'.jpg'));
        }

        return $source.'/'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT).'-'.$name;
    }

    private function dateValue(mixed $value): string
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) ($value ?? '');
    }
}
