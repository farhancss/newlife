@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-600">Recent updates from your move and package activity.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="space-y-3">
                @foreach (['Container CTN-21579 is now in transit.', 'Retail package Airfryer marked as in transit.', 'Support ticket updated by operations team.'] as $note)
                    <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">{{ $note }}</div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
