@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Dashboard Overview</h1>
            <p class="text-sm text-gray-500">Last updated {{ now()->format('M j, Y g:i A') }}</p>
        </div>

        {{-- Summary cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total Students', 'value' => '1,248', 'trend' => '+12 this week'],
                ['label' => 'Active Moves', 'value' => '982', 'trend' => '+8% vs last month'],
                ['label' => 'Containers In Transit', 'value' => '412', 'trend' => '34 arriving today'],
                ['label' => 'Pending Deliveries', 'value' => '156', 'trend' => '23 due this week'],
            ] as $stat)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-xs text-gray-500">{{ $stat['trend'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Recent Activity --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-xs lg:col-span-2">
                <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Recent Activity</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Latest events across students, packages, and deliveries</p>
                    </div>
                    <a href="{{ route('admin.customers') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all students</a>
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
                        @foreach ([
                            ['activity' => 'New student registered', 'name' => 'John Doe', 'type' => 'Student', 'time' => 'May 10, 2026 10:30 AM'],
                            ['activity' => 'Retail package received', 'name' => 'Jane Smith', 'type' => 'Package', 'time' => 'May 10, 2026 9:15 AM'],
                            ['activity' => 'Container marked in transit', 'name' => 'Mike Johnson', 'type' => 'Container', 'time' => 'May 9, 2026 4:45 PM'],
                            ['activity' => 'Delivery scheduled', 'name' => 'Sarah Lee', 'type' => 'Delivery', 'time' => 'May 9, 2026 2:20 PM'],
                            ['activity' => 'Add-on purchased', 'name' => 'Alex Brown', 'type' => 'Add-on', 'time' => 'May 9, 2026 11:00 AM'],
                            ['activity' => 'Profile completed', 'name' => 'Emily Davis', 'type' => 'Student', 'time' => 'May 8, 2026 3:40 PM'],
                            ['activity' => 'Package out for delivery', 'name' => 'Jack Peters', 'type' => 'Package', 'time' => 'May 8, 2026 1:15 PM'],
                            ['activity' => 'Move-in window selected', 'name' => 'Julia Morris', 'type' => 'Student', 'time' => 'May 8, 2026 10:05 AM'],
                            ['activity' => 'Container delivered to hub', 'name' => 'Riley Brown', 'type' => 'Container', 'time' => 'May 7, 2026 5:50 PM'],
                            ['activity' => 'Support ticket resolved', 'name' => 'David Lee', 'type' => 'Support', 'time' => 'May 7, 2026 2:30 PM'],
                            ['activity' => 'Retail package delayed', 'name' => 'Olivia Martinez', 'type' => 'Package', 'time' => 'May 7, 2026 9:20 AM'],
                            ['activity' => 'Delivery completed', 'name' => 'James Taylor', 'type' => 'Delivery', 'time' => 'May 6, 2026 6:10 PM'],
                        ] as $row)
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
                                            'Add-on' => 'bg-gray-100 text-gray-700',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeStyles }}">{{ $row['type'] }}</span>
                                </td>
                                <td class="whitespace-nowrap text-gray-500">{{ $row['time'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-portal.data-table>
            </div>

            {{-- Move Status Overview --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="text-base font-semibold text-gray-900">Move Status Overview</h2>
                <p class="mt-0.5 text-sm text-gray-500">Distribution of active moves</p>
                <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:justify-center lg:flex-col">
                    @php
                        $segments = [
                            ['label' => 'In Progress', 'percent' => 62, 'color' => '#215f95'],
                            ['label' => 'In Transit', 'percent' => 18, 'color' => '#4c8ec2'],
                            ['label' => 'At Hub', 'percent' => 10, 'color' => '#78afd9'],
                            ['label' => 'Out for Delivery', 'percent' => 7, 'color' => '#a9cceb'],
                            ['label' => 'Delivered', 'percent' => 3, 'color' => '#cfe4f7'],
                        ];
                        $radius = 54;
                        $circumference = 2 * M_PI * $radius;
                        $offset = 0;
                    @endphp
                    <div class="relative h-40 w-40 shrink-0">
                        <svg class="h-40 w-40 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                            <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="#f2f4f7" stroke-width="12" />
                            @foreach ($segments as $segment)
                                @php
                                    $dash = ($segment['percent'] / 100) * $circumference;
                                    $gap = $circumference - $dash;
                                @endphp
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
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-semibold text-gray-900">982</span>
                            <span class="text-xs text-gray-500">Active</span>
                        </div>
                    </div>
                    <ul class="w-full space-y-2.5 text-sm">
                        @foreach ($segments as $segment)
                            <li class="flex items-center justify-between gap-4">
                                <span class="flex items-center gap-2 text-gray-700">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $segment['color'] }}"></span>
                                    {{ $segment['label'] }}
                                </span>
                                <span class="font-medium text-gray-900">{{ $segment['percent'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Upcoming deliveries --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Upcoming Deliveries</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Scheduled drop-offs for the next 7 days</p>
                </div>
                <a href="{{ route('admin.deliveries') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Manage deliveries</a>
            </div>

            <x-portal.data-table
                compact
                flush
                search-placeholder="Search deliveries..."
                table-class="min-w-[720px]"
            >
                <thead>
                    <tr>
                        <th>Container</th>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Window</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['container' => 'CTN-104', 'student' => 'Jack Peters', 'date' => 'May 20, 2026', 'window' => '9AM – 12PM', 'location' => 'Atlanta, GA', 'status' => 'Scheduled'],
                        ['container' => 'CTN-140', 'student' => 'Julia Morris', 'date' => 'May 20, 2026', 'window' => '12PM – 3PM', 'location' => 'Norfolk, VA', 'status' => 'Out for delivery'],
                        ['container' => 'CTN-142', 'student' => 'Riley Brown', 'date' => 'May 21, 2026', 'window' => '3PM – 6PM', 'location' => 'Richmond, VA', 'status' => 'Scheduled'],
                        ['container' => 'CTN-155', 'student' => 'Emily Davis', 'date' => 'May 21, 2026', 'window' => '9AM – 12PM', 'location' => 'Charlotte, NC', 'status' => 'Assigned'],
                        ['container' => 'CTN-160', 'student' => 'Michael Brown', 'date' => 'May 22, 2026', 'window' => '12PM – 3PM', 'location' => 'Atlanta, GA', 'status' => 'Scheduled'],
                        ['container' => 'CTN-172', 'student' => 'Sarah Johnson', 'date' => 'May 22, 2026', 'window' => '3PM – 6PM', 'location' => 'Norfolk, VA', 'status' => 'Out for delivery'],
                        ['container' => 'CTN-180', 'student' => 'David Lee', 'date' => 'May 23, 2026', 'window' => '9AM – 12PM', 'location' => 'Richmond, VA', 'status' => 'Scheduled'],
                        ['container' => 'CTN-188', 'student' => 'Olivia Martinez', 'date' => 'May 23, 2026', 'window' => '12PM – 3PM', 'location' => 'Miami, FL', 'status' => 'Assigned'],
                    ] as $row)
                        <tr>
                            <td class="font-medium text-gray-900">{{ $row['container'] }}</td>
                            <td>{{ $row['student'] }}</td>
                            <td class="whitespace-nowrap">{{ $row['date'] }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $row['window'] }}</td>
                            <td>{{ $row['location'] }}</td>
                            <td><span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $row['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-portal.data-table>
        </div>
    </div>
@endsection
