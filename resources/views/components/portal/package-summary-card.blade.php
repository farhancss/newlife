@props(['package' => null, 'profile' => null, 'compact' => false])

@php
    $pkg = $package ?? $profile?->package;
    $isFeatured = $pkg?->is_featured ?? false;
@endphp

@if ($pkg)
    <div @class([
        'relative overflow-hidden rounded-2xl border p-5 sm:p-6',
        'border-brand-300 bg-brand-50' => $isFeatured && !$compact,
        'border-gray-200 bg-white' => !$isFeatured || $compact,
    ])>
        @if ($isFeatured && !$compact)
            <span class="absolute right-4 top-4 rounded-full bg-brand-500 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                Your package
            </span>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-500">
                    {{ $pkg->formattedPrice() }}<span class="font-normal text-brand-500">*</span>
                </p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl">
                    {{ $pkg->name }}
                </h2>
                @if ($pkg->tagline)
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-gray-700">
                        {{ $pkg->tagline }}
                    </p>
                @endif
            </div>

            <div class="flex shrink-0 flex-col items-center rounded-xl border border-brand-200 bg-white px-4 py-3 text-center shadow-sm">
                <span class="text-2xl font-bold tabular-nums text-brand-700">{{ $pkg->container_count }}</span>
                <span class="text-xs font-medium text-gray-500">containers</span>
            </div>
        </div>

        @if (!$compact && is_array($pkg->features) && count($pkg->features) > 0)
            <ul class="mt-5 space-y-2 text-sm text-gray-700">
                @foreach ($pkg->features as $feature)
                    <li class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($pkg->includes_move_out_cycle)
            <p class="mt-4 text-xs font-medium text-brand-700">
                Includes move-out, summer storage, and return delivery for the school year.
            </p>
        @endif
    </div>
@else
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
        <h2 class="text-base font-semibold text-amber-900">Package pending</h2>
        <p class="mt-1 text-sm text-amber-800">
            Your Squarespace order has not been linked yet. Once your purchase syncs, your container allowance and move plan will appear here.
        </p>
    </div>
@endif
