@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Reports & Exports</h1>
            <p class="mt-1 text-sm text-gray-600">Generate snapshots and export operational reports.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach (['Student Report', 'Retail Package Report', 'Container Export', 'Delivery Report', 'Communications Log', 'Audit Export'] as $report)
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h2 class="text-base font-semibold text-gray-900">{{ $report }}</h2>
                    <p class="mt-2 text-sm text-gray-600">Generate and export as CSV/XLSX.</p>
                    <button class="mt-4 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white">Run {{ str_contains($report, 'Export') ? 'Export' : 'Report' }}</button>
                </div>
            @endforeach
        </div>
    </div>
@endsection
