@props([
    'container',
])

@php
    $remaining = $container->remainingPhotoSlots();
    $cap = $container->photoCap();
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ $container->code }}</h3>
            <p class="text-xs text-gray-500">{{ $container->photos->count() }} of {{ $cap }} photos uploaded</p>
        </div>
        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Customer Packing</span>
    </div>

    @if ($container->photos->isNotEmpty())
        <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4">
            @foreach ($container->photos as $photo)
                <div class="group relative overflow-hidden rounded-lg border border-gray-200">
                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $photo->url() }}" alt="Container photo" class="h-24 w-full object-cover" loading="lazy" />
                    </a>
                    <form action="{{ route('student.move-tracking.photos.destroy', [$container, $photo]) }}" method="POST"
                        class="absolute right-1 top-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-full bg-gray-900/60 p-1 text-white transition hover:bg-red-600"
                            aria-label="Delete photo">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    @if ($remaining > 0)
        <form action="{{ route('student.move-tracking.photos.store', $container) }}" method="POST" enctype="multipart/form-data"
            class="mt-4 space-y-3">
            @csrf
            <div>
                <label for="photos-{{ $container->id }}" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Add exterior photos (up to {{ $remaining }} more)
                </label>
                <input id="photos-{{ $container->id }}" name="photos[]" type="file" accept="image/jpeg,image/png" multiple required
                    class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700" />
            </div>
            <label class="flex items-start gap-2 text-xs text-gray-600">
                <input type="checkbox" name="acknowledge" value="1" required
                    class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                <span>I understand failure to upload photos may impact damage claim processing.</span>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Upload photos
            </button>
        </form>
    @else
        <p class="mt-4 text-sm text-gray-500">You have uploaded the maximum number of photos for this container.</p>
    @endif
</div>
