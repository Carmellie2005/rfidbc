<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-blue-950 dark:text-white">Storage &amp; Backup Monitor</h2>
            <p class="mt-1 text-sm text-blue-600 dark:text-slate-200">Picture storage, media inventory, and backup controls</p>
        </div>
    </x-slot>

    @php
        $formatBytes = function (?int $bytes): string {
            if ($bytes === null) {
                return 'Unavailable';
            }

            if ($bytes < 1024) {
                return $bytes.' B';
            }

            $units = ['KB', 'MB', 'GB', 'TB'];
            $value = (float) $bytes;
            $unit = 'B';

            foreach ($units as $nextUnit) {
                $value /= 1024;
                $unit = $nextUnit;

                if ($value < 1024) {
                    break;
                }
            }

            return number_format($value, $value >= 10 ? 1 : 2).' '.$unit;
        };

        $storageStatus = $storage['storageStatus'];
        $statusConfig = match ($storageStatus) {
            'warning' => [
                'label' => 'Storage warning',
                'class' => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-400/40',
                'dot' => 'bg-amber-500',
            ],
            'healthy' => [
                'label' => 'Storage healthy',
                'class' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-400/40',
                'dot' => 'bg-emerald-500',
            ],
            default => [
                'label' => 'Capacity unavailable',
                'class' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600',
                'dot' => 'bg-slate-400',
            ],
        };

        $usedPercent = $storage['serverUsedPercent'];
        $progressWidth = $usedPercent === null ? 0 : min(100, max(0, (float) $usedPercent));
    @endphp

    <div class="py-5 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-400/40 dark:bg-red-950/30 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Stored pictures</p>
                    <p class="mt-2 text-2xl font-bold text-blue-950 dark:text-white sm:text-3xl">{{ number_format($storage['imageCount']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Across patrol and incident records</p>
                </div>
                <div class="rounded-md border border-sky-100 bg-sky-50/60 p-4 shadow-sm dark:border-sky-400/30 dark:bg-slate-900 sm:p-5">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Media footprint</p>
                    <p class="mt-2 text-2xl font-bold text-sky-950 dark:text-white sm:text-3xl">{{ $formatBytes($storage['trackedMediaBytes']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">File and database copies</p>
                </div>
                <div class="rounded-md border border-emerald-100 bg-emerald-50/60 p-4 shadow-sm dark:border-emerald-400/30 dark:bg-slate-900 sm:p-5">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Server free space</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-950 dark:text-white sm:text-3xl">{{ $formatBytes($storage['serverFreeBytes']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Available on the storage volume</p>
                </div>
                <div class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Backup</p>
                    <p class="mt-2 text-2xl font-bold text-blue-950 dark:text-white sm:text-3xl">On demand</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Media ZIP with inventory manifest</p>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.65fr)]">
                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-blue-950 dark:text-white">Server storage usage</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">The warning appears when the storage volume reaches 80% usage.</p>
                        </div>
                        <span class="inline-flex w-fit items-center gap-2 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusConfig['class'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $statusConfig['dot'] }}" aria-hidden="true"></span>
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>

                    <div class="mt-5">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $usedPercent === null ? 'Capacity unavailable' : number_format((float) $usedPercent, 1).'% used' }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ $formatBytes($storage['serverUsedBytes']) }} of {{ $formatBytes($storage['serverTotalBytes']) }}</span>
                        </div>
                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $storageStatus === 'warning' ? 'bg-amber-500' : 'bg-blue-600' }}" style="width: {{ $progressWidth }}%"></div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-md bg-blue-50/70 p-3 dark:bg-slate-800">
                            <p class="text-[0.65rem] font-semibold uppercase text-slate-500 dark:text-slate-400">Stored file copies</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ number_format($storage['storedFileCount']) }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $formatBytes($storage['storedFileBytes']) }}</p>
                        </div>
                        <div class="rounded-md bg-blue-50/70 p-3 dark:bg-slate-800">
                            <p class="text-[0.65rem] font-semibold uppercase text-slate-500 dark:text-slate-400">Database copies</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $formatBytes($storage['databaseCopyBytes']) }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Recovery copies</p>
                        </div>
                        <div class="rounded-md bg-blue-50/70 p-3 dark:bg-slate-800">
                            <p class="text-[0.65rem] font-semibold uppercase text-slate-500 dark:text-slate-400">Last media saved</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $storage['lastMediaAt']?->timezone(config('app.timezone'))->format('M d, Y') ?? 'No media yet' }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $storage['lastMediaAt']?->timezone(config('app.timezone'))->format('h:i A') ?? '' }}</p>
                        </div>
                    </div>

                    @if ($storage['missingFileCount'] > 0)
                        <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:border-amber-400/40 dark:bg-amber-950/30 dark:text-amber-200">
                            {{ number_format($storage['missingFileCount']) }} record(s) reference a missing media file. The database copy may still be available.
                        </p>
                    @else
                        <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">No missing media files were found in the records.</p>
                    @endif
                </article>

                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                    <h3 class="font-semibold text-blue-950 dark:text-white">Backup controls</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Keep a downloadable copy of the system’s stored media.</p>

                    <div class="mt-5 space-y-3">
                        <a href="{{ route('storage-monitor.media-backup') }}" class="inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                            Download Media Backup (.zip)
                        </a>
                        <a href="{{ route('storage-monitor.inventory') }}" class="inline-flex w-full items-center justify-center rounded-md border border-blue-200 bg-white px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-900 dark:text-blue-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-900">
                            Download Inventory (.csv)
                        </a>
                    </div>

                    <p class="mt-4 text-xs leading-5 text-slate-500 dark:text-slate-400">
                        The ZIP contains patrol selfies, incident photos, saved evidence, checklist proof photos, and a manifest. It is a media backup only and does not replace a full database or server backup.
                    </p>
                </article>
            </section>

            <section class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-blue-950 dark:text-white">Stored media by type</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Records currently tracked by the storage monitor</p>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">No files are deleted automatically.</span>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($storage['categories'] as $category)
                        <div class="rounded-md border border-blue-100 bg-blue-50/50 p-3 dark:border-slate-700 dark:bg-slate-800">
                            <p class="truncate text-xs font-semibold uppercase text-blue-800 dark:text-blue-200">{{ $category['label'] }}</p>
                            <p class="mt-2 text-2xl font-bold text-blue-950 dark:text-white">{{ number_format($category['count']) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
