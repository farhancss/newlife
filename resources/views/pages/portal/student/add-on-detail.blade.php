@extends('layouts.app')

@php
    use App\Enums\AddOnStatus;

    $statusBadge = match ($addOn->status) {
        AddOnStatus::ACTIVE => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        AddOnStatus::CANCELLED => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        default => 'bg-gray-100 text-gray-600 ring-gray-500/20',
    };
@endphp

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <a href="{{ route('student.add-ons') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 18l-6-6 6-6" />
            </svg>
            Back to Add-Ons
        </a>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ $addOn->name }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Add-on details</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $statusBadge }}">
                        {{ $addOn->statusLabel() }}
                    </span>
                </div>
            </div>

            <div class="px-6 py-5">
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Price</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $addOn->formattedPrice() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $addOn->statusLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Requested</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $addOn->requested_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Activated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $addOn->activated_at?->format('M j, Y g:i A') ?? 'Not yet activated' }}</dd>
                    </div>
                </dl>

                @if ($catalogEntry)
                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <h2 class="text-sm font-semibold text-gray-900">About this add-on</h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $catalogEntry['description'] }}</p>
                    </div>
                @endif

                @if ($addOn->isActive())
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-sm font-semibold text-emerald-800">Active</p>
                        <p class="mt-1 text-sm text-emerald-700">This add-on is active on your account.</p>
                    </div>
                @endif
            </div>
        </div>

        @if ($container && $timeline)
            @php
                $currentStepIndex = collect($timeline)->search(fn ($s) => $s['current']);
                $currentStepNumber = $currentStepIndex === false ? 1 : $currentStepIndex + 1;
                $timelineActiveIndex = $currentStepIndex === false ? 0 : (int) $currentStepIndex;
                $lastUpdate = $container->statusHistories->first();
            @endphp

            {{-- Container move journey (same 12-status flow as My Move) --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white px-5 py-5 sm:px-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Move progress</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Step {{ $currentStepNumber }} of 12
                                <span class="text-gray-300">·</span>
                                <span class="font-semibold text-green-700">{{ $container->statusLabel() }}</span>
                                <span class="text-gray-300">·</span>
                                <span class="font-mono text-xs text-gray-500">{{ $container->code }}</span>
                            </p>
                            <p class="mt-1.5 text-sm text-gray-500">
                                Updated
                                <span class="font-semibold text-gray-700">{{ $lastUpdate ? $lastUpdate->created_at->format('M j, Y') : $container->updated_at->format('M j, Y') }}</span>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 shadow-sm">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-500"></span>Done</span>
                            <span class="h-3 w-px bg-gray-200" aria-hidden="true"></span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-green-500 ring-2 ring-green-100"></span>Active</span>
                            <span class="h-3 w-px bg-gray-200" aria-hidden="true"></span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full border border-gray-300 bg-white"></span>Next</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-b from-white to-slate-50/80 px-4 py-6 sm:px-6 sm:py-8">
                    <x-move.status-timeline :steps="$timeline" :active-index="$timelineActiveIndex" />
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
                <div class="lg:col-span-1">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Your add-on container</h2>
                    <x-move.container-card :container="$container" :fed-ex-link-service="$fedExLinkService" :is-primary="true" />
                </div>
                <div class="space-y-4 lg:col-span-2">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Container photos</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Upload exterior photos of your container while it is being packed. Failure to upload photos may impact damage claim processing.
                        </p>
                    </div>
                    <x-move.container-photos :container="$container" />
                </div>
            </div>
        @endif
    </div>
@endsection
