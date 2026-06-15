@props([
    'container',
])

@php
    $remaining = $container->remainingPhotoSlots();
    $cap = $container->photoCap();
    $canUpload = $container->acceptsPhotos();
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ $container->code }}</h3>
            <p class="text-xs text-gray-500">{{ $container->photos->count() }} of {{ $cap }} photos uploaded</p>
        </div>
    </div>

    @if ($container->photos->isNotEmpty())
        <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
            @foreach ($container->photos as $photo)
                <div class="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer" class="block h-full w-full">
                        <img src="{{ $photo->url() }}" alt="{{ $photo->original_name ?? 'Container photo' }}"
                            class="h-full w-full object-cover transition duration-200 group-hover:scale-105" loading="lazy" />
                    </a>
                    @if ($canUpload)
                        <form action="{{ route('student.move-tracking.photos.destroy', [$container, $photo]) }}" method="POST"
                            class="absolute right-1 top-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full bg-gray-900/60 p-1 text-white opacity-0 transition group-hover:opacity-100 hover:bg-red-600 focus:opacity-100"
                                aria-label="Delete photo">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($remaining > 0)
        <div @class([
            'mt-4 rounded-xl',
            'select-none border border-dashed border-gray-200 bg-gray-50 p-4 opacity-70 grayscale' => ! $canUpload,
        ])>
            @if (! $canUpload)
                <p class="mb-3 text-xs font-medium text-gray-500">
                    Photo uploads open while your container is being packed.
                </p>
            @endif
            <form action="{{ route('student.move-tracking.photos.store', $container) }}" method="POST" enctype="multipart/form-data"
                class="space-y-3">
                @csrf
                <fieldset @disabled(! $canUpload) class="space-y-3 border-0 p-0">
                    <div>
                        <label for="photos-{{ $container->id }}" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Add exterior photos (up to {{ $remaining }} more)
                        </label>
                        <input id="photos-{{ $container->id }}" name="photos[]" type="file" accept="image/jpeg,image/png" multiple {{ $canUpload ? 'required' : '' }}
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400" />
                    </div>
                    <label class="flex items-start gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="acknowledge" value="1" {{ $canUpload ? 'required' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 disabled:cursor-not-allowed" />
                        <span>I understand failure to upload photos may impact damage claim processing.</span>
                    </label>
                    <button type="submit" {{ $canUpload ? '' : 'disabled' }}
                        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 disabled:hover:bg-gray-300">
                        Upload photos
                    </button>
                </fieldset>
            </form>
        </div>
    @else
        <p class="mt-4 text-sm text-gray-500">You have uploaded the maximum number of photos for this container.</p>
    @endif
</div>
