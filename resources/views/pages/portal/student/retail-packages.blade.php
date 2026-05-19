@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Retail Packages</h1>
                <p class="mt-1 text-sm text-gray-600">Track package reservations and delivery status.</p>
            </div>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Package</button>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px]">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
                            <th class="px-3 py-3">Item</th>
                            <th class="px-3 py-3">Description</th>
                            <th class="px-3 py-3">Tracking #</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">ETA</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700">
                        @foreach ([['Airfryer', 'Box Large', '123456789012345678', 'In transit', 'May 17, 2026'], ['Mattress', 'Foam Twin', '123456789012345679', 'Ready to move', 'May 19, 2026'], ['Printer', 'Office unit', '123456789012345680', 'Received', 'May 12, 2026'], ['Target Set', 'Bedding kit', '123456789012345681', 'Delivered', 'May 10, 2026']] as [$item, $desc, $track, $status, $eta])
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-3 font-medium text-gray-900">{{ $item }}</td>
                                <td class="px-3 py-3">{{ $desc }}</td>
                                <td class="px-3 py-3 font-mono text-xs">{{ $track }}</td>
                                <td class="px-3 py-3"><span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">{{ $status }}</span></td>
                                <td class="px-3 py-3">{{ $eta }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
