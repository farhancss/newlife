@extends('layouts.app')

@php
    use App\Enums\AddOnStatus;

    $statusBadge = function (string $status): string {
        return match ($status) {
            AddOnStatus::ACTIVE => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            AddOnStatus::CANCELLED => 'bg-gray-100 text-gray-600 ring-gray-500/20',
            default => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        };
    };

    $revenue = '$' . number_format($stats['revenue_cents'] / 100, 2);
@endphp

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add-Ons</h1>
            <p class="mt-1 text-sm text-gray-600">Add-ons purchased by students. The Additional Container add-on provisions a trackable container.</p>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total purchases</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $stats['total'] }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $stats['active'] }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Trackable containers</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $stats['trackable'] }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active revenue</p>
                <p class="mt-1 text-2xl font-bold text-brand-500 sm:text-3xl">{{ $revenue }}</p>
            </div>
        </div>

        {{-- Purchases listing --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <x-portal.data-table table-class="min-w-[820px]">
                <thead>
                    <tr>
                        <th>Add-on</th>
                        <th>Student</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Purchased</th>
                        <th>Container</th>
                        <th data-sortable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($addOns as $addOn)
                        @php $student = $addOn->studentProfile; @endphp
                        <tr class="hover:bg-gray-50/80">
                            <td class="font-medium text-gray-900">{{ $addOn->name }}</td>
                            <td>
                                <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-gray-900 hover:text-brand-700">
                                    {{ $student->fullName() ?: $student->user?->name }}
                                </a>
                                <span class="mt-0.5 block font-mono text-xs text-gray-500">{{ $student->new_life_id }}</span>
                            </td>
                            <td class="text-sm text-gray-700">{{ $addOn->formattedPrice() }}</td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusBadge($addOn->status) }}">
                                    {{ $addOn->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-xs text-gray-700">{{ $addOn->requested_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-xs">
                                @if ($addOn->container)
                                    <a href="{{ route('admin.containers', ['q' => $addOn->container->code]) }}" class="font-medium text-brand-500 hover:underline">
                                        {{ $addOn->container->code }}
                                    </a>
                                    <span class="mt-0.5 block text-gray-500">{{ $addOn->container->statusLabel() }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="inline-flex items-center gap-1.5">
                                    <x-portal.action-button :href="route('admin.add-ons.show', $addOn)" icon="eye">View</x-portal.action-button>
                                    @if ($addOn->container)
                                        <x-portal.action-button :href="route('admin.containers', ['edit' => $addOn->container->id])" icon="box" variant="neutral">Container</x-portal.action-button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <p class="text-sm font-medium text-gray-900">No add-ons purchased yet</p>
                                <p class="mt-1 text-sm text-gray-500">Add-ons appear here once students buy them.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-portal.data-table>
        </div>
    </div>
@endsection
