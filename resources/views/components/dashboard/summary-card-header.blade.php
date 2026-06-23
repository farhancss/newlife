@props([
    'title',
    'href' => null,
])

<div class="flex items-center justify-between gap-3">
    <h3 class="text-base font-semibold text-gray-600">{{ $title }}</h3>

    @if ($href)
        <a href="{{ $href }}"
            class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-brand-500 transition hover:text-brand-700">
            View All
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    @endif
</div>
