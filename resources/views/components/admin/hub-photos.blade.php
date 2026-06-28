@props([
    'container',
])

@php
    use App\Models\ContainerPhoto;

    $hubPhotos = $container->photos->where('type', ContainerPhoto::TYPE_HUB_INTAKE)->values();
    $canUpload = $container->acceptsHubPhotos();
    $cap = $container->hubPhotoCap();
    $remaining = $container->remainingHubPhotoSlots();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 sm:p-6']) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-gray-900">
                New Life hub evidence photos
                <span class="font-normal text-gray-500">({{ $hubPhotos->count() }} of {{ $cap }})</span>
            </h3>
            <p class="mt-1 text-xs text-gray-500">
                Captured by New Life when the container is received at the hub. Shared with the student as proof of condition.
            </p>
        </div>
        <span class="shrink-0 rounded-full bg-[#F3F5FF] px-3 py-1 text-xs font-medium text-brand-900">{{ $container->code }}</span>
    </div>

    @if ($hubPhotos->isNotEmpty())
        <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5">
            @foreach ($hubPhotos as $photo)
                <div class="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer" class="block h-full w-full">
                        <img src="{{ $photo->url() }}" alt="{{ $photo->original_name ?: 'Hub evidence photo' }}"
                            class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                    </a>
                    <form action="{{ route('admin.containers.photos.destroy', [$container, $photo]) }}" method="POST"
                        class="absolute right-1 top-1"
                        onsubmit="return confirm('Remove this hub evidence photo?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow-sm hover:bg-red-50 hover:text-red-600"
                            aria-label="Delete hub evidence photo">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    @if (! $canUpload)
        <p class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-500">
            Evidence uploads open once this container reaches <span class="font-medium text-gray-700">Delivered to Dorm</span>.
        </p>
    @elseif ($remaining <= 0)
        <p class="mt-4 text-xs text-gray-500">Maximum of {{ $cap }} hub evidence photos reached for this container.</p>
    @else
        @error('photos')
            <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700">{{ $message }}</p>
        @enderror

        <div x-data="containerPhotoPicker({{ $remaining }})" class="mt-4">
            <input
                id="hub-photos-{{ $container->id }}"
                type="file"
                accept="image/jpeg,image/png,image/jpg,.jpg,.jpeg,.png"
                multiple
                class="sr-only"
                x-ref="fileInput"
                x-on:change="addFiles($event)"
            />

            <div class="flex flex-wrap gap-3">
                <template x-for="item in pendingFiles" x-bind:key="item.id">
                    <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                        <img x-bind:src="item.preview" x-bind:alt="item.file.name" class="h-full w-full object-cover" />
                        <button type="button" x-on:click="removePending(item.id)"
                            class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow-sm hover:bg-red-50 hover:text-red-600"
                            aria-label="Remove selected photo">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>

                <div x-show="canAddMore()" x-cloak>
                    <button type="button" x-on:click="$refs.fileInput.click()"
                        class="flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-gray-300 bg-white text-gray-500 transition hover:border-brand-300 hover:bg-brand-50/40">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span class="text-xs font-medium">Add</span>
                    </button>
                </div>
            </div>

            <form
                action="{{ route('admin.containers.photos.store', $container) }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-4 flex items-center justify-between gap-3"
                x-on:submit.prevent="submitUpload($event)"
            >
                @csrf
                <p class="text-xs text-gray-500">Add up to {{ $remaining }} more photo(s).</p>
                <button type="submit" x-bind:disabled="pendingFiles.length === 0"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 disabled:hover:bg-gray-300">
                    Upload evidence
                </button>
            </form>
        </div>
    @endif
</div>
