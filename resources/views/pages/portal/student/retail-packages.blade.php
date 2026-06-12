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

    $atCap = $activeCount >= $activeCap;
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

        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Retail Packages</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Log shipments from other retailers so our hub can accept and deliver them to your dorm.
                </p>
                <p class="mt-2 text-xs text-gray-500">
                    Ship to <span class="font-semibold text-gray-700">{{ $profile->fullName() ?: $profile->user?->name }}</span>
                    · New Life ID <span class="font-mono font-semibold text-gray-700">{{ $profile->new_life_id }}</span>
                    · <span class="font-semibold text-gray-700">{{ $activeCount }} of {{ $activeCap }}</span> active
                </p>
            </div>
            @if ($atCap)
                <span class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-500">Active limit reached</span>
            @else
                <a href="{{ route('student.retail-packages', ['add' => 1]) }}"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Add package
                </a>
            @endif
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">Before you ship</p>
            <ul class="mt-1.5 list-disc space-y-1 pl-5 text-amber-700">
                <li>Only packages logged here can be accepted at our hub — unlogged deliveries may be refused.</li>
                <li>Packages arriving after your move-in window may not be guaranteed.</li>
                <li>Prohibited and restricted items (hazardous materials, perishables, weapons) are not accepted.</li>
            </ul>
        </div>

        <x-portal.data-table table-class="min-w-[820px]">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Retailer</th>
                    <th>Tracking #</th>
                    <th>Status</th>
                    <th>ETA</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($packages as $package)
                    @php $trackUrl = $carrierLinkBuilder->build($package->retailer, $package->tracking_number); @endphp
                    <tr class="hover:bg-gray-50/80">
                        <td class="font-medium text-gray-900">{{ $package->description }}</td>
                        <td>{{ $package->retailer }}</td>
                        <td class="font-mono text-xs">
                            @if ($trackUrl)
                                <a href="{{ $trackUrl }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline">
                                    {{ $package->tracking_number }}
                                </a>
                            @else
                                {{ $package->tracking_number }}
                            @endif
                        </td>
                        <td>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge($package->status) }}">
                                {{ $package->statusLabel() }}
                            </span>
                        </td>
                        <td class="text-xs text-gray-700">
                            {{ $package->estimated_arrival ? $package->estimated_arrival->format('M j, Y') : '—' }}
                        </td>
                        <td class="text-right whitespace-nowrap">
                            @if ($package->isEditable())
                                <a href="{{ route('student.retail-packages', ['edit' => $package->id]) }}"
                                    class="inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                                    Edit
                                </a>
                                <form action="{{ route('student.retail-packages.destroy', $package) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Remove this package?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                                        Remove
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">Locked</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
                            <p class="text-sm font-medium text-gray-900">No packages logged yet</p>
                            <p class="mt-1 text-sm text-gray-500">Add a package to let our hub know what to expect.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-portal.data-table>
    </div>

    {{-- Add / edit drawer (rendered at body level so it overlays the sidebar) --}}
    @push('modals')
        @if ($showForm)
            @php
                $closeUrl = route('student.retail-packages');
                $isEdit = $editing !== null;
                $action = $isEdit ? route('student.retail-packages.update', $editing) : route('student.retail-packages.store');
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $isEdit ? 'Edit package' : 'Add package' }}</p>
                            <h2 class="text-xl font-bold text-gray-900">{{ $isEdit ? $editing->description : 'New retail package' }}</h2>
                        </div>
                        <a href="{{ $closeUrl }}" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    </div>

                    <div class="flex-1 p-5">
                        <form action="{{ $action }}" method="POST" class="space-y-4">
                            @csrf
                            @if ($isEdit)
                                @method('PUT')
                            @endif

                            <div>
                                <label for="retailer" class="mb-1.5 block text-sm font-medium text-gray-700">Retailer</label>
                                <select id="retailer" name="retailer" class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm">
                                    <option value="">Select a retailer…</option>
                                    @foreach ($retailers as $retailer)
                                        <option value="{{ $retailer }}" @selected(old('retailer', $editing->retailer ?? '') === $retailer)>{{ $retailer }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700">Item description</label>
                                <input id="description" name="description" type="text" value="{{ old('description', $editing->description ?? '') }}"
                                    placeholder="e.g. Mini fridge, dorm size"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm" />
                            </div>

                            <div>
                                <label for="tracking_number" class="mb-1.5 block text-sm font-medium text-gray-700">Tracking number</label>
                                <input id="tracking_number" name="tracking_number" type="text" value="{{ old('tracking_number', $editing->tracking_number ?? '') }}"
                                    class="h-11 w-full rounded-xl border border-gray-300 px-3 text-sm font-mono" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="estimated_arrival">Estimated arrival date</label>
                                <x-form.flatpickr-input
                                    name="estimated_arrival"
                                    :value="old('estimated_arrival', $editing?->estimated_arrival?->format('Y-m-d'))"
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
                                <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                                <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">{{ old('notes', $editing->notes ?? '') }}</textarea>
                            </div>

                            @unless ($acknowledged)
                                <label class="flex items-start gap-2 rounded-xl bg-gray-50 p-3 text-sm text-gray-700">
                                    <input type="checkbox" name="acknowledge" value="1" class="mt-0.5 rounded border-gray-300 text-brand-600" />
                                    <span>I understand that only packages logged here can be accepted, and that prohibited or restricted items will be refused.</span>
                                </label>
                            @endunless

                            <button type="submit" class="w-full rounded-xl bg-brand-600 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                                {{ $isEdit ? 'Save changes' : 'Log package' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endpush
@endsection
