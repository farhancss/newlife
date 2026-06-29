@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold text-gray-900">Dashboard Overview</h1>
            <p class="text-sm text-gray-500">A live snapshot of students, moves, and what needs your attention.</p>
        </div>

        {{-- Summary cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $stat)
                @php
                    $tone = $stat['tone'] ?? 'brand';
                    $toneClasses = match ($tone) {
                        'info' => 'bg-blue-light-50 text-blue-light-600',
                        'warning' => 'bg-warning-50 text-warning-600',
                        'success' => 'bg-success-50 text-success-600',
                        default => 'bg-brand-50 text-brand-600',
                    };
                    $iconPath = match ($stat['icon'] ?? '') {
                        'truck' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 10.5h12V18H2.25zM14.25 13.5h3l3 3V18h-6zM6 18a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM18.75 18a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />',
                        'box' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25l-9-4.5-9 4.5m18 0l-9 4.5m9-4.5v7.5l-9 4.5m0-12l-9-4.5m9 4.5v12m-9-12v7.5l9 4.5" />',
                        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                        default => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5a6 6 0 00-12 0M21 19.5a4.5 4.5 0 00-6.75-3.9M12 7.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 8.25a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />',
                    };
                @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($stat['value']) }}</p>
                        </div>
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $toneClasses }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">{!! $iconPath !!}</svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">{{ $stat['trend'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Growth + package mix charts --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs lg:col-span-2">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Student Signups</h2>
                        <p class="mt-0.5 text-sm text-gray-500">New registrations over the last 8 weeks</p>
                    </div>
                    <span class="text-sm font-medium text-gray-500">{{ number_format($signupTrend['total']) }} total</span>
                </div>
                <div
                    class="mt-4"
                    data-chart="signup-trend"
                    data-categories='@json($signupTrend['categories'])'
                    data-values='@json($signupTrend['data'])'
                ></div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="text-base font-semibold text-gray-900">Package Mix</h2>
                <p class="mt-0.5 text-sm text-gray-500">Students by package tier</p>
                <div
                    class="mt-2"
                    data-chart="package-mix"
                    data-labels='@json($packageMix['labels'])'
                    data-series='@json($packageMix['series'])'
                    data-colors='@json($packageMix['colors'])'
                ></div>
            </div>
        </div>

        {{-- Recent activity + move status --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Recent Activity --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-xs lg:col-span-2">
                <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Recent Activity</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Latest events across students, packages, and deliveries</p>
                    </div>
                    <a href="{{ route('admin.students') }}" class="text-sm font-medium text-brand-500 hover:text-brand-700">View all students</a>
                </div>

                <x-portal.data-table
                    compact
                    flush
                    search-placeholder="Search activity..."
                    table-class="min-w-[640px]"
                >
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Date &amp; Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentActivity as $row)
                            <tr>
                                <td class="font-medium text-gray-900">{{ $row['activity'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>
                                    @php
                                        $typeStyles = match ($row['type']) {
                                            'Student' => 'bg-brand-50 text-brand-700',
                                            'Package' => 'bg-blue-light-50 text-blue-light-700',
                                            'Container' => 'bg-warning-50 text-warning-700',
                                            'Delivery' => 'bg-success-50 text-success-700',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeStyles }}">{{ $row['type'] }}</span>
                                </td>
                                <td class="whitespace-nowrap text-gray-500">{{ $row['time']->format('M j, Y g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-sm text-gray-500">No activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-portal.data-table>
            </div>

            {{-- Move Status Overview --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="text-base font-semibold text-gray-900">Move Status Overview</h2>
                <p class="mt-0.5 text-sm text-gray-500">Distribution of all moves</p>
                <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:justify-center lg:flex-col">
                    @php
                        $segments = $moveOverview['segments'];
                        $total = $moveOverview['total'];
                        $radius = 54;
                        $circumference = 2 * M_PI * $radius;
                        $offset = 0;
                    @endphp
                    <div class="relative h-40 w-40 shrink-0">
                        <svg class="h-40 w-40 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                            <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="#f2f4f7" stroke-width="12" />
                            @if ($total > 0)
                                @foreach ($segments as $segment)
                                    @php
                                        $dash = ($segment['percent'] / 100) * $circumference;
                                        $gap = $circumference - $dash;
                                    @endphp
                                    @if ($segment['percent'] > 0)
                                        <circle
                                            cx="60"
                                            cy="60"
                                            r="{{ $radius }}"
                                            fill="none"
                                            stroke="{{ $segment['color'] }}"
                                            stroke-width="12"
                                            stroke-dasharray="{{ $dash }} {{ $gap }}"
                                            stroke-dashoffset="{{ -$offset }}"
                                            stroke-linecap="butt"
                                        />
                                        @php $offset += $dash; @endphp
                                    @endif
                                @endforeach
                            @endif
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-semibold text-gray-900">{{ number_format($total) }}</span>
                            <span class="text-xs text-gray-500">Moves</span>
                        </div>
                    </div>
                    <ul class="w-full space-y-2.5 text-sm">
                        @foreach ($segments as $segment)
                            <li class="flex items-center justify-between gap-4">
                                <span class="flex items-center gap-2 text-gray-700">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $segment['color'] }}"></span>
                                    {{ $segment['label'] }}
                                </span>
                                <span class="font-medium text-gray-900">{{ $segment['count'] }} <span class="text-gray-400">({{ $segment['percent'] }}%)</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Upcoming deliveries + action center --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-xs lg:col-span-2">
                <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Upcoming Deliveries</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Scheduled drop-offs for the next 7 days</p>
                    </div>
                </div>

                <x-portal.data-table
                    compact
                    flush
                    search-placeholder="Search deliveries..."
                    table-class="min-w-[680px]"
                >
                    <thead>
                        <tr>
                            <th>Container</th>
                            <th>Student</th>
                            <th>Ship by</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($upcomingDeliveries as $container)
                            @php $student = $container->studentProfile; @endphp
                            <tr>
                                <td class="font-medium text-gray-900">{{ $container->code }}</td>
                                <td>{{ $student?->fullName() ?: $student?->user?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $container->ship_by_date ? $container->ship_by_date->format('M j, Y') : 'To schedule' }}</td>
                                <td>{{ $container->location ?: '—' }}</td>
                                <td><span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $container->statusLabel() }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-sm text-gray-500">No deliveries scheduled in the next 7 days.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-portal.data-table>
            </div>

            {{-- Action center --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="text-base font-semibold text-gray-900">Action Center</h2>
                <p class="mt-0.5 text-sm text-gray-500">Items that may need attention</p>
                <ul class="mt-4 space-y-2">
                    @foreach ($actionItems as $item)
                        @php
                            $itemTone = $item['tone'] ?? 'info';
                            $badgeClasses = match ($itemTone) {
                                'warning' => 'bg-warning-50 text-warning-700',
                                'success' => 'bg-success-50 text-success-700',
                                'muted' => 'bg-gray-100 text-gray-500',
                                default => 'bg-blue-light-50 text-blue-light-700',
                            };
                        @endphp
                        <li>
                            <a href="{{ $item['href'] }}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2.5 transition hover:border-gray-200 hover:bg-gray-50">
                                <span class="text-sm text-gray-700">{{ $item['label'] }}</span>
                                <span class="inline-flex min-w-[2rem] justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClasses }}">{{ number_format($item['value']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
