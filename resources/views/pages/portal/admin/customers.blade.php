@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Student Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage student records and move details.</p>
            </div>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Student</button>
        </div>

        <x-portal.data-table table-class="min-w-[850px]">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Move ID</th>
                    <th>University</th>
                    <th>ETA</th>
                    <th>Status</th>
                    <th data-sortable="false">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['John Wilson', 'M-1001', 'ODU', 'May 27, 2026', 'In transit'],
                    ['Jane Smith', 'M-1002', 'Auburn', 'Apr 20, 2026', 'Delivered'],
                    ['Emily Davis', 'M-1004', 'VSU', 'Apr 3, 2026', 'Delayed'],
                    ['Michael Brown', 'M-1005', 'VCU', 'May 30, 2026', 'Scheduled'],
                    ['Sarah Johnson', 'M-1006', 'GMU', 'Jun 2, 2026', 'In transit'],
                    ['David Lee', 'M-1007', 'UVA', 'May 18, 2026', 'Delivered'],
                    ['Olivia Martinez', 'M-1008', 'JMU', 'May 22, 2026', 'Processing'],
                    ['James Taylor', 'M-1009', 'VT', 'May 25, 2026', 'In transit'],
                    ['Sophia Anderson', 'M-1010', 'Radford', 'Jun 5, 2026', 'Scheduled'],
                    ['Liam Thomas', 'M-1011', 'Liberty', 'May 14, 2026', 'Delayed'],
                    ['Ava Jackson', 'M-1012', 'ODU', 'May 29, 2026', 'In transit'],
                    ['Noah White', 'M-1013', 'Auburn', 'Jun 8, 2026', 'Processing'],
                ] as [$name, $id, $university, $eta, $status])
                    <tr>
                        <td class="font-medium text-gray-900">{{ $name }}</td>
                        <td>{{ $id }}</td>
                        <td>{{ $university }}</td>
                        <td>{{ $eta }}</td>
                        <td><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                        <td><button type="button" class="rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Edit</button></td>
                    </tr>
                @endforeach
            </tbody>
        </x-portal.data-table>
    </div>
@endsection
