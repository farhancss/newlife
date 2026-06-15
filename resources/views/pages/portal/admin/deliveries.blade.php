@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Deliveries Management</h1>
                <p class="mt-1 text-sm text-gray-600">Schedule and monitor outbound deliveries.</p>
            </div>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Schedule Delivery</button>
        </div>

        <div class="flex flex-wrap gap-3 text-sm">
            <span class="rounded-full bg-brand-50 px-3 py-1 font-semibold text-brand-700">Scheduled (8)</span>
            <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">Out for Delivery (7)</span>
            <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">Completed (15)</span>
        </div>

        <x-portal.data-table table-class="min-w-[860px]">
            <thead>
                <tr>
                    <th>Container</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['CTN-104', 'Jack Peters', 'May 27, 2026', '9AM - 12PM', 'Atlanta', 'Assigned'],
                    ['CTN-140', 'Julia Morris', 'May 27, 2026', '12PM - 3PM', 'Norfolk', 'Out for delivery'],
                    ['CTN-142', 'Riley Brown', 'May 28, 2026', '3PM - 6PM', 'Richmond', 'Scheduled'],
                    ['CTN-155', 'Emily Davis', 'May 28, 2026', '9AM - 12PM', 'Charlotte', 'Assigned'],
                    ['CTN-160', 'Michael Brown', 'May 29, 2026', '12PM - 3PM', 'Atlanta', 'Scheduled'],
                    ['CTN-172', 'Sarah Johnson', 'May 29, 2026', '3PM - 6PM', 'Norfolk', 'Out for delivery'],
                    ['CTN-180', 'David Lee', 'May 30, 2026', '9AM - 12PM', 'Richmond', 'Completed'],
                    ['CTN-188', 'Olivia Martinez', 'May 30, 2026', '12PM - 3PM', 'Miami', 'Scheduled'],
                    ['CTN-195', 'James Taylor', 'May 31, 2026', '3PM - 6PM', 'Atlanta', 'Assigned'],
                    ['CTN-201', 'Sophia Anderson', 'May 31, 2026', '9AM - 12PM', 'Charlotte', 'Out for delivery'],
                    ['CTN-210', 'Liam Thomas', 'Jun 1, 2026', '12PM - 3PM', 'Norfolk', 'Scheduled'],
                    ['CTN-218', 'Ava Jackson', 'Jun 1, 2026', '3PM - 6PM', 'Richmond', 'Completed'],
                ] as [$container, $student, $date, $time, $address, $status])
                    <tr>
                        <td class="font-medium text-gray-900">{{ $container }}</td>
                        <td>{{ $student }}</td>
                        <td>{{ $date }}</td>
                        <td>{{ $time }}</td>
                        <td>{{ $address }}</td>
                        <td><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </x-portal.data-table>
    </div>
@endsection
