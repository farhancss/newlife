@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
            <h1 class="text-xl font-semibold text-gray-900">Deadline Center</h1>
            <p class="mt-1 text-sm text-gray-600">Stay on top of every time-sensitive step of your move. We email you a reminder a day before each deadline.</p>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="flex items-center gap-2.5 rounded-xl bg-brand-50 px-3 py-3 sm:px-4">
                    <img src="{{ asset('images/dashboard/clock.svg') }}" alt="" class="h-6 w-6 shrink-0" aria-hidden="true" />
                    <div class="flex min-w-0 items-baseline gap-1.5">
                        <span class="text-2xl font-semibold text-brand-700">{{ $upcoming->count() }}</span>
                        <span class="text-sm font-semibold text-brand-700">Upcoming</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 rounded-xl bg-amber-50 px-3 py-3 sm:px-4">
                    <img src="{{ asset('images/dashboard/alert-circle.svg') }}" alt="" class="h-6 w-6 shrink-0" aria-hidden="true" />
                    <div class="flex min-w-0 items-baseline gap-1.5">
                        <span class="text-2xl font-semibold text-amber-800">{{ $overdue->count() }}</span>
                        <span class="text-sm font-semibold text-amber-800">Overdue</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 rounded-xl bg-emerald-50 px-3 py-3 sm:px-4">
                    <img src="{{ asset('images/dashboard/check-circle.svg') }}" alt="" class="h-6 w-6 shrink-0" aria-hidden="true" />
                    <div class="flex min-w-0 items-baseline gap-1.5">
                        <span class="text-2xl font-semibold text-emerald-700">{{ $completed->count() }}</span>
                        <span class="text-sm font-semibold text-emerald-700">Completed</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($overdue->isNotEmpty())
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-50">
                        <img src="{{ asset('images/dashboard/alert-circle.svg') }}" alt="" class="h-4 w-4" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Overdue ({{ $overdue->count() }})</h2>
                        <p class="text-sm text-amber-800">
                            {{ $overdue->count() }} {{ \Illuminate\Support\Str::plural('item', $overdue->count()) }}
                            {{ $overdue->count() === 1 ? 'requires' : 'require' }} immediate action
                        </p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($overdue as $deadline)
                        <x-deadline.card :deadline="$deadline" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="space-y-3">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50">
                    <img src="{{ asset('images/dashboard/clock.svg') }}" alt="" class="h-4 w-4" aria-hidden="true" />
                </span>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Upcoming ({{ $upcoming->count() }})</h2>
                    <p class="text-sm text-brand-700">
                        {{ $upcoming->count() }} {{ \Illuminate\Support\Str::plural('item', $upcoming->count()) }} due soon - take action now
                    </p>
                </div>
            </div>
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
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50">
                        <img src="{{ asset('images/dashboard/check-circle.svg') }}" alt="" class="h-4 w-4" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Completed ({{ $completed->count() }})</h2>
                        <p class="text-sm text-emerald-700">
                            {{ $completed->count() }} {{ \Illuminate\Support\Str::plural('item', $completed->count()) }} marked as complete
                        </p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($completed as $deadline)
                        <x-deadline.card :deadline="$deadline" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
