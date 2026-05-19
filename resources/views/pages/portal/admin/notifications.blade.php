@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
            <h1 class="text-xl font-semibold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-600">System alerts and operational updates.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs">
            <ul class="divide-y divide-gray-100">
                @foreach ([
                    ['title' => '12 containers delayed at hub', 'time' => 'May 19, 2:30 PM', 'type' => 'alert'],
                    ['title' => 'New student batch imported', 'time' => 'May 19, 11:00 AM', 'type' => 'info'],
                    ['title' => 'Delivery route updated for Zone B', 'time' => 'May 18, 4:15 PM', 'type' => 'info'],
                    ['title' => 'Retail package sync completed', 'time' => 'May 18, 9:00 AM', 'type' => 'success'],
                ] as $note)
                    <li class="flex items-start justify-between gap-4 py-4 first:pt-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $note['title'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $note['time'] }}</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium capitalize',
                            'bg-error-50 text-error-700' => $note['type'] === 'alert',
                            'bg-brand-50 text-brand-700' => $note['type'] === 'info',
                            'bg-success-50 text-success-700' => $note['type'] === 'success',
                        ])>{{ $note['type'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
