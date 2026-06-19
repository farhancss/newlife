@extends('layouts.app')

@php
    use App\Enums\AddOnStatus;

    $statusBadge = match ($addOn->status) {
        AddOnStatus::ACTIVE => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        AddOnStatus::CANCELLED => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        default => 'bg-gray-100 text-gray-600 ring-gray-500/20',
    };

    $student = $addOn->studentProfile;
    $studentName = $student->fullName() ?: $student->user?->name;
@endphp

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <a href="{{ route('admin.add-ons') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-brand-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Add-Ons
        </a>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ $addOn->name }}</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Purchased by
                            <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-brand-700 hover:underline">{{ $studentName }}</a>
                            · <span class="font-mono">{{ $student->new_life_id }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $statusBadge }}">
                            {{ $addOn->statusLabel() }}
                        </span>
                        @if ($container)
                            <a href="{{ route('admin.containers', ['edit' => $container->id]) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                                Edit container
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="px-6 py-5">
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Price</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $addOn->formattedPrice() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Purchased</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $addOn->requested_at?->format('M j, Y · g:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Activated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $addOn->activated_at?->format('M j, Y · g:i A') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 border-t border-gray-100 pt-4">
                    <a href="{{ $addOn->squarespace_url }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:underline">
                        View on newlifecampus.com →
                    </a>
                </div>
            </div>
        </div>

        @if ($container && $timeline)
            @php
                $currentStepIndex = collect($timeline)->search(fn ($s) => $s['current']);
                $currentStepNumber = $currentStepIndex === false ? 1 : $currentStepIndex + 1;
                $timelineActiveIndex = $currentStepIndex === false ? 0 : (int) $currentStepIndex;
                $lastUpdate = $container->statusHistories->first();
            @endphp

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white px-5 py-5 sm:px-8">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-base font-semibold text-gray-900">Container move journey</h2>
                        <p class="text-sm text-gray-600">
                            Step {{ $currentStepNumber }} of 12
                            <span class="text-gray-300">·</span>
                            <span class="font-semibold text-green-700">{{ $container->statusLabel() }}</span>
                            <span class="text-gray-300">·</span>
                            <span class="font-mono text-xs text-gray-500">{{ $container->code }}</span>
                            @if ($lastUpdate)
                                <span class="text-gray-300">·</span>
                                Updated <span class="font-semibold text-gray-700">{{ $lastUpdate->created_at->format('M j, Y') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="bg-gradient-to-b from-white to-slate-50/80 px-4 py-6 sm:px-6 sm:py-8">
                    <x-move.status-timeline :steps="$timeline" :active-index="$timelineActiveIndex" />
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
                <div class="lg:col-span-1">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Container</h2>
                    <x-move.container-card :container="$container" :fed-ex-link-service="$fedExLinkService" :is-primary="true" />
                </div>
                <div class="space-y-4 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-900">Packing photos ({{ $container->photos->count() }})</h2>
                    @if ($container->photos->isNotEmpty())
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                            @foreach ($container->photos as $photo)
                                <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer" class="group relative block aspect-square overflow-hidden rounded-lg border border-gray-200">
                                    <img src="{{ $photo->url() }}" alt="{{ $photo->original_name ?: 'Container photo' }}" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                            No photos uploaded yet.
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
