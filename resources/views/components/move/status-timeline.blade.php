@props(['steps' => [], 'activeIndex' => 0])

@php
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
        resolveLayout() {
            const viewport = this.$refs.viewport;
            if (!viewport) {
                return { visible: 6, stepWidth: 0 };
            }

            const width = viewport.clientWidth;

            if (width < 480) {
                return { visible: 2, stepWidth: width / 2 };
            }

            if (width < 640) {
                return { visible: 3, stepWidth: width / 3 };
            }

            if (width < 768) {
                return { visible: 3, stepWidth: width / 3 };
            }

            if (width < 1024) {
                return { visible: 4, stepWidth: width / 4 };
            }

            if (width < 1280) {
                return { visible: 5, stepWidth: width / 5 };
            }

            return { visible: 6, stepWidth: width / 6 };
        },
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
            const layout = this.resolveLayout();
            this.visibleCount = layout.visible;
            this.stepWidth = layout.stepWidth;
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
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="text-xs text-gray-500" x-text="rangeLabel"></p>
        <div class="flex items-center gap-1.5">
            <button
                type="button"
                class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                x-on:click="prev()"
                x-bind:disabled="!canPrev()"
                aria-label="Show previous steps"
            >
                <svg class="h-2 w-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button
                type="button"
                class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                x-on:click="next()"
                x-bind:disabled="!canNext()"
                aria-label="Show next steps"
            >
                <svg class="h-2 w-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <div class="overflow-hidden" x-ref="viewport">
        <ol
            class="m-0 flex list-none p-0 transition-transform duration-300 ease-out"
            x-bind:style="`transform: translateX(-${translateX}px); width: ${stepWidth * stepCount}px`"
        >
            @foreach ($steps as $step)
                @php
                    $isCurrent = $step['current'] ?? false;
                    $isPast = ($step['reached'] ?? false) && !$isCurrent;
                    $isFuture = !$isPast && !$isCurrent;
                    $prevPast = !$loop->first
                        && (($steps[$loop->index - 1]['reached'] ?? false) && !($steps[$loop->index - 1]['current'] ?? false));
                    $stepNumber = $loop->iteration;
                @endphp
                <li
                    class="relative flex shrink-0 flex-col items-center px-1 sm:px-2"
                    x-bind:style="stepWidth > 0 ? { width: stepWidth + 'px', minWidth: stepWidth + 'px' } : {}"
                >
                    @if (!$loop->first)
                        <span
                            @class([
                                'pointer-events-none absolute top-4 right-1/2 z-0 h-0.5 w-1/2 -translate-y-1/2 sm:top-5',
                                'bg-brand-300' => $prevPast,
                                'bg-gray-200' => !$prevPast,
                            ])
                            aria-hidden="true"
                        ></span>
                    @endif

                    @if (!$loop->last)
                        <span
                            @class([
                                'pointer-events-none absolute top-4 left-1/2 z-0 h-0.5 w-1/2 -translate-y-1/2 sm:top-5',
                                'bg-brand-300' => $isPast,
                                'bg-gray-200' => !$isPast,
                            ])
                            aria-hidden="true"
                        ></span>
                    @endif

                    <div class="relative z-10 flex flex-col items-center">
                        @if ($isCurrent)
                            <span
                                class="relative flex h-8 w-8 items-center justify-center rounded-full bg-green-500 text-xs font-semibold text-white sm:h-10 sm:w-10 sm:text-sm"
                                aria-current="step"
                            >
                                {{ $stepNumber }}
                            </span>
                        @elseif ($isPast)
                            <span class="relative flex h-8 w-8 items-center justify-center rounded-full bg-brand-300 text-xs font-semibold text-white sm:h-10 sm:w-10 sm:text-sm">
                                {{ $stepNumber }}
                            </span>
                        @else
                            <span class="relative flex h-8 w-8 items-center justify-center rounded-full border-2 border-[#D0D5DD] bg-white text-xs font-semibold text-brand-300 sm:h-10 sm:w-10 sm:text-sm">
                                {{ $stepNumber }}
                            </span>
                        @endif
                    </div>

                    <div @class([
                        'relative z-10 mt-3 w-full px-1 text-center sm:px-2',
                        'border-r border-gray-200' => !$loop->last,
                    ])>
                        <p @class([
                            'break-words text-xs font-semibold leading-snug sm:text-sm',
                            'text-brand-900' => $isPast || $isCurrent,
                            'text-gray-500' => $isFuture,
                        ])>
                            {{ $step['label'] }}
                        </p>

                        @if (($isPast || $isCurrent) && !empty($step['reached_at']))
                            <time
                                datetime="{{ $step['reached_at']->toDateString() }}"
                                class="mt-1 hidden text-xs text-gray-500 sm:block"
                            >
                                updated on {{ $step['reached_at']->format('j F Y') }}
                            </time>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</div>
