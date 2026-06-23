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

    $iconPath = function (string $icon): string {
        return match ($icon) {
            'truck' => 'M3 9h11v6H3zM14 11h3.5L21 14v1h-7zM6.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM17.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
            'shield' => 'M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z',
            'sparkles' => 'M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3zM18 14l.8 2.2L21 17l-2.2.8L18 20l-.8-2.2L15 17l2.2-.8L18 14z',
            default => 'M3.5 8.5L12 4l8.5 4.5v7L12 20l-8.5-4.5v-7zM12 4v16M3.5 8.5L12 13l8.5-4.5',
        };
    };
@endphp

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Add-Ons</h1>
            <p class="mt-1 text-sm text-gray-600">
                Enhance your move with optional services. Payment is completed securely on the
                <span class="font-medium text-gray-700">New Life Campus</span> store, then our team activates your add-on.
            </p>
        </div>

        {{-- Available add-ons --}}
        <div>
            <h2 class="text-base font-semibold text-gray-900">Available add-ons</h2>
            <p class="mt-0.5 text-sm text-gray-500">Tap an add-on to complete your purchase on the New Life Campus store.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($catalog as $addOn)
                    <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="{{ $iconPath($addOn['icon']) }}" />
                                </svg>
                            </span>
                            <span class="rounded-full bg-gray-900 px-3 py-1 text-sm font-semibold text-white">{{ $addOn['formatted_price'] }}</span>
                        </div>

                        <h3 class="mt-4 font-semibold text-gray-900">{{ $addOn['name'] }}</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-gray-600">{{ $addOn['description'] }}</p>

                        <a href="{{ $addOn['url'] }}" target="_blank" rel="noopener"
                            class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            Add-On
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M7 17L17 7M17 7H9M17 7v8" />
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- My add-ons --}}
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">My add-ons</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Services you've requested and their current status.</p>
                </div>
            </div>

            @if ($purchases->isEmpty())
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-gray-500">You haven't requested any add-ons yet.</p>
                    <p class="mt-1 text-xs text-gray-400">Browse the options above to get started.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($purchases as $purchase)
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-gray-900">{{ $purchase->name }}</p>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusBadge($purchase->status) }}">
                                        {{ $purchase->statusLabel() }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $purchase->formattedPrice() }}
                                    @if ($purchase->requested_at)
                                        · Requested {{ $purchase->requested_at->format('M j, Y') }}
                                    @endif
                                    @if ($purchase->tracksContainer())
                                        · <span class="font-medium text-brand-500">Trackable · {{ $purchase->container->code }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('student.add-ons.show', $purchase) }}"
                                    class="inline-flex items-center justify-center rounded-lg border border-brand-500 px-3 py-1.5 text-xs font-medium text-brand-500 hover:bg-brand-50">
                                    View
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
