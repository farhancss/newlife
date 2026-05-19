@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Customer Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage customer records and move details.</p>
            </div>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Customer</button>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4">
            <div class="mb-4 flex flex-wrap gap-3">
                <input type="text" placeholder="Search by name, email, or ID..." class="h-10 w-full max-w-md rounded-lg border border-gray-300 px-3 text-sm" />
                <select class="h-10 rounded-lg border border-gray-300 px-3 text-sm"><option>All Status</option></select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px]">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
                            <th class="px-3 py-3">Name</th>
                            <th class="px-3 py-3">Move ID</th>
                            <th class="px-3 py-3">University</th>
                            <th class="px-3 py-3">ETA</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700">
                        @foreach ([['John Wilson', 'M-1001', 'ODU', 'May 27, 2026', 'In transit'], ['Jane Smith', 'M-1002', 'Auburn', 'Apr 20, 2026', 'Delivered'], ['Emily Davis', 'M-1004', 'VSU', 'Apr 3, 2026', 'Delayed']] as [$name, $id, $university, $eta, $status])
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-3 font-medium text-gray-900">{{ $name }}</td>
                                <td class="px-3 py-3">{{ $id }}</td>
                                <td class="px-3 py-3">{{ $university }}</td>
                                <td class="px-3 py-3">{{ $eta }}</td>
                                <td class="px-3 py-3"><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                                <td class="px-3 py-3"><button class="rounded-lg bg-gray-100 px-2 py-1 text-xs">Edit</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
