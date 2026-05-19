@extends('layouts.app')

@php
    $firstName = strtok(auth()->user()->name ?? 'John', ' ');
@endphp

@section('content')
    <div class="space-y-6">
        <p class="text-lg font-semibold text-gray-900">
            Welcome, {{ $firstName }}! <span class="font-normal text-gray-500">· Essential Package</span>
        </p>

        {{-- Move-In Progress --}}
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-6 sm:px-8">
            <h2 class="text-base font-semibold text-gray-900">Move-In Progress</h2>

            <div class="mt-10 overflow-x-auto pb-2">
                <div class="relative min-w-[640px]">
                    <div class="absolute left-[8.3%] right-[8.3%] top-5 h-px bg-gray-200" aria-hidden="true"></div>
                    <div class="absolute left-[8.3%] top-5 h-px w-[33%] bg-brand-300" aria-hidden="true"></div>

                    <ol class="relative grid grid-cols-6 gap-2">
                        @php
                            $steps = [
                                ['label' => 'Reservation Confirmed', 'state' => 'done'],
                                ['label' => 'Profile Completed', 'state' => 'done-icon'],
                                ['label' => 'Containers Preparing', 'state' => 'active'],
                                ['label' => 'Containers Shipped', 'state' => 'pending'],
                                ['label' => 'Delivered to Home', 'state' => 'pending'],
                                ['label' => 'Dorm Delivery', 'state' => 'pending'],
                            ];
                        @endphp

                        @foreach ($steps as $step)
                            <li class="flex flex-col items-center text-center">
                                @if ($step['state'] === 'done')
                                    <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-300 text-white shadow-sm">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @elseif ($step['state'] === 'done-icon')
                                    <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </span>
                                @elseif ($step['state'] === 'active')
                                    <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-800 text-sm font-semibold text-white">3</span>
                                @else
                                    <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 border-gray-300 bg-white"></span>
                                @endif
                                <p class="mt-3 max-w-[108px] text-xs leading-snug text-gray-600">{{ $step['label'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>

        {{-- Three summary cards --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="flex min-h-[200px] flex-col rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-base font-semibold text-gray-900">Action Items</h3>
                <ul class="mt-5 flex-1 space-y-3.5">
                    @foreach ([
                        ['label' => 'Complete Profile', 'done' => true],
                        ['label' => 'Add Retail Packages', 'done' => false],
                        ['label' => 'Select Move-in Window', 'done' => false],
                    ] as $item)
                        <li class="flex items-start gap-3 text-sm text-gray-800">
                            @if ($item['done'])
                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border border-brand-500 bg-brand-500 text-white">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            @else
                                <span class="mt-0.5 h-4 w-4 shrink-0 rounded border border-gray-300 bg-white"></span>
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('student.profile') }}" class="mt-5 text-right text-sm font-medium text-brand-600 hover:text-brand-700">View All</a>
            </div>

            <div class="flex min-h-[200px] flex-col rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-base font-semibold text-gray-900">Upcoming Deadlines</h3>
                <ul class="mt-5 flex-1 space-y-3.5 text-sm text-gray-800">
                    <li class="flex items-center justify-between gap-3">
                        <span>Profile Completion</span>
                        <span class="shrink-0 text-gray-500">May 15, 2026</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span>Add Retail Packages</span>
                        <span class="shrink-0 text-gray-500">May 20, 2026</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span>Move-in Window</span>
                        <span class="shrink-0 text-gray-500">Jun 10, 2026</span>
                    </li>
                </ul>
                <a href="#" class="mt-5 text-right text-sm font-medium text-brand-600 hover:text-brand-700">View All</a>
            </div>

            <div class="flex min-h-[200px] flex-col rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-base font-semibold text-gray-900">Latest Update</h3>
                <div class="mt-5 flex-1">
                    <p class="text-sm font-medium text-gray-900">Containers are being prepared</p>
                    <p class="mt-2 text-sm text-gray-500">May 19, 2026</p>
                </div>
                <a href="{{ route('student.notifications') }}" class="mt-5 text-right text-sm font-medium text-brand-600 hover:text-brand-700">View All</a>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $quickLinks = [
                    [
                        'title' => 'Retail Packages',
                        'subtitle' => 'View & Manage',
                        'href' => route('student.retail-packages'),
                        'icon' => 'package',
                    ],
                    [
                        'title' => 'Track Shipments',
                        'subtitle' => 'View Status',
                        'href' => route('student.move-tracking'),
                        'icon' => 'truck',
                    ],
                    [
                        'title' => 'Add-Ons',
                        'subtitle' => 'Explore Options',
                        'href' => route('student.add-ons'),
                        'icon' => 'addons',
                    ],
                    [
                        'title' => 'Help & Support',
                        'subtitle' => 'Get Help',
                        'href' => route('student.support'),
                        'icon' => 'support',
                    ],
                ];
            @endphp

            @foreach ($quickLinks as $link)
                <a href="{{ $link['href'] }}" class="flex flex-col items-center rounded-xl border border-gray-200 bg-white px-4 py-7 text-center transition hover:border-brand-200 hover:shadow-theme-xs">
                    <span class="mb-4 flex h-11 w-11 items-center justify-center text-gray-700">
                        @if ($link['icon'] === 'package')
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9-4 9 4-9 4-9-4zm0 0v8l9 4 9-4V8"/>
                            </svg>
                        @elseif ($link['icon'] === 'truck')
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 104 0M3 13V6a1 1 0 011-1h11v8M3 13h13m0 0l2.5 5H21M3 13l1.5-3h4"/>
                            </svg>
                        @elseif ($link['icon'] === 'addons')
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24">
                                <circle cx="8" cy="8" r="2.5" stroke="currentColor"/>
                                <circle cx="16" cy="8" r="2.5" stroke="currentColor"/>
                                <circle cx="8" cy="16" r="2.5" stroke="currentColor"/>
                                <circle cx="16" cy="16" r="2.5" stroke="currentColor"/>
                            </svg>
                        @else
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a6 6 0 00-6 6c0 3.5 6 10 6 10s6-6.5 6-10a6 6 0 00-6-6z"/>
                                <circle cx="12" cy="9" r="2"/>
                            </svg>
                        @endif
                    </span>
                    <h4 class="text-sm font-semibold text-gray-900">{{ $link['title'] }}</h4>
                    <p class="mt-1 text-sm text-gray-500">{{ $link['subtitle'] }}</p>
                </a>
            @endforeach
        </div>
    </div>
@endsection
