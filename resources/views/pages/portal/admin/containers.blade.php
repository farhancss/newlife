@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Container Management</h1>
                <p class="mt-1 text-sm text-gray-600">Track container status, routes, and customer assignments.</p>
            </div>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Container</button>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
                            <th class="px-3 py-3">Container</th>
                            <th class="px-3 py-3">Size</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Location</th>
                            <th class="px-3 py-3">Customer</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700">
                        @foreach ([['CTN-101', '20ft', 'In transit', 'Atlanta', 'John Doe'], ['CTN-205', '40ft', 'Delivered', 'Norfolk', 'Jane Lee'], ['CTN-389', '20ft', 'Maintenance', 'Miami', 'N/A']] as [$id, $size, $status, $location, $customer])
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-3 font-medium text-gray-900">{{ $id }}</td>
                                <td class="px-3 py-3">{{ $size }}</td>
                                <td class="px-3 py-3"><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                                <td class="px-3 py-3">{{ $location }}</td>
                                <td class="px-3 py-3">{{ $customer }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
