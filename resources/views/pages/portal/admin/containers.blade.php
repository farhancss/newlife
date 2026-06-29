@extends('layouts.app')

@section('content')
    @php
        $totalContainers = $containers->count();
        $inTransit = $containers->filter(fn ($c) => in_array($c->status, [
            \App\Enums\ContainerStatus::SHIPPED_TO_HOME,
            \App\Enums\ContainerStatus::RETURN_SHIPMENT_IN_TRANSIT,
            \App\Enums\ContainerStatus::OUT_FOR_DELIVERY,
        ], true))->count();
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Container operations</h1>
                <p class="mt-1 text-sm text-gray-600">Containers are auto-assigned at onboarding. Update workflow status, tracking, and dates here.</p>
            </div>
        </div>

        {{-- Stats — always inline on admin viewport --}}
        <div class="flex flex-row gap-3">
            <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Move shipments</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $totalContainers }}</p>
            </div>
            <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">In transit</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $inTransit }}</p>
            </div>
            <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Students loaded</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $studentsLoaded }}</p>
            </div>
        </div>

        <div>
            {{-- Table --}}
            <div>
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <x-portal.data-table table-class="min-w-[760px]">
                        <thead>
                            <tr>
                                <th>Container</th>
                                <th>Student / package</th>
                                <th>Status</th>
                                <th>Ship by</th>
                                <th>Tracking</th>
                                <th data-sortable="false"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($containers as $container)
                                @php
                                    $student = $container->studentProfile;
                                    $pkg = $student->package;
                                    $outUrl = $fedExLinkService->trackingUrl($container->outbound_tracking);
                                @endphp
                                <tr class="hover:bg-gray-50/80">
                                    <td>
                                        <span class="font-semibold text-gray-900">{{ $container->code }}</span>
                                        @if ($container->isAddOn())
                                            <span class="mt-0.5 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Add-on</span>
                                        @elseif ($pkg)
                                            <span class="mt-0.5 block text-xs text-gray-500">Includes {{ $pkg->container_count }} {{ \Illuminate\Support\Str::plural('container', $pkg->container_count) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-medium text-gray-900">{{ $student->fullName() ?: $student->user?->name }}</span>
                                        <span class="mt-0.5 block text-xs text-gray-500">{{ $student->new_life_id }}</span>
                                        @if ($pkg)
                                            <span class="mt-1 inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-500">
                                                {{ $pkg->shortLabel() }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="inline-flex max-w-[140px] rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold leading-snug text-gray-800">
                                            {{ $container->statusLabel() }}
                                        </span>
                                        @if ($container->location)
                                            <span class="mt-1 block text-xs text-gray-500">{{ $container->location }}</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-gray-700">
                                        {{ $container->ship_by_date ? $container->ship_by_date->format('M j, Y') : '—' }}
                                    </td>
                                    <td class="text-xs">
                                        @if ($container->outbound_tracking)
                                            <a href="{{ $outUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-500 hover:underline">
                                                {{ $container->outbound_tracking }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.containers', ['edit' => $container->id, 'q' => $search ?: null]) }}"
                                            class="inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-500 hover:bg-brand-50">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-16 text-center">
                                        <p class="text-sm font-medium text-gray-900">No containers yet</p>
                                        <p class="mt-1 text-sm text-gray-500">Containers appear automatically once a student completes onboarding.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-portal.data-table>
                </div>
            </div>
        </div>

    </div>

    {{-- Edit drawer (rendered at body level so it overlays the sidebar) --}}
    @push('modals')
        @if ($editing)
            @php $closeUrl = route('admin.containers', array_filter(['q' => $search ?: null])); @endphp
            <div class="fixed inset-0 z-[100000] flex justify-end"
                x-data="{ open: false }"
                x-init="$nextTick(() => open = true); document.body.style.overflow = 'hidden'"
                x-cloak
                @keydown.escape.window="window.location='{{ $closeUrl }}'">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"
                    x-show="open" x-transition.opacity.duration.200ms
                    @click="window.location='{{ $closeUrl }}'"></div>
                <div class="relative flex h-full w-full max-w-lg flex-col overflow-y-auto bg-white shadow-2xl"
                    x-show="open"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full">
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Edit container</p>
                            <h2 class="text-xl font-bold text-gray-900">{{ $editing->code }}</h2>
                        </div>
                        <a href="{{ route('admin.containers', array_filter(['q' => $search ?: null])) }}"
                            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    </div>

                    <div class="flex-1 p-5">
                        @php $editStudent = $editing->studentProfile; @endphp
                        <div class="mb-6 rounded-xl bg-gray-50 p-4 text-sm">
                            <p class="font-semibold text-gray-900">{{ $editStudent->fullName() ?: $editStudent->user?->name }}</p>
                            <p class="text-gray-600">{{ $editStudent->new_life_id }}</p>
                            @if ($editing->isAddOn())
                                <p class="mt-2 inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    Additional Container (add-on)
                                </p>
                            @elseif ($editStudent->package)
                                <p class="mt-2 text-brand-700">{{ $editStudent->package->name }} · {{ $editStudent->package->container_count }} {{ \Illuminate\Support\Str::plural('container', $editStudent->package->container_count) }}</p>
                            @endif
                        </div>

                        <form action="{{ route('admin.containers.update', $editing) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700">Workflow status</label>
                                <select id="status" name="status" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(old('status', $editing->status) === $status)>
                                            {{ \App\Enums\ContainerStatus::label($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="location" class="mb-1.5 block text-sm font-medium text-gray-700">Location</label>
                                <input id="location" name="location" type="text" value="{{ old('location', $editing->location) }}"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="ship_by_date">Ship by date</label>
                                <x-form.flatpickr-input
                                    name="ship_by_date"
                                    :value="old('ship_by_date', $editing->ship_by_date?->format('Y-m-d'))"
                                    placeholder="Select a date"
                                    icon="calendar"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 pr-10 text-sm focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-200"
                                    :options="[
                                        'dateFormat' => 'Y-m-d',
                                        'altInput' => true,
                                        'altFormat' => 'F j, Y',
                                    ]"
                                />
                            </div>

                            <div>
                                <label for="outbound_tracking" class="mb-1.5 block text-sm font-medium text-gray-700">Outbound FedEx tracking</label>
                                <input id="outbound_tracking" name="outbound_tracking" type="text" value="{{ old('outbound_tracking', $editing->outbound_tracking) }}"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm font-mono" placeholder="7946…" />
                            </div>

                            <div>
                                <label for="return_tracking" class="mb-1.5 block text-sm font-medium text-gray-700">Return FedEx tracking</label>
                                <input id="return_tracking" name="return_tracking" type="text" value="{{ old('return_tracking', $editing->return_tracking) }}"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm font-mono" />
                            </div>

                            <div>
                                <label for="status_note" class="mb-1.5 block text-sm font-medium text-gray-700">Status note (audit)</label>
                                <input id="status_note" name="status_note" type="text" value="{{ old('status_note') }}"
                                    placeholder="Add a note to log with this update…"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm" />

                                @php $savedNotes = $editing->statusHistories->filter(fn ($h) => filled($h->note)); @endphp
                                @if ($savedNotes->isNotEmpty())
                                    <div class="mt-2 space-y-2 rounded-xl border border-gray-100 bg-gray-50 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Saved notes</p>
                                        <ul class="space-y-2">
                                            @foreach ($savedNotes->take(6) as $history)
                                                <li class="text-xs text-gray-700">
                                                    <p>{{ $history->note }}</p>
                                                    <p class="mt-0.5 text-[11px] text-gray-400">
                                                        {{ $history->toStatusLabel() }} · {{ $history->created_at->format('M j, Y · g:i A') }}@if ($history->changedBy) · {{ $history->changedBy->name }}@endif
                                                    </p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label for="internal_notes" class="mb-1.5 block text-sm font-medium text-gray-700">Internal notes</label>
                                <textarea id="internal_notes" name="internal_notes" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">{{ old('internal_notes', $editing->internal_notes) }}</textarea>
                            </div>

                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="force_status" value="1" class="rounded border-gray-300 text-brand-500" />
                                Allow backward status override
                            </label>

                            <button type="submit" class="w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                                Save changes
                            </button>
                        </form>

                        {{-- Student container photos --}}
                        @php
                            $studentPhotos = $editing->photos->where('type', \App\Models\ContainerPhoto::TYPE_EXTERIOR)->values();
                        @endphp
                        <div class="mt-8 border-t border-gray-100 pt-6">
                            <h3 class="text-sm font-semibold text-gray-900">Container photos
                                <span class="font-normal text-gray-500">({{ $studentPhotos->count() }})</span>
                            </h3>
                            @if ($studentPhotos->isNotEmpty())
                                <div class="mt-3 grid grid-cols-3 gap-2">
                                    @foreach ($studentPhotos as $photo)
                                        <a href="{{ $photo->url() }}" data-gallery="admin-container-{{ $editing->id }}"
                                            class="glightbox group relative block overflow-hidden rounded-lg border border-gray-200">
                                            <img src="{{ $photo->url() }}" alt="Container photo"
                                                class="h-24 w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                        </a>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Uploaded by the student during Student Packing.</p>
                            @else
                                <p class="mt-3 text-sm text-gray-500">No photos uploaded yet. Students can upload exterior photos once the container reaches Student Packing.</p>
                            @endif
                        </div>

                        {{-- New Life hub evidence photos (admin upload) --}}
                        <x-admin.hub-photos :container="$editing" class="mt-8 border-0 p-0" />

                        @if ($editing->statusHistories->isNotEmpty())
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                <h3 class="text-sm font-semibold text-gray-900">Recent history</h3>
                                <ul class="mt-3 max-h-56 space-y-3 overflow-y-auto">
                                    @foreach ($editing->statusHistories->take(8) as $history)
                                        <li class="rounded-lg bg-gray-50 px-3 py-2 text-xs">
                                            <span class="font-semibold text-gray-800">{{ $history->toStatusLabel() }}</span>
                                            <span class="text-gray-500"> · {{ $history->created_at->format('M j, g:i A') }}</span>
                                            @if ($history->note)
                                                <p class="mt-1 text-gray-600">{{ $history->note }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endpush
@endsection
