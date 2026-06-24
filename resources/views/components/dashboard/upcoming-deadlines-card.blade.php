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

    <ul class="mt-3 flex-1 divide-y divide-gray-100">
        @forelse ($deadlines->take(3) as $deadline)
            @php
                $priority = $priorityFor($deadline);
                $styles = $priorityStyles[$priority];
                $days = $deadline->daysRemaining();
            @endphp
            <li class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                <span class="flex min-w-0 items-center gap-2.5">
                    <span class="inline-flex h-2 w-2 shrink-0 rounded-full bg-brand-300"></span>
                    <span class="truncate text-sm text-gray-800">{{ $deadline->title }}</span>
                </span>
                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium bg-brand-300 text-white">
                    @if ($deadline->isOverdue())
                        {{ abs($days) }} {{ \Illuminate\Support\Str::plural('day', abs($days)) }} overdue
                    @elseif ($days <= 0)
                        Due today
                    @else
                        {{ $days }} {{ \Illuminate\Support\Str::plural('day', $days) }} left
                    @endif
                </span>
            </li>
        @empty
            <li class="text-sm text-gray-500">No active deadlines — you're all caught up.</li>
        @endforelse
    </ul>
</div>
