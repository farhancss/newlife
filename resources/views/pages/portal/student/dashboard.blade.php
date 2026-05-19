@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Welcome, John!</h1>
                    <p class="mt-1 text-sm text-gray-600">Student dashboard overview</p>
                </div>
                <div class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600">
                    Move-In Progress
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-4">
                @foreach ([['Reservation', '1'], ['Start Packing', '2'], ['Container Dropoff', '3'], ['Move-In', '4']] as [$title, $step])
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">{{ $step }}</span>
                        <p class="text-sm font-medium text-gray-800">{{ $title }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                @foreach ([['Action Items', 'Complete profile and documents'], ['Upcoming Deadlines', 'Packout by May 25, 2026'], ['Latest Update', 'Container confirmed for dropoff']] as [$title, $desc])
                    <div class="rounded-xl border border-gray-200 p-4">
                        <h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>
                        <p class="mt-2 text-sm text-gray-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 xl:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Retail Packages</h2>
                    <a href="{{ route('student.retail-packages') }}" class="text-sm font-medium text-brand-600">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px]">
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
                            @foreach ([['Airfryer', 'Box Large', '123456789012345678', 'In transit', 'May 17, 2026'], ['Mattress', 'Foam Twin', '123456789012345679', 'Ready to move', 'May 19, 2026'], ['Printer', 'Office unit', '123456789012345680', 'Received', 'May 12, 2026']] as [$item, $desc, $track, $status, $eta])
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

            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                <div class="mt-4 grid gap-3">
                    @foreach ([['View Profile', route('student.profile')], ['Track Move', route('student.move-tracking')], ['Retail Packages', route('student.retail-packages')], ['Support', route('student.support')]] as [$label, $href])
                        <a href="{{ $href }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
