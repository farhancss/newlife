@extends('layouts.app')

@php
    use App\Enums\RetailPackageStatus;
    use App\Enums\UserStatus;

    $user = $profile->user;
    $name = $profile->fullName() ?: ($user?->name ?? 'Student');
    $initials = collect(explode(' ', $name))->filter()->take(2)
        ->map(fn ($p) => strtoupper(substr($p, 0, 1)))->join('') ?: 'NL';

    $accountBadge = match ($user?->status) {
        UserStatus::ACTIVE => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        UserStatus::INVITED => 'bg-amber-50 text-amber-700 ring-amber-200',
        UserStatus::SUSPENDED => 'bg-error-50 text-error-700 ring-error-200',
        default => 'bg-gray-100 text-gray-600 ring-gray-200',
    };

    $retailBadge = function (string $status): string {
        return match ($status) {
            RetailPackageStatus::LOGGED => 'bg-gray-100 text-gray-700',
            RetailPackageStatus::IN_TRANSIT => 'bg-brand-50 text-brand-700',
            RetailPackageStatus::RECEIVED_AT_HUB => 'bg-indigo-50 text-indigo-700',
            RetailPackageStatus::STAGED_FOR_DELIVERY => 'bg-amber-50 text-amber-700',
            RetailPackageStatus::DELIVERED_TO_DORM => 'bg-emerald-50 text-emerald-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    // Reusable definition-list row (escaped, with em-dash fallback).
    $row = function (string $label, ?string $value): string {
        $display = ($value !== null && $value !== '')
            ? e($value)
            : '<span class="text-gray-400">—</span>';

        return '<div class="flex items-start justify-between gap-3 py-2">'
            . '<dt class="text-sm text-gray-500">' . e($label) . '</dt>'
            . '<dd class="text-right text-sm font-medium text-gray-900">' . $display . '</dd>'
            . '</div>';
    };

    $fmtDate = fn ($d) => $d?->format('M j, Y');
    $fmtDateTime = fn ($d) => $d?->format('M j, Y · g:i A');

    $shipping = $profile->shippingAddress;
    $housing = $profile->housingInfo;
    $parent = $profile->parentGuardian;
    $package = $profile->package;
@endphp

@section('content')
    <div class="space-y-6">
        {{-- Breadcrumb / back --}}
        <a href="{{ route('admin.students') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-brand-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to students
        </a>

        {{-- Identity header --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="h-20 bg-gradient-to-r from-brand-600 to-brand-500"></div>
            <div class="px-5 pb-5 sm:px-6">
                <div class="-mt-12">
                    <x-ui.avatar :src="$user?->avatarUrl()" :initials="$initials"
                        class="flex h-20 w-20 items-center justify-center rounded-2xl border-4 border-white bg-brand-100 text-2xl font-bold text-brand-500 shadow-sm" />
                </div>

                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $name }}</h1>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                            <span class="font-mono font-semibold text-brand-700">{{ $profile->new_life_id }}</span>
                            @if ($user)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold capitalize ring-1 {{ $accountBadge }}">
                                    {{ $user->status }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.containers', ['q' => $profile->new_life_id]) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Containers
                        </a>
                        <a href="{{ route('admin.retail-packages', ['q' => $profile->new_life_id]) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Retail packages
                        </a>
                        <a href="{{ route('admin.add-ons', ['q' => $profile->new_life_id]) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Add-ons
                        </a>
                        <a href="{{ route('admin.deadlines', ['q' => $profile->new_life_id]) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Deadlines
                        </a>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-3 border-t border-gray-100 pt-4 sm:grid-cols-3">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        <span class="truncate">{{ $user?->email ?: '—' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        <span>{{ $profile->phone ?: '—' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                        <span>Stage: <span class="font-semibold text-gray-900">{{ $currentStage }}</span></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Package</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $package?->shortLabel() ?: '—' }}</p>
                <p class="text-xs text-gray-500">{{ $package ? $package->formattedPrice() : 'No package' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Move containers</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $profile->move_container_quantity }}</p>
                <p class="text-xs text-gray-500">{{ $containers->count() }} tracked shipment{{ $containers->count() === 1 ? '' : 's' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Retail packages</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $activeRetailCount }} active</p>
                <p class="text-xs text-gray-500">{{ $profile->retailPackages->count() }} total logged</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Profile complete</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $completion['percent'] }}%</p>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $completion['percent'] }}%"></div>
                </div>
            </div>
        </div>

        {{-- Containers --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:p-6">
            <h2 class="mb-4 text-base font-semibold text-gray-900">Containers</h2>
            @forelse ($containers as $container)
                @php $timeline = $containerTimelines[$container->id]; @endphp
                <div class="mb-5 rounded-2xl border border-gray-200 p-5 last:mb-0">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Move shipment</p>
                            <h3 class="text-lg font-bold text-gray-900">{{ $container->code }}</h3>
                        </div>
                        <span class="inline-flex h-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-500">{{ $container->statusLabel() }}</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
                        <div>
                            <dt class="text-xs text-gray-500">Location</dt>
                            <dd class="font-medium text-gray-900">{{ $container->location ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Ship by</dt>
                            <dd class="font-medium text-gray-900">{{ $container->ship_by_date?->format('M j, Y') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Outbound tracking</dt>
                            <dd class="font-medium">
                                @if ($container->outbound_tracking)
                                    <a href="{{ $fedExLinkService->trackingUrl($container->outbound_tracking) }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline">{{ $container->outbound_tracking }}</a>
                                @else
                                    <span class="text-gray-400">Pending</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Return tracking</dt>
                            <dd class="font-medium">
                                @if ($container->return_tracking)
                                    <a href="{{ $fedExLinkService->trackingUrl($container->return_tracking) }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline">{{ $container->return_tracking }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($container->internal_notes)
                        <p class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600"><span class="font-semibold text-gray-700">Internal notes:</span> {{ $container->internal_notes }}</p>
                    @endif

                    <div class="mt-5 rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                        <x-move.status-timeline :steps="$timeline['steps']" :active-index="$timeline['activeIndex']" />
                    </div>

                    @if ($container->photos->isNotEmpty())
                        <div class="mt-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Packing photos ({{ $container->photos->count() }})</p>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                @foreach ($container->photos as $photo)
                                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer" class="group relative block aspect-square overflow-hidden rounded-lg border border-gray-200">
                                        <img src="{{ $photo->url() }}" alt="{{ $photo->original_name ?: 'Container photo' }}" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($container->statusHistories->isNotEmpty())
                        <div class="mt-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Status history</p>
                            <ol class="space-y-2">
                                @foreach ($container->statusHistories->take(8) as $history)
                                    <li class="flex items-start gap-3 text-sm">
                                        <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                                        <div>
                                            <p class="text-gray-900">
                                                <span class="font-medium">{{ \App\Enums\ContainerStatus::label($history->to_status) }}</span>
                                                @if ($history->note)
                                                    <span class="text-gray-500">— {{ $history->note }}</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ $history->created_at?->format('M j, Y · g:i A') }}
                                                @if ($history->changedBy)
                                                    · by {{ $history->changedBy->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-8 text-center text-sm text-gray-400">No containers assigned yet.</p>
            @endforelse
        </div>

        {{-- Deadlines --}}
        @php
            $allDeadlines = $deadlines['overdue']->concat($deadlines['upcoming'])->concat($deadlines['completed']);
        @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Deadlines</h2>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-800">
                        {{ $deadlines['overdue']->count() }} overdue
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 font-semibold text-brand-700">
                        {{ $deadlines['upcoming']->count() }} upcoming
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700">
                        {{ $deadlines['completed']->count() }} completed
                    </span>
                </div>
            </div>

            @if ($allDeadlines->isNotEmpty())
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($allDeadlines as $deadline)
                        <x-deadline.card :deadline="$deadline" />
                    @endforeach
                </div>
            @else
                <p class="py-8 text-center text-sm text-gray-400">No deadlines on record.</p>
            @endif
        </div>

        {{-- Detail grid --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="mb-2 text-base font-semibold text-gray-900">Student information</h2>
                <dl class="divide-y divide-gray-100">
                    {!! $row('First name', $profile->first_name) !!}
                    {!! $row('Last name', $profile->last_name) !!}
                    {!! $row('School', $profile->school) !!}
                    {!! $row('Incoming year', $profile->incoming_year) !!}
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="mb-2 text-base font-semibold text-gray-900">Account</h2>
                <dl class="divide-y divide-gray-100">
                    {!! $row('Role', $user ? ucfirst($user->role) : null) !!}
                    {!! $row('Status', $user ? ucfirst($user->status) : null) !!}
                    {!! $row('Must reset password', $user ? ($user->must_reset_password ? 'Yes' : 'No') : null) !!}
                    {!! $row('Password changed', $fmtDateTime($user?->password_changed_at)) !!}
                    {!! $row('Squarespace contact', $profile->squarespace_contact_id) !!}
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="mb-2 text-base font-semibold text-gray-900">Parent / Guardian</h2>
                @if ($parent)
                    <dl class="divide-y divide-gray-100">
                        {!! $row('Name', $parent->name) !!}
                        {!! $row('Email', $parent->email) !!}
                        {!! $row('Phone', $parent->phone) !!}
                        {!! $row('Relationship', $parent->relationship) !!}
                    </dl>
                @else
                    <p class="py-4 text-sm text-gray-400">No parent/guardian on file.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="mb-2 text-base font-semibold text-gray-900">Home address</h2>
                @if ($shipping)
                    <dl class="divide-y divide-gray-100">
                        {!! $row('Street', trim(($shipping->line1 ?? '') . ' ' . ($shipping->line2 ?? ''))) !!}
                        {!! $row('City', $shipping->city) !!}
                        {!! $row('State / Region', $shipping->region) !!}
                        {!! $row('Postal code', $shipping->postal_code) !!}
                        {!! $row('Country', $shipping->country_code) !!}
                        {!! $row('Confirmed', $fmtDateTime($shipping->confirmed_at)) !!}
                    </dl>
                    @if ($shipping->shipping_notes)
                        <p class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">{{ $shipping->shipping_notes }}</p>
                    @endif
                @else
                    <p class="py-4 text-sm text-gray-400">No home address on file.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="mb-2 text-base font-semibold text-gray-900">University / Dorm</h2>
                @if ($housing)
                    <dl class="divide-y divide-gray-100">
                        {!! $row('University', $housing->university) !!}
                        {!! $row('Residence hall', $housing->residence_hall) !!}
                        {!! $row('Building', $housing->building) !!}
                        {!! $row('Room', $housing->room) !!}
                        {!! $row('Move-in date', $fmtDate($housing->move_in_date)) !!}
                        {!! $row('Move-in window', $housing->move_in_window) !!}
                    </dl>
                    @if ($housing->delivery_notes)
                        <p class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">{{ $housing->delivery_notes }}</p>
                    @endif
                @else
                    <p class="py-4 text-sm text-gray-400">No university/dorm info on file.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h2 class="mb-2 text-base font-semibold text-gray-900">Onboarding &amp; milestones</h2>
                <dl class="divide-y divide-gray-100">
                    {!! $row('Onboarding step', (string) $profile->onboarding_step) !!}
                    {!! $row('Onboarding completed', $fmtDateTime($profile->onboarding_completed_at)) !!}
                    {!! $row('Address confirmed', $fmtDateTime($profile->move_address_confirmed_at)) !!}
                    {!! $row('Shipment triggered', $fmtDateTime($profile->move_shipment_triggered_at)) !!}
                    {!! $row('Retail terms accepted', $fmtDateTime($profile->retail_packages_acknowledged_at)) !!}
                </dl>
                @php $incomplete = collect($completion['sections'])->where('complete', false); @endphp
                @if ($incomplete->isNotEmpty())
                    <div class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">
                        <p class="font-semibold">Outstanding profile items</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4">
                            @foreach ($incomplete as $section)
                                <li>{{ $section['label'] }}: {{ implode(', ', $section['missing']) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        {{-- Retail packages --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:p-6">
            <h2 class="mb-4 text-base font-semibold text-gray-900">Retail packages</h2>
            @if ($profile->retailPackages->isNotEmpty())
                <x-portal.data-table table-class="min-w-[720px]">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Retailer</th>
                            <th>Tracking #</th>
                            <th>Status</th>
                            <th>ETA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($profile->retailPackages as $pkg)
                            <tr class="hover:bg-gray-50/80">
                                <td class="font-medium text-gray-900">{{ $pkg->description }}</td>
                                <td>{{ $pkg->retailer }}</td>
                                <td class="font-mono text-xs">
                                    @if ($pkg->tracking_url)
                                        <a href="{{ $pkg->tracking_url }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline">{{ $pkg->tracking_number }}</a>
                                    @else
                                        {{ $pkg->tracking_number }}
                                    @endif
                                </td>
                                <td><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $retailBadge($pkg->status) }}">{{ $pkg->statusLabel() }}</span></td>
                                <td class="text-xs text-gray-700">{{ $pkg->estimated_arrival ? $pkg->estimated_arrival->format('M j, Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-portal.data-table>
            @else
                <p class="py-8 text-center text-sm text-gray-400">No retail packages logged.</p>
            @endif
        </div>

        {{-- Add-ons --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Add-ons</h2>
                <a href="{{ route('admin.add-ons', ['q' => $profile->new_life_id]) }}" class="text-sm font-medium text-brand-600 hover:underline">View all</a>
            </div>
            @if ($addOns->isNotEmpty())
                @php
                    $addOnBadge = fn (string $status) => match ($status) {
                        \App\Enums\AddOnStatus::ACTIVE => 'bg-emerald-50 text-emerald-700',
                        \App\Enums\AddOnStatus::CANCELLED => 'bg-gray-100 text-gray-600',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <x-portal.data-table table-class="min-w-[640px]">
                    <thead>
                        <tr>
                            <th>Add-on</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Purchased</th>
                            <th>Container</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($addOns as $addOn)
                            <tr class="hover:bg-gray-50/80">
                                <td class="font-medium text-gray-900">{{ $addOn->name }}</td>
                                <td class="text-sm text-gray-700">{{ $addOn->formattedPrice() }}</td>
                                <td><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $addOnBadge($addOn->status) }}">{{ $addOn->statusLabel() }}</span></td>
                                <td class="text-xs text-gray-700">{{ $addOn->requested_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="text-xs">
                                    @if ($addOn->container)
                                        <a href="{{ route('admin.containers', ['q' => $addOn->container->code]) }}" class="font-medium text-brand-600 hover:underline">{{ $addOn->container->code }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-portal.data-table>
            @else
                <p class="py-8 text-center text-sm text-gray-400">No add-ons purchased.</p>
            @endif
        </div>
    </div>
@endsection
