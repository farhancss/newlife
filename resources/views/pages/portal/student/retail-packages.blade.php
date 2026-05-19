@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Retail Packages</h1>
                <p class="mt-1 text-sm text-gray-600">Track package reservations and delivery status.</p>
            </div>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Package</button>
        </div>

        <x-portal.data-table table-class="min-w-[780px]">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Tracking #</th>
                    <th>Status</th>
                    <th>ETA</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['Airfryer', 'Box Large', '123456789012345678', 'In transit', 'May 17, 2026'],
                    ['Mattress', 'Foam Twin', '123456789012345679', 'Ready to move', 'May 19, 2026'],
                    ['Printer', 'Office unit', '123456789012345680', 'Received', 'May 12, 2026'],
                    ['Target Set', 'Bedding kit', '123456789012345681', 'Delivered', 'May 10, 2026'],
                    ['Desk Lamp', 'LED adjustable', '123456789012345682', 'In transit', 'May 22, 2026'],
                    ['Mini Fridge', 'Dorm size', '123456789012345683', 'Processing', 'May 25, 2026'],
                    ['Bookshelf', '3-tier wood', '123456789012345684', 'Received', 'May 14, 2026'],
                    ['Coffee Maker', 'Keurig compatible', '123456789012345685', 'Delivered', 'May 8, 2026'],
                    ['Desk Chair', 'Ergonomic', '123456789012345686', 'In transit', 'May 27, 2026'],
                    ['TV Stand', '42 inch max', '123456789012345687', 'Ready to move', 'May 30, 2026'],
                ] as [$item, $desc, $track, $status, $eta])
                    <tr>
                        <td class="font-medium text-gray-900">{{ $item }}</td>
                        <td>{{ $desc }}</td>
                        <td class="font-mono text-xs">{{ $track }}</td>
                        <td><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                        <td>{{ $eta }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-portal.data-table>
    </div>
@endsection
