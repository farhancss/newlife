@props([
    'container',
    'fedExLinkService',
    'isPrimary' => false,
    'quantity' => null,
    'sectionTitle' => null,
])

@php
    $outUrl = $fedExLinkService->trackingUrl($container->outbound_tracking);
    $returnUrl = $fedExLinkService->trackingUrl($container->return_tracking);
@endphp

<article {{ $attributes->merge(['class' => 'flex h-full w-full flex-col rounded-2xl border border-gray-200 bg-white p-5 sm:p-6']) }}>
    @if ($sectionTitle)
        <h2 class="mb-4 text-lg font-medium text-gray-500">{{ $sectionTitle }}</h2>
    @endif

    <div class="rounded-xl bg-[#F3F5FF] px-4 py-4">
        <div class="flex items-start justify-between gap-5">
            <div class="min-w-0">
                <p class="text-sm text-gray-500">{{ $container->isAddOn() ? 'Add-on container' : 'Move shipment' }}</p>
                @if ($quantity)
                    <p class="mt-2 text-sm text-gray-500">Includes {{ $quantity }} {{ \Illuminate\Support\Str::plural('container', $quantity) }}</p>
                @endif
            </div>
            <p class="shrink-0 text-md font-medium text-gray-900">{{ $container->code }}</p>
        </div>
    </div>

    <dl class="mt-5 space-y-3 text-sm">
        @if ($container->location)
            <div class="flex items-start justify-between gap-4">
                <dt class="text-gray-500">Location</dt>
                <dd class="text-right text-gray-500">{{ $container->location }}</dd>
            </div>
        @endif
        @if ($container->ship_by_date)
            <div class="flex items-start justify-between gap-4">
                <dt class="text-gray-500">Ship by</dt>
                <dd class="text-right text-gray-500">{{ $container->ship_by_date->format('M j, Y') }}</dd>
            </div>
        @endif
        <div class="flex items-start justify-between gap-4">
            <dt class="text-gray-500">Shipped</dt>
            <dd class="text-right text-gray-500">
                @if ($container->shippedAt())
                    {{ $container->shippedAt()->format('M j, Y') }}
                @else
                    <span class="text-gray-500">—</span>
                @endif
            </dd>
        </div>
        @if ($container->deliveredHomeAt())
            <div class="flex items-start justify-between gap-4">
                <dt class="text-gray-500">Delivered home</dt>
                <dd class="text-right text-gray-500">{{ $container->deliveredHomeAt()->format('M j, Y') }}</dd>
            </div>
        @endif
        <div class="flex items-start justify-between gap-4">
            <dt class="text-gray-500">Outbound tracking</dt>
            <dd class="text-right text-gray-500">
                @if ($container->outbound_tracking)
                    <a href="{{ $outUrl }}" target="_blank" rel="noopener noreferrer" class="text-gray-500 hover:underline">
                        {{ $container->outbound_tracking }}
                    </a>
                @else
                    <span class="text-gray-400">Pending</span>
                @endif
            </dd>
        </div>
        <div class="flex items-start justify-between gap-4">
            <dt class="text-gray-500">Return tracking</dt>
            <dd class="text-right text-gray-500">
                @if ($container->return_tracking)
                    <a href="{{ $returnUrl }}" target="_blank" rel="noopener noreferrer" class=" text-brand-500 hover:underline">
                        {{ $container->return_tracking }}
                    </a>
                @else
                    <span class="text-gray-500">Added when shipped</span>
                @endif
            </dd>
        </div>
    </dl>

    <div class="mt-auto flex justify-end pt-5">
        <span class="inline-flex rounded-full bg-[#E8EEFF] px-3 py-1 text-xs text-brand-300">
            {{ $container->statusLabel() }}
        </span>
    </div>
</article>
