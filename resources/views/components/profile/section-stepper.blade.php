@props([
    'sections' => [],
    'activeSection' => 1,
])

@php
    $stepCount = count($sections);
    $lastCompleteIndex = collect($sections)->keys()->filter(fn (int $i): bool => $sections[$i]['complete'])->last();

    $lineEndIndex = $lastCompleteIndex !== null ? (int) $lastCompleteIndex : -1;

    $progressPercent = $stepCount > 1 && $lineEndIndex >= 0
        ? ($lineEndIndex / ($stepCount - 1)) * 100
        : 0;

    $stepIcons = [
        'student' => 'profile',
        'parent' => 'profile',
        'shipping' => 'house',
        'housing' => 'dome',
    ];
@endphp

<div class="overflow-x-auto pb-1">
    <div class="relative min-w-[640px] px-1" role="navigation" aria-label="Profile sections">
        <div class="absolute top-6 right-[12.5%] left-[12.5%] h-0.5 bg-gray-200" aria-hidden="true"></div>
        @if ($progressPercent > 0)
            <div class="absolute top-6 left-[12.5%] h-0.5 bg-brand-300 transition-all duration-500"
                style="width: calc((100% - 25%) * {{ $progressPercent / 100 }});" aria-hidden="true"></div>
        @endif

        <ol class="relative grid grid-cols-4 gap-2">
            @foreach ($sections as $section)
                @php
                    $isComplete = $section['complete'];
                    $isCurrent = $activeSection === $section['step'];
                    $icon = $stepIcons[$section['key']] ?? 'profile';
                @endphp
                <li class="flex flex-col items-center text-center" role="listitem">
                    <a href="{{ route('student.profile', ['section' => $section['step']]) }}"
                        class="group flex flex-col items-center text-center"
                        aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                        @if ($isComplete)
                            <span
                                class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 border-[#D0D5DD] bg-brand-300 text-white shadow-sm">
                                <x-dashboard.move-step-icon name="check-badge" class="h-8 w-8" />
                            </span>
                        @else
                            <span
                                class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 border-[#D0D5DD] bg-white text-brand-300">
                                @if ($section['key'] === 'housing')
                                    <img src="{{ asset('images/dashboard/dome-2.svg') }}" alt="" class="h-5 w-5" aria-hidden="true" />
                                @else
                                    <x-dashboard.move-step-icon :name="$icon" class="h-8 w-8" />
                                @endif
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ol>

        <ol class="mt-2 grid grid-cols-4 gap-2">
            @foreach ($sections as $section)
                @php
                    $isComplete = $section['complete'];
                    $isCurrent = $activeSection === $section['step'];
                @endphp
                <li>
                    <a href="{{ route('student.profile', ['section' => $section['step']]) }}"
                        @class([
                            'block px-2 text-center text-sm leading-snug font-medium',
                            'border-r border-gray-200' => !$loop->last,
                            'text-brand-300' => $isCurrent,
                            'text-gray-600' => $isComplete && !$isCurrent,
                            'text-gray-500' => !$isComplete && !$isCurrent,
                        ])
                        aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                        {{ $section['label'] }}
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
</div>
