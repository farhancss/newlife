@props([
    'container',
    'showSectionHeader' => true,
])

@php
    $remaining = $container->remainingPhotoSlots();
    $cap = $container->photoCap();
    $canUpload = $container->acceptsPhotos();
    $exteriorPhotos = $container->photos->where('type', \App\Models\ContainerPhoto::TYPE_EXTERIOR)->values();
    $hubPhotos = $container->photos->where('type', \App\Models\ContainerPhoto::TYPE_HUB_INTAKE)->values();
@endphp

<div {{ $attributes->class(['flex h-full w-full flex-col rounded-2xl border border-gray-200 bg-white p-5 sm:p-6', 'mt-6' => ! $showSectionHeader]) }}>
    <div @class([
        'flex flex-col gap-4 sm:flex-row sm:items-start',
        'sm:justify-between' => $showSectionHeader,
        'sm:justify-end' => ! $showSectionHeader,
    ])>
        @if ($showSectionHeader)
            <div class="min-w-0 flex-1 pr-0 sm:pr-6">
                <h2 class="text-lg font-medium text-gray-600">Container photos</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Upload exterior photos of your container while it is being packed. Failure to upload photos may impact damage claim processing.
                </p>
            </div>
        @endif

        <div class="shrink-0 rounded-xl bg-[#F3F5FF] px-4 py-3 text-right">
            <p class="text-base font-medium text-brand-900">{{ $container->code }}</p>
            <p class="mt-0.5 text-xs text-gray-400">{{ $exteriorPhotos->count() }} of {{ $cap }} photos uploaded</p>
        </div>
    </div>

    @if ($remaining > 0)
        <div @class([
            'mt-5 flex flex-1 flex-col',
            'select-none opacity-70' => ! $canUpload,
        ])>
            @if (! $canUpload)
                <p class="mb-3 text-xs font-medium text-gray-500">
                    Photo uploads open while your container is being packed.
                </p>
            @endif

            @if ($canUpload)
            <div x-data="containerPhotoPicker({{ $remaining }})" class="flex flex-1 flex-col">
            @endif

            <p class="mb-3 text-sm text-gray-800">
                Add exterior photos (up to {{ $remaining }} more)
            </p>

            @if ($canUpload)
                <input
                    id="photos-{{ $container->id }}"
                    type="file"
                    accept="image/jpeg,image/png,image/jpg,.jpg,.jpeg,.png"
                    multiple
                    class="sr-only"
                    x-ref="fileInput"
                    x-on:change="addFiles($event)"
                />
            @endif

            <div class="flex flex-wrap gap-3">
                @foreach ($exteriorPhotos as $photo)
                    <div class="group relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer" class="block h-full w-full">
                            <img
                                src="{{ $photo->url() }}"
                                alt="{{ $photo->original_name ?? 'Container photo' }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </a>
                        @if ($canUpload)
                            <form action="{{ route('student.move-tracking.photos.destroy', [$container, $photo]) }}" method="POST"
                                class="absolute right-1 top-1">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow-sm hover:bg-red-50 hover:text-red-600"
                                    aria-label="Delete photo"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach

                @if ($canUpload)
                    <template x-for="item in pendingFiles" x-bind:key="item.id">
                        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                            <img
                                x-bind:src="item.preview"
                                x-bind:alt="item.file.name"
                                class="h-full w-full object-cover"
                            />
                            <button
                                type="button"
                                x-on:click="removePending(item.id)"
                                class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow-sm hover:bg-red-50 hover:text-red-600"
                                aria-label="Remove selected photo"
                            >
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>

                    <div x-show="canAddMore()" x-cloak>
                        <button
                            type="button"
                            x-on:click="$refs.fileInput.click()"
                            class="flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-gray-300 bg-white text-gray-500 transition hover:border-brand-300 hover:bg-brand-50/40"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span class="text-xs font-medium">Upload</span>
                        </button>
                    </div>
                @else
                    <div class="flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-gray-300 bg-gray-50 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span class="text-xs font-medium">Upload</span>
                    </div>
                @endif
            </div>

            <form
                id="upload-photos-{{ $container->id }}"
                action="{{ route('student.move-tracking.photos.store', $container) }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-auto pt-8"
                @if ($canUpload)
                x-on:submit.prevent="submitUpload($event)"
                @endif
            >
                @csrf
                <fieldset @disabled(! $canUpload) class="border-0 p-0">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <label class="flex items-start gap-2 text-sm text-gray-600">
                            <input
                                type="checkbox"
                                name="acknowledge"
                                value="1"
                                {{ $canUpload ? 'required' : '' }}
                                class="mt-0.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 disabled:cursor-not-allowed"
                            />
                            <span class="text-gray-800">I understand failure to upload photos may impact damage claim processing.</span>
                        </label>
                        <button
                            type="submit"
                            @if ($canUpload)
                            x-bind:disabled="pendingFiles.length === 0"
                            @else
                            disabled
                            @endif
                            class="inline-flex shrink-0 items-center justify-center rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 disabled:hover:bg-gray-300"
                        >
                            Upload photos
                        </button>
                    </div>
                </fieldset>
            </form>

            @if ($canUpload)
            </div>
            @endif
        </div>
    @else
        @if ($exteriorPhotos->isNotEmpty())
            <div class="mt-5 flex flex-wrap gap-3">
                @foreach ($exteriorPhotos as $photo)
                    <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer" class="block h-full w-full">
                            <img
                                src="{{ $photo->url() }}"
                                alt="{{ $photo->original_name ?? 'Container photo' }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
        <p class="mt-4 text-sm text-gray-500">You have uploaded the maximum number of photos for this container.</p>
    @endif

    @if ($hubPhotos->isNotEmpty())
        <div class="mt-6 border-t border-gray-100 pt-5">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-sm font-medium text-gray-700">New Life hub photos</h3>
            </div>
            <p class="mt-1 text-xs text-gray-500">
                Photos taken by New Life when your container arrived at our hub, kept on file as proof of condition.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                @foreach ($hubPhotos as $photo)
                    <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer" class="block h-full w-full">
                            <img
                                src="{{ $photo->url() }}"
                                alt="{{ $photo->original_name ?? 'Hub evidence photo' }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
