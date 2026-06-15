@extends('layouts.app')

@section('content')
    @php
        $address = $profile->shippingAddress;
        $assignedCount = $containers->count();
    @endphp

    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success" :title="'Success'" :message="session('status')" />
        @endif

        @if ($errors->any())
            <x-ui.alert variant="error" title="Please fix the following">
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        {{-- Page header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">My Move</h1>
                <p class="mt-1 text-sm text-gray-600">Track containers from home shipment through dorm delivery.</p>
            </div>
            @if ($package)
                <div class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-800">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    Move includes {{ $containerAllowance }} {{ \Illuminate\Support\Str::plural('container', $containerAllowance) }}
                </div>
            @endif
        </div>

        {{-- Package from Squarespace --}}
        <x-portal.package-summary-card :package="$package" />

        @if (!$package)
            <p class="text-center text-sm text-gray-500">
                Purchased on
                <a href="https://www.newlifecampus.com" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-600 hover:underline">newlifecampus.com</a>?
                Your package syncs automatically after checkout.
            </p>
        @endif

        {{-- Move progress — inline horizontal state timeline --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white px-5 py-5 sm:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Move progress</h2>
                        @if ($primaryContainer)
                            @php
                                $currentStepIndex = collect($timeline)->search(fn ($s) => $s['current']);
                                $currentStepNumber = $currentStepIndex === false ? 1 : $currentStepIndex + 1;
                                $lastUpdate = $primaryContainer->statusHistories->first();
                            @endphp
                            <p class="mt-1 text-sm text-gray-600">
                                Step {{ $currentStepNumber }} of 12
                                <span class="text-gray-300">·</span>
                                <span class="font-semibold text-green-700">{{ $primaryContainer->statusLabel() }}</span>
                                <span class="text-gray-300">·</span>
                                <span class="font-mono text-xs text-gray-500">{{ $primaryContainer->code }}</span>
                            </p>
                            <p class="mt-1.5 text-sm text-gray-500">
                                Ship by
                                <span class="font-semibold text-gray-700">{{ $primaryContainer->ship_by_date ? $primaryContainer->ship_by_date->format('M j, Y') : 'To be scheduled' }}</span>
                                @if ($primaryContainer->ship_by_date)
                                    <span class="text-xs text-gray-400">({{ $primaryContainer->ship_by_date->isFuture() ? $primaryContainer->ship_by_date->diffForHumans(null, \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW) : 'date passed' }})</span>
                                @endif
                                <span class="text-gray-300">·</span>
                                Updated
                                <span class="font-semibold text-gray-700">{{ $lastUpdate ? $lastUpdate->created_at->format('M j, Y') : $primaryContainer->updated_at->format('M j, Y') }}</span>
                            </p>
                        @else
                            <p class="mt-1 text-sm text-amber-700">Waiting for your first container assignment.</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 shadow-sm">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            Done
                        </span>
                        <span class="h-3 w-px bg-gray-200" aria-hidden="true"></span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-green-500 ring-2 ring-green-100"></span>
                            Active
                        </span>
                        <span class="h-3 w-px bg-gray-200" aria-hidden="true"></span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full border border-gray-300 bg-white"></span>
                            Next
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-b from-white to-slate-50/80 px-4 py-6 sm:px-6 sm:py-8">
                @php
                    $timelineActiveIndex = collect($timeline)->search(fn ($s) => $s['current']);
                    $timelineActiveIndex = $timelineActiveIndex === false ? 0 : (int) $timelineActiveIndex;
                @endphp
                <x-move.status-timeline :steps="$timeline" :active-index="$timelineActiveIndex" />
            </div>
        </div>

        {{-- Move shipment + container photos --}}
        @if ($primaryContainer)
            <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
                <div class="lg:col-span-1">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Your move shipment</h2>
                    <x-move.container-card
                        :container="$primaryContainer"
                        :fed-ex-link-service="$fedExLinkService"
                        :quantity="$containerAllowance"
                        :is-primary="true"
                    />
                </div>

                @if ($containers->isNotEmpty())
                    <div class="space-y-4 lg:col-span-2">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Container photos</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Upload exterior photos of your container while it is being packed. Failure to upload photos may impact damage claim processing.
                            </p>
                        </div>
                        @foreach ($containers as $container)
                            <x-move.container-photos :container="$container" />
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
