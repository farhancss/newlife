@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Container Management</h1>
                <p class="mt-1 text-sm text-gray-600">Track container status, routes, and customer assignments.</p>
            </div>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Container</button>
        </div>

        <x-portal.data-table table-class="min-w-[860px]">
            <thead>
                <tr>
                    <th>Container</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Customer</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['CTN-101', '20ft', 'In transit', 'Atlanta', 'John Doe'],
                    ['CTN-205', '40ft', 'Delivered', 'Norfolk', 'Jane Lee'],
                    ['CTN-389', '20ft', 'Maintenance', 'Miami', 'N/A'],
                    ['CTN-412', '20ft', 'In transit', 'Richmond', 'Jack Peters'],
                    ['CTN-518', '40ft', 'Scheduled', 'Charlotte', 'Julia Morris'],
                    ['CTN-622', '20ft', 'Delivered', 'Norfolk', 'Riley Brown'],
                    ['CTN-731', '40ft', 'In transit', 'Atlanta', 'Emily Davis'],
                    ['CTN-844', '20ft', 'Maintenance', 'Miami', 'N/A'],
                    ['CTN-905', '40ft', 'Scheduled', 'Richmond', 'Michael Brown'],
                    ['CTN-110', '20ft', 'In transit', 'Charlotte', 'Sarah Johnson'],
                ] as [$id, $size, $status, $location, $customer])
                    <tr>
                        <td class="font-medium text-gray-900">{{ $id }}</td>
                        <td>{{ $size }}</td>
                        <td><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                        <td>{{ $location }}</td>
                        <td>{{ $customer }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-portal.data-table>
    </div>
@endsection
