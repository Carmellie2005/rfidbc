<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-blue-950">New Supervisor Report</h2>
                <p class="mt-1 text-sm text-blue-600">Record an issue discovered during supervisor monitoring.</p>
            </div>
            <a href="{{ route('incidents.index') }}" class="inline-flex h-10 items-center justify-center rounded-md border border-blue-200 px-4 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Back to Incident Reports
            </a>
        </div>
    </x-slot>

    <div class="py-5 sm:py-8">
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-semibold">Please review the form.</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">
                Use this form when a supervisor discovers a campus issue that was not included in the guard's incident report. This report is labeled <strong>Supervisor Report</strong> and does not require a new RFID scan or patrol log.
            </div>

            <form method="POST" action="{{ route('incidents.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <section class="rounded-md border border-blue-100 bg-white p-4 shadow-sm sm:p-6">
                    <div class="mb-5">
                        <h3 class="text-lg font-semibold text-blue-950">Incident details</h3>
                        <p class="mt-1 text-sm text-slate-500">Provide the basic information needed for review and follow-up.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="category" class="block text-sm font-semibold text-slate-800">Incident category <span class="text-red-600">*</span></label>
                            <select id="category" name="category" required class="mt-2 block h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Choose a category</option>
                                @foreach ($incidentCategories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="priority" class="block text-sm font-semibold text-slate-800">Priority <span class="text-red-600">*</span></label>
                            <select id="priority" name="priority" required class="mt-2 block h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach ($priorityOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="location" class="block text-sm font-semibold text-slate-800">Location or area <span class="text-red-600">*</span></label>
                            <input id="location" name="location" type="text" value="{{ old('location') }}" required maxlength="255" placeholder="Example: Main Gate entrance" class="mt-2 block h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('location')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="checkpoint_id" class="block text-sm font-semibold text-slate-800">Related checkpoint <span class="font-normal text-slate-500">(optional)</span></label>
                            <select id="checkpoint_id" name="checkpoint_id" class="mt-2 block h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Not linked to a checkpoint</option>
                                @foreach ($checkpoints as $checkpoint)
                                    <option value="{{ $checkpoint->id }}" @selected((string) old('checkpoint_id') === (string) $checkpoint->id)>{{ $checkpoint->name }} ({{ $checkpoint->code }})</option>
                                @endforeach
                            </select>
                            @error('checkpoint_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="incident_at" class="block text-sm font-semibold text-slate-800">Observed date and time <span class="text-red-600">*</span></label>
                            <input id="incident_at" name="incident_at" type="datetime-local" value="{{ old('incident_at', $defaultIncidentAt) }}" required class="mt-2 block h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:max-w-md">
                            @error('incident_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="description" class="block text-sm font-semibold text-slate-800">Description <span class="text-red-600">*</span></label>
                            <textarea id="description" name="description" rows="6" required maxlength="3000" placeholder="Describe what was observed and what may need attention." class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-md border border-blue-100 bg-white p-4 shadow-sm sm:p-6">
                    <div class="mb-5">
                        <h3 class="text-lg font-semibold text-blue-950">Evidence photo</h3>
                        <p class="mt-1 text-sm text-slate-500">Upload up to three photos that help document the issue. Photos are optional.</p>
                    </div>

                    <label for="incident_camera_images" class="block text-sm font-semibold text-slate-800">Upload evidence photos <span class="font-normal text-slate-500">(optional)</span></label>
                    <input id="incident_camera_images" name="incident_camera_images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="mt-2 block w-full rounded-md border border-slate-300 bg-white p-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-2 text-xs text-slate-500">Accepted formats: JPG, PNG, or WEBP. Maximum 12 MB per file.</p>
                    @error('incident_camera_images')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @foreach ($errors->get('incident_camera_images.*') as $messages)
                        @foreach ($messages as $message)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @endforeach
                    @endforeach
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('incidents.index') }}" class="inline-flex h-11 items-center justify-center rounded-md border border-blue-200 px-5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Cancel</a>
                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-md bg-blue-700 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Save Supervisor Report</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
