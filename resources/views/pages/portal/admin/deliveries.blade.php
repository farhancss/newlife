@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Deliveries Management</h1>
                <p class="mt-1 text-sm text-gray-600">Schedule and monitor outbound deliveries.</p>
            </div>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Schedule Delivery</button>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4">
            <div class="mb-4 flex gap-3 text-sm">
                <span class="rounded-full bg-brand-50 px-3 py-1 font-semibold text-brand-700">Scheduled (8)</span>
                <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">Out for Delivery (7)</span>
                <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">Completed (15)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
                            <th class="px-3 py-3">Container</th>
                            <th class="px-3 py-3">Customer</th>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Time</th>
                            <th class="px-3 py-3">Address</th>
                            <th class="px-3 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700">
                        @foreach ([['CTN-104', 'Jack Peters', 'May 27, 2026', '9AM - 12PM', 'Atlanta', 'Assigned'], ['CTN-140', 'Julia Morris', 'May 27, 2026', '12PM - 3PM', 'Norfolk', 'Out for delivery'], ['CTN-142', 'Riley Brown', 'May 28, 2026', '3PM - 6PM', 'Richmond', 'Scheduled']] as [$container, $customer, $date, $time, $address, $status])
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-3 font-medium text-gray-900">{{ $container }}</td>
                                <td class="px-3 py-3">{{ $customer }}</td>
                                <td class="px-3 py-3">{{ $date }}</td>
                                <td class="px-3 py-3">{{ $time }}</td>
                                <td class="px-3 py-3">{{ $address }}</td>
                                <td class="px-3 py-3"><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
