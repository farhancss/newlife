@props([
    'recentUpdates',
])

@php
    $latest = $recentUpdates->first();
@endphp

<div {{ $attributes->merge(['class' => 'flex min-h-[220px] flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs']) }}>
    <x-dashboard.summary-card-header title="Latest Update" :href="route('student.move-tracking')" />

    @if ($latest)
        <div class="mt-4 flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <p class="truncate text-sm font-medium text-gray-600">{{ $latest['label'] }}</p>
            </div>
            <p class="shrink-0 text-xs text-gray-500">{{ $latest['date']->format('M j, Y') }}</p>
        </div>

        <div class="mt-4 border-t border-gray-100 pt-4">
            <div class="flex items-end justify-between gap-3">
                <div class="w-[8.75rem] shrink-0 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3.5 py-2.5 text-center sm:w-[9.5rem]">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Container ID</p>
                    <p class="mt-0.5 font-mono text-sm font-semibold text-brand-500">{{ $latest['code'] }}</p>
                </div>

                <img src="{{ asset('images/dashboard/truck.png') }}" alt=""
                    class="h-16 w-auto shrink-0 object-contain sm:h-20" aria-hidden="true" />
            </div>
        </div>
    @else
        <div class="mt-4 flex flex-1 flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-gray-600">Containers are being prepared</p>
                </div>
                <p class="shrink-0 text-xs text-gray-500">{{ now()->format('M j, Y') }}</p>
            </div>

            <div class="mt-4 flex items-end justify-end border-t border-gray-100 pt-4">
                <img src="{{ asset('images/dashboard/truck.png') }}" alt=""
                    class="h-16 w-auto shrink-0 object-contain opacity-60 sm:h-20" aria-hidden="true" />
            </div>
        </div>
    @endif
</div>
