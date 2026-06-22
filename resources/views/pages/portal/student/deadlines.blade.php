@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
            <h1 class="text-xl font-semibold text-gray-900">Deadline Center</h1>
            <p class="mt-1 text-sm text-gray-600">Stay on top of every time-sensitive step of your move. We email you a reminder a day before each deadline.</p>

            <div class="mt-4 grid grid-cols-3 gap-3">
                <div class="rounded-xl bg-brand-50 p-3 text-center">
                    <p class="text-2xl font-semibold text-brand-700">{{ $upcoming->count() }}</p>
                    <p class="text-xs font-medium text-brand-700">Upcoming</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-3 text-center">
                    <p class="text-2xl font-semibold text-amber-800">{{ $overdue->count() }}</p>
                    <p class="text-xs font-medium text-amber-800">Overdue</p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-3 text-center">
                    <p class="text-2xl font-semibold text-emerald-700">{{ $completed->count() }}</p>
                    <p class="text-xs font-medium text-emerald-700">Completed</p>
                </div>
            </div>
        </div>

        @if ($overdue->isNotEmpty())
            <section class="space-y-3">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-800">
                    <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                    Overdue ({{ $overdue->count() }})
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($overdue as $deadline)
                        <x-deadline.card :deadline="$deadline" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="space-y-3">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-brand-700">
                <span class="inline-block h-2 w-2 rounded-full bg-brand-500"></span>
                Upcoming ({{ $upcoming->count() }})
            </h2>
            @if ($upcoming->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                    No upcoming deadlines right now. You're all caught up.
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($upcoming as $deadline)
                        <x-deadline.card :deadline="$deadline" />
                    @endforeach
                </div>
            @endif
        </section>

        @if ($completed->isNotEmpty())
            <section class="space-y-3">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-emerald-700">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    Completed ({{ $completed->count() }})
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($completed as $deadline)
                        <x-deadline.card :deadline="$deadline" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
