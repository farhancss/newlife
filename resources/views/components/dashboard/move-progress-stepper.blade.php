@props(['steps' => []])

@php
    $stepCount = count($steps);
    $activeIndex = collect($steps)->search(fn (array $step): bool => $step['state'] === 'active');
    $lastDoneIndex = collect($steps)->keys()->filter(fn (int $i): bool => $steps[$i]['state'] === 'done')->last();

    $lineEndIndex = match (true) {
        $activeIndex !== false => (int) $activeIndex,
        $lastDoneIndex !== null => (int) $lastDoneIndex,
        default => 0,
    };

    $progressPercent = $stepCount > 1 ? ($lineEndIndex / ($stepCount - 1)) * 100 : 0;

    $stepIcons = [
        'reservation' => 'clipboard',
        'profile' => 'profile',
        'preparing' => 'containers',
        'shipped' => 'package',
        'delivered_home' => 'house',
        'dorm' => 'dome',
    ];
@endphp

<div class="overflow-x-auto pb-1">
    <div class="relative min-w-[720px] px-1" role="list" aria-label="Move-in progress">
        <div class="absolute top-6 right-[8.3%] left-[8.3%] h-0.5 bg-gray-200" aria-hidden="true"></div>
        @if ($progressPercent > 0)
            <div class="absolute top-6 left-[8.3%] h-0.5 bg-brand-300 transition-all duration-500"
                style="width: calc((100% - 16.6%) * {{ $progressPercent / 100 }});" aria-hidden="true"></div>
        @endif

        {{-- Step icons --}}
        <ol class="relative grid grid-cols-6 gap-2">
            @foreach ($steps as $step)
                @php
                    $icon = $stepIcons[$step['key']] ?? 'package';
                    $isDone = $step['state'] === 'done';
                    $isActive = $step['state'] === 'active';
                @endphp
                <li class="flex flex-col items-center text-center" role="listitem"
                    aria-current="{{ $isActive ? 'step' : 'false' }}">
                    @if ($isDone)
                        <span
                            class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 border-[#D0D5DD] bg-brand-300 text-white shadow-sm">
                            <x-dashboard.move-step-icon name="check-badge" class="h-8 w-8" />
                        </span>
                    @else
                        <span
                            class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 border-[#D0D5DD] bg-white text-brand-300">
                            <x-dashboard.move-step-icon :name="$icon" class="h-8 w-8" />
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>

        {{-- Step labels --}}
        <ol class="mt-2 grid grid-cols-6 gap-2">
            @foreach ($steps as $step)
                <li @class([
                    'px-2 text-center text-sm leading-snug font-medium',
                    'border-r border-gray-200' => !$loop->last,
                    'text-brand-300' => $step['state'] === 'active',
                    'text-gray-700' => $step['state'] === 'done',
                    'text-gray-500' => $step['state'] === 'pending',
                ])>
                    {{ $step['label'] }}
                </li>
            @endforeach
        </ol>
    </div>
</div>
