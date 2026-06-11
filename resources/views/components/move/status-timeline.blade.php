@props(['steps' => [], 'activeIndex' => 0])

@php
    $shortLabels = [
        'container_prepared' => 'Prepared',
        'label_generated' => 'Label',
        'shipped_to_home' => 'Shipped',
        'delivered_to_home' => 'At home',
        'customer_packing' => 'Packing',
        'pickup_scheduled' => 'Pickup',
        'return_shipment_in_transit' => 'Return',
        'received_at_new_life_hub' => 'At hub',
        'stored_at_receiving_hub' => 'Stored',
        'scheduled_for_dorm_delivery' => 'Scheduled',
        'out_for_delivery' => 'Out',
        'delivered_to_dorm' => 'Delivered',
    ];
    $stepCount = count($steps);
    $visibleCount = 6;
@endphp

<div
    class="move-timeline-carousel"
    x-data="{
        activeIndex: {{ (int) $activeIndex }},
        startIndex: 0,
        visibleCount: {{ $visibleCount }},
        stepCount: {{ $stepCount }},
        stepWidth: 0,
        init() {
            this.$nextTick(() => {
                this.measure();
                this.scrollToActive();
            });
            window.addEventListener('resize', () => {
                this.measure();
                this.clampStart();
            });
        },
        measure() {
            const viewport = this.$refs.viewport;
            if (!viewport) return;
            this.stepWidth = viewport.clientWidth / this.visibleCount;
        },
        get maxStartIndex() {
            return Math.max(0, this.stepCount - this.visibleCount);
        },
        get translateX() {
            return this.startIndex * this.stepWidth;
        },
        get rangeLabel() {
            const from = this.startIndex + 1;
            const to = Math.min(this.startIndex + this.visibleCount, this.stepCount);
            return `Steps ${from}–${to} of ${this.stepCount}`;
        },
        scrollToActive() {
            const ideal = Math.min(
                Math.max(this.activeIndex - 2, 0),
                this.maxStartIndex
            );
            this.startIndex = ideal;
        },
        clampStart() {
            this.startIndex = Math.min(this.startIndex, this.maxStartIndex);
        },
        prev() {
            this.measure();
            this.startIndex = Math.max(0, this.startIndex - 1);
        },
        next() {
            this.measure();
            this.startIndex = Math.min(this.maxStartIndex, this.startIndex + 1);
        },
        canPrev() {
            return this.startIndex > 0;
        },
        canNext() {
            return this.startIndex < this.maxStartIndex;
        }
    }"
    aria-label="Move status timeline"
>
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-xs font-medium text-gray-500" x-text="rangeLabel"></p>
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                x-on:click="prev()"
                x-bind:disabled="!canPrev()"
                aria-label="Show previous steps"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                x-on:click="next()"
                x-bind:disabled="!canNext()"
                aria-label="Show next steps"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <div class="overflow-hidden" x-ref="viewport">
        <ol
            class="flex list-none p-0 m-0 transition-transform duration-300 ease-out"
            x-bind:style="`transform: translateX(-${translateX}px); width: ${stepWidth * stepCount}px`"
        >
            @foreach ($steps as $step)
                @php
                    $isCurrent = $step['current'] ?? false;
                    $isPast = ($step['reached'] ?? false) && !$isCurrent;
                    $isFuture = !$isPast && !$isCurrent;
                    $statusKey = $step['status'] ?? '';
                    $shortLabel = $shortLabels[$statusKey] ?? $step['label'];
                    $prevReached = !$loop->first && (($steps[$loop->index - 1]['reached'] ?? false));
                    $stepNumber = $loop->iteration;
                @endphp
                <li
                    class="relative shrink-0 flex flex-col items-center px-2"
                    x-bind:style="stepWidth > 0 ? { width: stepWidth + 'px', minWidth: stepWidth + 'px' } : {}"
                >
                    @if (!$loop->first)
                        <span
                            @class([
                                'pointer-events-none absolute top-[1.375rem] right-1/2 z-0 h-1 w-1/2 -translate-y-1/2',
                                'bg-brand-500' => $prevReached,
                                'bg-gray-200' => !$prevReached,
                            ])
                            aria-hidden="true"
                        ></span>
                    @endif

                    @if (!$loop->last)
                        <span
                            @class([
                                'pointer-events-none absolute top-[1.375rem] left-1/2 z-0 h-1 w-1/2 -translate-y-1/2',
                                'bg-brand-500' => $isPast,
                                'bg-green-500' => $isCurrent,
                                'bg-gray-200' => $isFuture,
                            ])
                            aria-hidden="true"
                        ></span>
                    @endif

                    <div class="relative z-20 flex flex-col items-center">
                        @if ($isCurrent)
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-full bg-green-500 text-sm font-bold text-white shadow-md ring-4 ring-green-100"
                                aria-current="step"
                            >
                                {{ $stepNumber }}
                            </span>
                        @elseif ($isPast)
                            <span class="relative flex h-11 w-11 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white shadow-md">
                                {{ $stepNumber }}
                            </span>
                        @else
                            <span class="relative flex h-11 w-11 items-center justify-center rounded-full border-2 border-gray-200 bg-white text-sm font-semibold text-gray-400 shadow-sm">
                                {{ $stepNumber }}
                            </span>
                        @endif
                    </div>

                    <div class="relative z-20 mt-3 w-full text-center">
                        @if ($isCurrent)
                            <span class="inline-block rounded-full bg-green-50 px-2 py-1 text-[11px] font-semibold leading-snug text-green-800 ring-1 ring-green-200">
                                {{ $step['label'] }}
                            </span>
                        @else
                            <p @class([
                                'mx-auto max-w-[108px] text-[11px] leading-snug',
                                'font-semibold text-brand-700' => $isPast,
                                'font-medium text-gray-400' => $isFuture,
                            ]) title="{{ $step['label'] }}">
                                {{ $step['label'] }}
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</div>
