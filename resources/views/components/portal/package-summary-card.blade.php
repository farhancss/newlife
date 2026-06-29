@props(['package' => null, 'profile' => null, 'compact' => false])

@php
    $pkg = $package ?? $profile?->package;
    $isFeatured = $pkg?->is_featured ?? false;
    // Prefer what the student actually paid (Squarespace order grand total);
    // fall back to the catalogue list price when no order total is stored.
    $paidCents = $profile?->package_price_cents;
    $priceLabel = $paidCents !== null
        ? '$' . number_format($paidCents / 100, 2)
        : ($pkg?->formattedPrice());
@endphp

@if ($pkg)
    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        @unless ($compact)
            <h2 class="text-base font-medium text-gray-600">Current plan</h2>
        @endunless

        <div @class([
            'relative overflow-hidden rounded-2xl border border-brand-200 bg-brand-50',
            'mt-3 p-5 sm:p-6' => !$compact,
            'p-4 sm:p-5' => $compact,
        ])>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-stretch lg:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        @if ($isFeatured)
                            <img src="{{ asset('images/dashboard/diamond.svg') }}" alt="" class="h-5 w-5 shrink-0" aria-hidden="true" />
                        @endif
                        <h3 class="text-lg font-medium text-brand-900 sm:text-xl">
                            {{ $pkg->name }}
                        </h3>
                        <span class="text-base font-semibold text-brand-300 sm:text-lg">
                            {{ $priceLabel }}<span class="font-normal">*</span>
                        </span>
                    </div>

                    @if ($pkg->tagline)
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                            {{ $pkg->tagline }}
                        </p>
                    @endif

                    @if (!$compact && is_array($pkg->features) && count($pkg->features) > 0)
                        <ul class="mt-4 space-y-2.5 text-sm text-gray-700">
                            @foreach ($pkg->features as $feature)
                                <li class="flex items-start gap-2.5">
                                    <img src="{{ asset('images/dashboard/bullet.svg') }}" alt="" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($pkg->includes_move_out_cycle && !$compact)
                        <p class="mt-4 text-xs font-small text-brand-300">
                            Includes move-out, summer storage, and return delivery for the school year.
                        </p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-col rounded-xl border border-gray-200 bg-white px-4 py-4 text-center shadow-sm sm:min-w-[180px] lg:self-center">
                    <span class="text-[12px] font-semibold uppercase tracking-[0.14em] text-brand-300">
                        Your package
                    </span>
                    <div class="mt-3 rounded-xl bg-[#F3F5FF] px-5 py-4">
                        <span class="block text-4xl font-bold tabular-nums leading-none text-brand-400">{{ $pkg->container_count }}</span>
                        <span class="mt-1 block text-md font-medium text-gray-500">
                            {{ \Illuminate\Support\Str::plural('Container', $pkg->container_count) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
        <h2 class="text-base font-semibold text-amber-900">Package pending</h2>
        <p class="mt-1 text-sm text-amber-800">
            Your Squarespace order has not been linked yet. Once your purchase syncs, your container allowance and move plan will appear here.
        </p>
    </div>
@endif
