@extends('layouts.app')

@php
    use App\Enums\RetailPackageStatus;

    $statusBadge = function (string $status): string {
        return match ($status) {
            RetailPackageStatus::LOGGED => 'bg-gray-100 text-gray-700',
            RetailPackageStatus::IN_TRANSIT => 'bg-brand-50 text-brand-700',
            RetailPackageStatus::RECEIVED_AT_HUB => 'bg-indigo-50 text-indigo-700',
            RetailPackageStatus::STAGED_FOR_DELIVERY => 'bg-amber-50 text-amber-700',
            RetailPackageStatus::DELIVERED_TO_DORM => 'bg-emerald-50 text-emerald-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $totalPackages = $packages->count();
    $activePackages = $packages->where('status', '!=', RetailPackageStatus::DELIVERED_TO_DORM)->count();
    $deliveredPackages = $packages->where('status', RetailPackageStatus::DELIVERED_TO_DORM)->count();
    $filters = array_filter(['q' => $search ?: null, 'status' => $statusFilter ?: null, 'retailer' => $retailerFilter ?: null]);
    $showAdd = request()->boolean('add') && ! $editing;
@endphp

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success" :message="session('status')" />
        @endif

        @if ($errors->any())
            <x-ui.alert variant="error" title="Could not save">
                <ul class="mt-2 list-disc pl-5 text-sm text-gray-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Retail package operations</h1>
                <p class="mt-1 text-sm text-gray-600">Receive, stage, and deliver student-logged retail shipments.</p>
            </div>
            <a href="{{ route('admin.retail-packages', ['add' => 1]) }}"
                class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Add on behalf
            </a>
        </div>

        <div class="flex flex-row gap-3">
            <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Packages</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $totalPackages }}</p>
            </div>
            <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active</p>
                <p class="mt-1 text-2xl font-bold text-brand-700 sm:text-3xl">{{ $activePackages }}</p>
            </div>
            <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Delivered</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600 sm:text-3xl">{{ $deliveredPackages }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.retail-packages') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search tracking, item, student…"
                class="h-11 flex-1 rounded-xl border border-gray-300 px-4 text-sm shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-500/20" />
            <select name="status" class="h-11 rounded-xl border border-gray-300 px-3 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($statusFilter === $status)>{{ RetailPackageStatus::label($status) }}</option>
                @endforeach
            </select>
            <select name="retailer" class="h-11 rounded-xl border border-gray-300 px-3 text-sm">
                <option value="">All retailers</option>
                @foreach ($retailers as $retailer)
                    <option value="{{ $retailer }}" @selected($retailerFilter === $retailer)>{{ $retailer }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-11 rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Filter
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <x-portal.data-table table-class="min-w-[920px]">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Retailer</th>
                        <th>Item</th>
                        <th>Tracking #</th>
                        <th>Status</th>
                        <th>ETA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        @php
                            $student = $package->studentProfile;
                            $trackUrl = $carrierLinkBuilder->build($package->retailer, $package->tracking_number);
                        @endphp
                        <tr class="hover:bg-gray-50/80">
                            <td>
                                <span class="font-medium text-gray-900">{{ $student->fullName() ?: $student->user?->name }}</span>
                                <span class="mt-0.5 block font-mono text-xs text-gray-500">{{ $student->new_life_id }}</span>
                            </td>
                            <td>{{ $package->retailer }}</td>
                            <td class="text-gray-700">{{ $package->description }}</td>
                            <td class="font-mono text-xs">
                                @if ($trackUrl)
                                    <a href="{{ $trackUrl }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline">{{ $package->tracking_number }}</a>
                                @else
                                    {{ $package->tracking_number }}
                                @endif
                            </td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge($package->status) }}">
                                    {{ $package->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-xs text-gray-700">{{ $package->estimated_arrival ? $package->estimated_arrival->format('M j, Y') : '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.retail-packages', array_merge($filters, ['edit' => $package->id])) }}"
                                    class="inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <p class="text-sm font-medium text-gray-900">No packages found</p>
                                <p class="mt-1 text-sm text-gray-500">Packages appear here as students log them.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-portal.data-table>
        </div>
    </div>

    {{-- Manage drawer --}}
    @push('modals')
        @if ($editing)
            @php
                $closeUrl = route('admin.retail-packages', $filters);
                $editStudent = $editing->studentProfile;
            @endphp
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Manage package</p>
                            <h2 class="text-xl font-bold text-gray-900">{{ $editing->description }}</h2>
                        </div>
                        <a href="{{ $closeUrl }}" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    </div>

                    <div class="flex-1 p-5">
                        <div class="mb-6 rounded-xl bg-gray-50 p-4 text-sm">
                            <p class="font-semibold text-gray-900">{{ $editStudent->fullName() ?: $editStudent->user?->name }}</p>
                            <p class="font-mono text-gray-600">{{ $editStudent->new_life_id }}</p>
                            <p class="mt-2 text-gray-600">{{ $editing->retailer }} · <span class="font-mono">{{ $editing->tracking_number }}</span></p>
                        </div>

                        <form action="{{ route('admin.retail-packages.update', $editing) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700">Workflow status</label>
                                <select id="status" name="status" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(old('status', $editing->status) === $status)>
                                            {{ RetailPackageStatus::label($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="status_note" class="mb-1.5 block text-sm font-medium text-gray-700">Status note (audit)</label>
                                <input id="status_note" name="status_note" type="text" value="{{ old('status_note') }}"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm" />
                            </div>

                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="force_status" value="1" class="rounded border-gray-300 text-brand-600" />
                                Allow backward status override
                            </label>

                            <button type="submit" class="w-full rounded-xl bg-brand-600 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                                Save status
                            </button>
                        </form>

                        @if ($editing->statusHistories->isNotEmpty())
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                <h3 class="text-sm font-semibold text-gray-900">Status history</h3>
                                <ul class="mt-3 max-h-56 space-y-3 overflow-y-auto">
                                    @foreach ($editing->statusHistories->take(8) as $history)
                                        <li class="rounded-lg bg-gray-50 px-3 py-2 text-xs">
                                            <span class="font-semibold text-gray-800">{{ $history->toStatusLabel() }}</span>
                                            <span class="text-gray-500"> · {{ $history->created_at->format('M j, g:i A') }}</span>
                                            @if ($history->changedBy)
                                                <span class="text-gray-500"> · {{ $history->changedBy->name }}</span>
                                            @endif
                                            @if ($history->note)
                                                <p class="mt-1 text-gray-600">{{ $history->note }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mt-8 border-t border-gray-100 pt-6">
                            <h3 class="text-sm font-semibold text-red-700">Remove package</h3>
                            <form action="{{ route('admin.retail-packages.destroy', $editing) }}" method="POST" class="mt-3 space-y-3"
                                onsubmit="return confirm('Remove this package? This requires a reason.');">
                                @csrf
                                @method('DELETE')
                                <input name="removed_reason" type="text" value="{{ old('removed_reason') }}" required
                                    placeholder="Reason for removal"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm" />
                                <button type="submit" class="w-full rounded-xl border border-red-200 bg-red-50 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">
                                    Remove package
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Add-on-behalf drawer --}}
        @if ($showAdd)
            @php $closeUrl = route('admin.retail-packages', $filters); @endphp
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Add on behalf</p>
                            <h2 class="text-xl font-bold text-gray-900">New retail package</h2>
                        </div>
                        <a href="{{ $closeUrl }}" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    </div>

                    <div class="flex-1 p-5">
                        <form action="{{ route('admin.retail-packages.store') }}" method="POST" class="space-y-4">
                            @csrf

                            <div>
                                <label for="student_profile_id" class="mb-1.5 block text-sm font-medium text-gray-700">Student</label>
                                <select id="student_profile_id" name="student_profile_id" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm">
                                    <option value="">Select a student…</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}" @selected(old('student_profile_id') == $student->id)>
                                            {{ $student->fullName() ?: $student->user?->name }} ({{ $student->new_life_id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="add_retailer" class="mb-1.5 block text-sm font-medium text-gray-700">Retailer</label>
                                <select id="add_retailer" name="retailer" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm">
                                    <option value="">Select a retailer…</option>
                                    @foreach ($retailers as $retailer)
                                        <option value="{{ $retailer }}" @selected(old('retailer') === $retailer)>{{ $retailer }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="add_description" class="mb-1.5 block text-sm font-medium text-gray-700">Item description</label>
                                <input id="add_description" name="description" type="text" value="{{ old('description') }}"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm" />
                            </div>

                            <div>
                                <label for="add_tracking_number" class="mb-1.5 block text-sm font-medium text-gray-700">Tracking number</label>
                                <input id="add_tracking_number" name="tracking_number" type="text" value="{{ old('tracking_number') }}"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm font-mono" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="add_estimated_arrival">Estimated arrival date</label>
                                <x-form.flatpickr-input
                                    name="estimated_arrival"
                                    :value="old('estimated_arrival')"
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
                                <label for="add_notes" class="mb-1.5 block text-sm font-medium text-gray-700">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                                <textarea id="add_notes" name="notes" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-brand-600 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                                Add package
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endpush
@endsection
