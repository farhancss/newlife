@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">My Move</h1>
            <p class="mt-1 text-sm text-gray-600">Shipment and move tracking</p>

            <div class="mt-4 grid gap-2 md:grid-cols-7">
                @foreach (['Reservation', 'Profile', 'Containers', 'Packout', 'In transit', 'Delivery', 'Move-in'] as $index => $step)
                    <div class="rounded-lg px-2 py-2 text-center text-xs font-semibold {{ $index < 4 ? 'bg-brand-50 text-brand-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $step }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Current Status</h2>
                <p class="mt-2 text-sm text-gray-600">Containers shipped to move in on schedule.</p>
                <div class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-700">
                    <p><span class="font-semibold">Updated:</span> May 19, 2026</p>
                    <p class="mt-1"><span class="font-semibold">Location:</span> Norfolk Terminal</p>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Shipment Details</h2>
                <ul class="mt-3 space-y-2 text-sm text-gray-700">
                    <li class="flex justify-between"><span>Container ID</span><span class="font-semibold">CTN-21579</span></li>
                    <li class="flex justify-between"><span>Carrier</span><span class="font-semibold">FedEx</span></li>
                    <li class="flex justify-between"><span>ETA</span><span class="font-semibold">May 27, 2026</span></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
