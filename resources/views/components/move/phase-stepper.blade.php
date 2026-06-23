@props(['phases' => []])

<div class="overflow-x-auto pb-2">
    <div class="relative min-w-[560px] px-1">
        <div class="absolute left-[8%] right-[8%] top-5 h-0.5 bg-gray-200" aria-hidden="true"></div>
        @php
            $lastReached = collect($phases)->filter(fn ($p) => $p['reached'])->keys()->last();
            $progressWidth = $lastReached !== null && count($phases) > 1
                ? (($lastReached + 1) / count($phases)) * 84
                : 0;
        @endphp
        @if ($progressWidth > 0)
            <div class="absolute left-[8%] top-5 h-0.5 bg-brand-500 transition-all duration-500" style="width: {{ $progressWidth }}%;" aria-hidden="true"></div>
        @endif

        <ol class="relative grid grid-cols-6 gap-2">
            @foreach ($phases as $phase)
                <li class="flex flex-col items-center text-center">
                    @if ($phase['current'])
                        <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white shadow-md ring-4 ring-brand-100">
                            {{ $loop->iteration }}
                        </span>
                    @elseif ($phase['reached'])
                        <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    @else
                        <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 border-gray-200 bg-white text-xs font-semibold text-gray-400">
                            {{ $loop->iteration }}
                        </span>
                    @endif
                    <p @class([
                        'mt-3 max-w-[92px] text-xs leading-snug',
                        'font-semibold text-brand-500' => $phase['current'],
                        'text-gray-700' => $phase['reached'] && !$phase['current'],
                        'text-gray-400' => !$phase['reached'],
                    ])>
                        {{ $phase['label'] }}
                    </p>
                </li>
            @endforeach
        </ol>
    </div>
</div>
