@props([
    'container',
    'fedExLinkService',
    'isPrimary' => false,
    'quantity' => null,
])

@php
    $outUrl = $fedExLinkService->trackingUrl($container->outbound_tracking);
@endphp

<article @class([
    'flex flex-col rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md',
    'border-brand-300 ring-2 ring-brand-100' => $isPrimary,
    'border-gray-200' => !$isPrimary,
])>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Move shipment</p>
            <h3 class="text-lg font-bold text-gray-900">{{ $container->code }}</h3>
            @if ($quantity)
                <p class="mt-0.5 text-xs text-gray-500">Includes {{ $quantity }} {{ \Illuminate\Support\Str::plural('container', $quantity) }}</p>
            @endif
        </div>
        <span class="inline-flex shrink-0 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800">
            {{ $container->statusLabel() }}
        </span>
    </div>

    <dl class="mt-4 flex-1 space-y-2 text-sm text-gray-600">
        @if ($container->location)
            <div class="flex justify-between gap-2">
                <dt>Location</dt>
                <dd class="font-medium text-gray-900">{{ $container->location }}</dd>
            </div>
        @endif
        @if ($container->ship_by_date)
            <div class="flex justify-between gap-2">
                <dt>Ship by</dt>
                <dd class="font-medium text-gray-900">{{ $container->ship_by_date->format('M j, Y') }}</dd>
            </div>
        @endif
        @if ($container->shippedAt())
            <div class="flex justify-between gap-2">
                <dt>Shipped by</dt>
                <dd class="font-medium text-gray-900">{{ $container->shippedAt()->format('M j, Y') }}</dd>
            </div>
        @endif
        @if ($container->deliveredHomeAt())
            <div class="flex justify-between gap-2">
                <dt>Delivered home</dt>
                <dd class="font-medium text-gray-900">{{ $container->deliveredHomeAt()->format('M j, Y') }}</dd>
            </div>
        @endif
        <div class="flex justify-between gap-2">
            <dt>Outbound tracking</dt>
            <dd class="font-medium text-gray-900">
                @if ($container->outbound_tracking)
                    <a href="{{ $outUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-600 hover:underline">
                        {{ $container->outbound_tracking }}
                    </a>
                @else
                    <span class="text-gray-400">Pending</span>
                @endif
            </dd>
        </div>
    </dl>
</article>
