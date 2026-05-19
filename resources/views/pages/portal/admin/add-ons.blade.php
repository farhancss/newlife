@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Add-Ons Management</h1>
                <p class="mt-1 text-sm text-gray-600">Manage optional add-ons available to students.</p>
            </div>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Add Add-On</button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['name' => 'Extra Container', 'price' => '$49.00', 'active' => 128],
                ['name' => 'Priority Delivery', 'price' => '$29.00', 'active' => 86],
                ['name' => 'Insurance Plus', 'price' => '$19.00', 'active' => 204],
            ] as $addon)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <h2 class="font-semibold text-gray-900">{{ $addon['name'] }}</h2>
                    <p class="mt-1 text-lg font-semibold text-brand-700">{{ $addon['price'] }}</p>
                    <p class="mt-2 text-sm text-gray-500">{{ $addon['active'] }} active subscriptions</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
