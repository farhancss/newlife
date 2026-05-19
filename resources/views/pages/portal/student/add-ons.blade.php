@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Add-Ons</h1>
            <p class="mt-1 text-sm text-gray-600">Explore optional services and upgrades for your move.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (['Extra Container', 'Priority Delivery', 'Insurance Plus'] as $addon)
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h2 class="font-semibold text-gray-900">{{ $addon }}</h2>
                    <p class="mt-2 text-sm text-gray-600">Available as an optional upgrade for your package.</p>
                    <button type="button" class="mt-4 rounded-lg border border-brand-600 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50">
                        Learn More
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endsection
