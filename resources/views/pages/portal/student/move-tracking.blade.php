@extends('layouts.app')

@section('content')
    @php
        $address = $profile->shippingAddress;
        $assignedCount = $containers->count();
    @endphp

    <div class="space-y-6">
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

        @if (($showStartPacking ?? false) && $primaryContainer)
            <div class="rounded-2xl border border-brand-200 bg-gradient-to-r from-brand-50 to-white p-6">
                <div class="sm:flex sm:items-start sm:justify-between sm:gap-6">
                    <div>
                        <h2 class="text-lg font-semibold text-brand-900">Your container has arrived — ready to pack?</h2>
                        <p class="mt-2 max-w-xl text-sm text-brand-800">
                            When you begin packing, let us know. We'll notify the New Life team that your move is in progress,
                            and you'll be able to upload container photos and request your pickup.
                        </p>
                    </div>
                </div>

                @error('packing')
                    <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
                @enderror

                <div class="mt-5 border-t border-brand-100 pt-5">
                    <form action="{{ route('student.move-tracking.start-packing', $primaryContainer) }}" method="POST"
                        data-confirm="Let us know you've started packing — our team will be notified."
                        data-confirm-title="Start packing?"
                        data-confirm-button="Yes, I've started"
                        data-confirm-icon="question">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            I've started packing
                        </button>
                        <p class="mt-2 text-xs text-brand-700/80">This moves your move to “Student Packing” and notifies our team.</p>
                    </form>
                </div>
            </div>
        @endif

        @if (($showPickupInstructions ?? false) && $primaryContainer)
            <div class="rounded-2xl border border-brand-200 bg-gradient-to-r from-brand-50 to-white p-6">
                <div class="sm:flex sm:items-start sm:justify-between sm:gap-6">
                    <div>
                        <h2 class="text-lg font-semibold text-brand-900">Ready to schedule your pickup?</h2>
                        <p class="mt-2 max-w-xl text-sm text-brand-800">
                            Pack your container using the pre-printed return label and upload exterior photos above. Once you're
                            packed, confirm below and our team will arrange the pickup at your home address.
                        </p>
                    </div>
                    <a href="https://www.fedex.com/en-us/shipping/schedule-manage-pickups.html" target="_blank" rel="noopener noreferrer"
                        class="mt-4 inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50 sm:mt-0">
                        FedEx pickup help
                    </a>
                </div>

                @error('pickup')
                    <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
                @enderror

                <div class="mt-5 border-t border-brand-100 pt-5">
                    @if ($pickupPhotosUploaded ?? false)
                        <form action="{{ route('student.move-tracking.schedule-pickup', $primaryContainer) }}" method="POST"
                            data-confirm="This confirms your container is fully packed and notifies our team to arrange your pickup."
                            data-confirm-title="Request pickup?"
                            data-confirm-button="Yes, request pickup"
                            data-confirm-icon="question">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                I'm packed — request pickup
                            </button>
                            <p class="mt-2 text-xs text-brand-700/80">This moves your move to “Pickup Scheduled” and notifies our team.</p>
                        </form>
                    @else
                        <p class="rounded-lg bg-white/70 px-3 py-2 text-sm text-brand-800">
                            Upload at least one container photo above to unlock pickup scheduling.
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
