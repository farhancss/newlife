@props([
    'deadlines',
])

@php
    $priorityFor = function ($deadline): string {
        $days = $deadline->daysRemaining();

        if ($deadline->isOverdue() || $days <= 2) {
            return 'high';
        }

        if ($days <= 7) {
            return 'moderate';
        }

        return 'low';
    };

    $priorityStyles = [
        'high' => [
            'dot' => 'bg-blue-light-400',
            'badge' => 'bg-blue-light-50 text-blue-light-700',
            'legend' => 'bg-blue-light-100',
        ],
        'moderate' => [
            'dot' => 'bg-amber-400',
            'badge' => 'bg-amber-50 text-amber-700',
            'legend' => 'bg-amber-100',
        ],
        'low' => [
            'dot' => 'bg-emerald-400',
            'badge' => 'bg-emerald-50 text-emerald-700',
            'legend' => 'bg-emerald-100',
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex min-h-[220px] flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs']) }}>
    <x-dashboard.summary-card-header title="Upcoming Deadlines" :href="route('student.deadlines')" />

    <div class="mt-3 flex flex-wrap items-center justify-end gap-4 text-xs text-gray-500">
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm {{ $priorityStyles['high']['legend'] }}"></span>
            High
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm {{ $priorityStyles['moderate']['legend'] }}"></span>
            Moderate
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm {{ $priorityStyles['low']['legend'] }}"></span>
            Low
        </span>
    </div>

    <ul class="mt-3 flex-1 divide-y divide-gray-100">
        @forelse ($deadlines->take(3) as $deadline)
            @php
                $priority = $priorityFor($deadline);
                $styles = $priorityStyles[$priority];
            @endphp
            <li class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                <span class="flex min-w-0 items-center gap-2.5">
                    <span class="inline-flex h-2 w-2 shrink-0 rounded-full {{ $styles['dot'] }}"></span>
                    <span class="truncate text-sm text-gray-800">{{ $deadline->title }}</span>
                </span>
                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $styles['badge'] }}">
                    {{ $deadline->due_at->format('M j, Y') }}
                </span>
            </li>
        @empty
            <li class="py-6 text-sm text-gray-500">No active deadlines — you're all caught up.</li>
        @endforelse
    </ul>
</div>
