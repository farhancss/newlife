@extends('layouts.app')

@php
    use App\Enums\DeadlineStatus;

    $statusBadge = function (string $status): string {
        return match ($status) {
            DeadlineStatus::COMPLETED => 'bg-emerald-50 text-emerald-700',
            DeadlineStatus::OVERDUE => 'bg-amber-50 text-amber-800',
            default => 'bg-brand-50 text-brand-700',
        };
    };
@endphp

@section('content')
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
            <h1 class="text-xl font-semibold text-gray-900">Deadline Center</h1>
            <p class="mt-1 text-sm text-gray-600">Every student deadline across profile completion, container pickups, and retail arrivals.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs">
                <p class="text-xs font-medium text-gray-500">Total</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-xl border border-brand-200 bg-brand-50 p-4 shadow-theme-xs">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/dashboard/clock.svg') }}" alt="" class="h-5 w-5 shrink-0" aria-hidden="true" />
                    <span class="text-sm font-semibold text-brand-700">Upcoming</span>
                </div>
                <p class="mt-1 text-2xl font-semibold text-brand-700">{{ $stats['upcoming'] }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-theme-xs">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/dashboard/alert-circle.svg') }}" alt="" class="h-5 w-5 shrink-0" aria-hidden="true" />
                    <span class="text-sm font-semibold text-amber-800">Overdue</span>
                </div>
                <p class="mt-1 text-2xl font-semibold text-amber-800">{{ $stats['overdue'] }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-theme-xs">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/dashboard/check-circle.svg') }}" alt="" class="h-5 w-5 shrink-0" aria-hidden="true" />
                    <span class="text-sm font-semibold text-emerald-700">Completed</span>
                </div>
                <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ $stats['completed'] }}</p>
            </div>
        </div>

        <form method="GET" class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="mb-1.5 block text-xs font-medium text-gray-500">Search</label>
                <input id="q" name="q" type="text" value="{{ $search }}" placeholder="Student, email, New Life ID, or title"
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-gray-500">Status</label>
                <select id="status" name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($statusFilter === $status)>{{ DeadlineStatus::label($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="h-10 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
        </form>

        <x-portal.data-table table-class="min-w-[820px]">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Deadline</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th>Remaining</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deadlines as $deadline)
                    @php $days = $deadline->daysRemaining(); @endphp
                    <tr class="hover:bg-gray-50/80">
                        <td class="font-medium text-gray-900">
                            @if ($deadline->studentProfile)
                                {{ $deadline->studentProfile->fullName() ?: $deadline->studentProfile->user?->name }}
                                <span class="block font-mono text-xs text-gray-400">{{ $deadline->studentProfile->new_life_id }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            <p class="font-medium text-gray-900">{{ $deadline->title }}</p>
                            @if ($deadline->description)
                                <p class="mt-0.5 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($deadline->description, 70) }}</p>
                            @endif
                        </td>
                        <td class="text-sm text-gray-600">{{ $deadline->typeLabel() }}</td>
                        <td>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge($deadline->effectiveStatus()) }}">
                                {{ $deadline->statusLabel() }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-600">{{ $deadline->due_at->format('M j, Y') }}</td>
                        <td class="text-sm">
                            @if ($deadline->isCompleted())
                                <span class="text-emerald-600">Done</span>
                            @elseif ($deadline->isOverdue())
                                <span class="font-medium text-amber-700">{{ abs($days) }}d overdue</span>
                            @elseif ($days <= 0)
                                <span class="text-gray-600">Due today</span>
                            @else
                                <span class="text-gray-600">{{ $days }}d left</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
                            <p class="text-sm font-medium text-gray-900">No deadlines found</p>
                            <p class="mt-1 text-sm text-gray-500">Deadlines appear as students onboard, receive containers, and log retail packages.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-portal.data-table>

        @if ($deadlines->hasPages())
            <div>{{ $deadlines->links() }}</div>
        @endif
    </div>
@endsection
