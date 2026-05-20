@props([
    'completion' => null,
    'compact' => false,
])

@if ($completion && !$completion['is_complete'])
    <a
        href="{{ route('student.profile') }}"
        @class([
            'inline-flex items-center gap-2 rounded-lg border border-warning-200 bg-warning-50 text-warning-800 transition hover:bg-warning-100',
            'px-2.5 py-1.5 text-xs font-semibold' => $compact,
            'px-3 py-2 text-sm font-semibold' => !$compact,
        ])
        title="Complete your profile to unlock all portal features"
    >
        <span class="relative flex h-2 w-2 shrink-0">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning-500 opacity-75"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-warning-600"></span>
        </span>
        <span class="hidden sm:inline">Profile {{ $completion['percent'] }}%</span>
        <span class="sm:hidden">{{ $completion['percent'] }}%</span>
        @unless ($compact)
            <span class="hidden text-xs font-normal text-warning-700 md:inline">
                — {{ $completion['total_fields'] - $completion['completed_fields'] }} fields left
            </span>
        @endunless
    </a>
@endif
