@extends('layouts.app')

@php
    use App\Enums\StoragePickupStatus;

    $statusBadge = function (string $status): string {
        return match ($status) {
            StoragePickupStatus::REQUESTED => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            StoragePickupStatus::SCHEDULED => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            StoragePickupStatus::PICKED_UP,
            StoragePickupStatus::OUT_FOR_RETURN => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            StoragePickupStatus::IN_STORAGE => 'bg-brand-50 text-brand-700 ring-brand-600/20',
            StoragePickupStatus::RETURNED => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            StoragePickupStatus::CANCELLED => 'bg-gray-100 text-gray-600 ring-gray-500/20',
            default => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        };
    };
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">End-of-year storage pickups</h1>
                <p class="mt-1 text-sm text-gray-600">Students with storage schedule a dorm pickup at the end of the academic year. Confirm dates and track containers from dorm to storage and back.</p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $stats['total'] }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Awaiting confirmation</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $stats['requested'] }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">In storage</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $stats['in_storage'] }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Returned</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $stats['returned'] }}</p>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <x-portal.data-table table-class="min-w-[820px]">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Pickup location</th>
                        <th>Requested date</th>
                        <th>Confirmed date</th>
                        <th>Status</th>
                        <th data-sortable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pickups as $pickup)
                        @php $student = $pickup->studentProfile; @endphp
                        <tr class="hover:bg-gray-50/80">
                            <td>
                                <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-gray-900 hover:text-brand-700">
                                    {{ $student->fullName() ?: $student->user?->name }}
                                </a>
                                <span class="mt-0.5 block font-mono text-xs text-gray-500">{{ $student->new_life_id }}</span>
                            </td>
                            <td class="text-sm text-gray-700">
                                {{ $pickup->pickup_location }}
                                @if ($pickup->container_count)
                                    <span class="mt-0.5 block text-xs text-gray-500">{{ $pickup->container_count }} {{ \Illuminate\Support\Str::plural('container', $pickup->container_count) }}</span>
                                @endif
                            </td>
                            <td class="text-xs text-gray-700">{{ $pickup->requested_pickup_date->format('M j, Y') }}</td>
                            <td class="text-xs text-gray-700">{{ $pickup->confirmed_pickup_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusBadge($pickup->status) }}">
                                    {{ $pickup->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                <x-portal.action-button :href="route('admin.storage-pickups', ['edit' => $pickup->id, 'q' => $search ?: null])" icon="edit">Manage</x-portal.action-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <p class="text-sm font-medium text-gray-900">No storage pickups yet</p>
                                <p class="mt-1 text-sm text-gray-500">Requests appear here once students schedule an end-of-year pickup.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-portal.data-table>
        </div>
    </div>

    {{-- Edit drawer --}}
    @push('modals')
        @if ($editing)
            @php $closeUrl = route('admin.storage-pickups', array_filter(['q' => $search ?: null])); @endphp
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Manage pickup</p>
                            <h2 class="text-xl font-bold text-gray-900">{{ $editing->studentProfile->fullName() ?: $editing->studentProfile->user?->name }}</h2>
                        </div>
                        <a href="{{ $closeUrl }}" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    </div>

                    <div class="flex-1 p-5">
                        <div class="mb-6 space-y-3 rounded-xl bg-gray-50 p-4 text-sm">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">New Life ID</p>
                                <p class="font-medium text-gray-900">{{ $editing->studentProfile->new_life_id }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pickup location</p>
                                <p class="text-gray-800">{{ $editing->pickup_location }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Requested date</p>
                                    <p class="text-gray-800">{{ $editing->requested_pickup_date->format('M j, Y') }}</p>
                                </div>
                                @if ($editing->contact_phone)
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</p>
                                        <p class="text-gray-800">{{ $editing->contact_phone }}</p>
                                    </div>
                                @endif
                                @if ($editing->container_count)
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Containers</p>
                                        <p class="text-gray-800">{{ $editing->container_count }}</p>
                                    </div>
                                @endif
                                @if ($editing->container)
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Move container</p>
                                        <a href="{{ route('admin.containers', ['q' => $editing->container->code]) }}" class="font-medium text-brand-500 hover:underline">{{ $editing->container->code }}</a>
                                    </div>
                                @endif
                            </div>
                            @if ($editing->notes)
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Student notes</p>
                                    <p class="text-gray-800">{{ $editing->notes }}</p>
                                </div>
                            @endif
                        </div>

                        <form action="{{ route('admin.storage-pickups.update', $editing) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
                                <select id="status" name="status" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(old('status', $editing->status) === $status)>
                                            {{ StoragePickupStatus::label($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="confirmed_pickup_date">Confirmed pickup date</label>
                                <x-form.flatpickr-input
                                    name="confirmed_pickup_date"
                                    :value="old('confirmed_pickup_date', $editing->confirmed_pickup_date?->format('Y-m-d'))"
                                    placeholder="Select a date"
                                    icon="calendar"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 pr-10 text-sm focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-200"
                                    :options="[
                                        'dateFormat' => 'Y-m-d',
                                        'altInput' => true,
                                        'altFormat' => 'F j, Y',
                                        'minDate' => $editing->requested_pickup_date->format('Y-m-d'),
                                    ]"
                                />
                                <p class="mt-1 text-xs text-gray-400">Must be on or after the student's requested date ({{ $editing->requested_pickup_date->format('M j, Y') }}).</p>
                            </div>

                            <div>
                                <label for="admin_notes" class="mb-1.5 block text-sm font-medium text-gray-700">Internal notes</label>
                                <textarea id="admin_notes" name="admin_notes" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">{{ old('admin_notes', $editing->admin_notes) }}</textarea>
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                                Save &amp; notify student
                            </button>
                        </form>

                        @if ($editing->confirmed_at)
                            <p class="mt-4 text-xs text-gray-500">
                                Confirmed {{ $editing->confirmed_at->format('M j, Y · g:i A') }}@if ($editing->confirmedBy) by {{ $editing->confirmedBy->name }}@endif.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endpush
@endsection
