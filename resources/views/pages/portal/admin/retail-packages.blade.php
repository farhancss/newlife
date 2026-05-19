@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Retail Package Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage package shipments and customer assignments.</p>
            </div>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Package</button>
        </div>

        <x-portal.data-table table-class="min-w-[860px]">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Tracking #</th>
                    <th>Status</th>
                    <th>ETA</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['John Doe', 'Airfryer', '123456789012345678', 'In transit', 'May 17, 2026'],
                    ['Jane Lee', 'Mattress', '123456789012345679', 'Processing', 'May 19, 2026'],
                    ['Jade Kim', 'Desk Chair', '123456789012345680', 'Received', 'May 12, 2026'],
                    ['Jack Peters', 'Mini Fridge', '123456789012345681', 'In transit', 'May 21, 2026'],
                    ['Julia Morris', 'TV Stand', '123456789012345682', 'Delivered', 'May 10, 2026'],
                    ['Riley Brown', 'Microwave', '123456789012345683', 'Processing', 'May 24, 2026'],
                    ['Emily Davis', 'Lamp Set', '123456789012345684', 'Received', 'May 15, 2026'],
                    ['Michael Brown', 'Bookshelf', '123456789012345685', 'In transit', 'May 28, 2026'],
                    ['Sarah Johnson', 'Coffee Maker', '123456789012345686', 'Delayed', 'Jun 1, 2026'],
                    ['David Lee', 'Desk', '123456789012345687', 'Delivered', 'May 8, 2026'],
                ] as [$customer, $item, $track, $status, $eta])
                    <tr>
                        <td class="font-medium text-gray-900">{{ $customer }}</td>
                        <td>{{ $item }}</td>
                        <td class="font-mono text-xs">{{ $track }}</td>
                        <td><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                        <td>{{ $eta }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-portal.data-table>
    </div>
@endsection
