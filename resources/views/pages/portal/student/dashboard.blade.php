@extends('layouts.app')

@php
    $firstName = strtok(auth()->user()->name ?? 'John', ' ');
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-lg font-semibold text-gray-900">Welcome, {{ $firstName }}!</h1>
            @if ($package)
                <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-300">
                    {{ $package->name }}
                </span>
            @endif
        </div>

        {{-- Move-In Progress --}}
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-6 sm:px-8">
            <h2 class="text-base font-semibold text-gray-900">Dashboard</h2>

            <div class="mt-8">
                <x-dashboard.move-progress-stepper :steps="$dashboardSteps" />
            </div>
        </div>

        {{-- Quick links --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.quick-link-card
                :href="route('student.retail-packages')"
                title="Retail Packages"
                subtitle="View & Manage"
                icon="package"
                arrow-class="h-7 w-7"
            />
            <x-dashboard.quick-link-card
                :href="route('student.move-tracking')"
                title="Track Shipments"
                subtitle="View Status"
                icon="map"
                arrow-class="h-7 w-7"
            />
            <x-dashboard.quick-link-card
                :href="route('student.add-ons')"
                title="Add-Ons"
                subtitle="Explore Options"
                icon="addons"
                arrow-class="h-7 w-7"
            />
            <x-dashboard.quick-link-card
                :href="route('student.support')"
                title="Help & Support"
                subtitle="Get Help"
                icon="phone"
                arrow-class="h-7 w-7"
            />
        </div>

        {{-- Three summary cards --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <x-dashboard.action-items-card
                :profile="$profile"
                :primary-container="$primaryContainer"
                :deadlines="$deadlines"
            />
            <x-dashboard.upcoming-deadlines-card :deadlines="$deadlines" />
            <x-dashboard.latest-updates-card :recent-updates="$recentUpdates" />
        </div>
    </div>
@endsection
