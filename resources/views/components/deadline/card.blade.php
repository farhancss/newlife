@props(['deadline'])

@php
    $tone = $deadline->tone();
    $toneClasses = match ($tone) {
        'success' => ['ring' => 'ring-emerald-200', 'bg' => 'bg-emerald-50', 'badge' => 'bg-emerald-100 text-emerald-700', 'accent' => 'text-emerald-700'],
        'warning' => ['ring' => 'ring-amber-200', 'bg' => 'bg-amber-50', 'badge' => 'bg-amber-100 text-amber-800', 'accent' => 'text-amber-800'],
        default => ['ring' => 'ring-brand-200', 'bg' => 'bg-brand-50', 'badge' => 'bg-brand-100 text-brand-700', 'accent' => 'text-brand-700'],
    };
    $days = $deadline->daysRemaining();
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs ring-1 {{ $toneClasses['ring'] }}">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $toneClasses['badge'] }}">
                {{ $deadline->statusLabel() }}
            </span>
            <p class="mt-2 font-semibold text-gray-900">{{ $deadline->title }}</p>
            <p class="mt-0.5 text-xs uppercase tracking-wide text-gray-400">{{ $deadline->typeLabel() }}</p>
            @if ($deadline->description)
                <p class="mt-2 text-sm text-gray-600">{{ $deadline->description }}</p>
            @endif
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
        <div class="text-xs text-gray-500">
            <span class="font-medium text-gray-700">Due</span> {{ $deadline->due_at->format('M j, Y') }}
        </div>
        <div class="text-sm font-semibold {{ $toneClasses['accent'] }}">
            @if ($deadline->isCompleted())
                Completed{{ $deadline->completed_at ? ' ' . $deadline->completed_at->format('M j') : '' }}
            @elseif ($deadline->isOverdue())
                {{ abs($days) }} {{ \Illuminate\Support\Str::plural('day', abs($days)) }} overdue
            @elseif ($days <= 0)
                Due today
            @else
                {{ $days }} {{ \Illuminate\Support\Str::plural('day', $days) }} left
            @endif
        </div>
    </div>
</div>
