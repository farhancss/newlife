@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Internal operations overview</p>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                @foreach ([['Total Customers', '1,248'], ['Active Moves', '392'], ['Containers In Transit', '412'], ['Pending Deliveries', '156']] as [$label, $value])
                    <div class="rounded-xl bg-gray-50 p-3">
                        <p class="text-xs uppercase text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Recent Activity</h2>
                <div class="space-y-2 text-sm">
                    @foreach (['New customer registered - John Wilson', 'Retail package #PKG-119 delivered', 'Container CTN-888 marked in transit', 'Delivery route updated for May 20'] as $line)
                        <div class="rounded-lg bg-gray-50 px-3 py-2 text-gray-700">{{ $line }}</div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Move Status Overview</h2>
                <div class="mt-4 rounded-full border-8 border-brand-100 p-10 text-center">
                    <p class="text-2xl font-bold text-brand-700">63%</p>
                    <p class="text-xs text-gray-500">In Transit</p>
                </div>
            </div>
        </div>
    </div>
@endsection
