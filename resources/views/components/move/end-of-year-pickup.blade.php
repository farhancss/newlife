@props([
    'container',
    'eligible' => false,
    'storageAddOn' => null,
    'activePickup' => null,
    'timeline' => [],
    'defaultLocation' => '',
    'defaultContainerCount' => 1,
])

@php
    use App\Enums\StoragePickupStatus;

    $minDate = now()->addDay()->format('Y-m-d');
@endphp

<div class="overflow-hidden rounded-2xl border border-brand-200 bg-white">
    <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-8">
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4-9 4-9-4zm0 0v10l9 4 9-4V7M12 11v10"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-semibold text-brand-900">End-of-Year Pickup &amp; Storage</h2>
                <p class="mt-1 max-w-2xl text-sm text-brand-500">
                    Your move is complete. At the end of the academic year we can collect your containers from your dorm,
                    store them over the summer, and re-deliver them for the next academic cycle.
                </p>
            </div>
        </div>
    </div>

    <div class="px-5 py-6 sm:px-8">
        @if ($activePickup)
            {{-- Scheduled / in-progress pickup --}}
            @php
                $confirmedDate = $activePickup->confirmed_pickup_date;
                $requestedDate = $activePickup->requested_pickup_date;
            @endphp

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">
                    {{ $activePickup->statusLabel() }}
                </span>
                <span class="text-sm text-gray-500">
                    @if ($confirmedDate)
                        Confirmed for <span class="font-semibold text-brand-900">{{ $confirmedDate->format('M j, Y') }}</span>
                    @else
                        Requested for <span class="font-semibold text-brand-900">{{ $requestedDate->format('M j, Y') }}</span> · awaiting confirmation
                    @endif
                </span>
            </div>

            {{-- Storage journey progress --}}
            @if (!empty($timeline))
                <ol class="mt-6 space-y-3">
                    @foreach ($timeline as $step)
                        <li class="flex items-center gap-3">
                            @if ($step['current'])
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-[11px] font-semibold text-white">{{ $loop->iteration }}</span>
                            @elseif ($step['reached'])
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-300 text-[11px] font-semibold text-white">{{ $loop->iteration }}</span>
                            @else
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-gray-300 bg-white text-[11px] font-semibold text-gray-400">{{ $loop->iteration }}</span>
                            @endif
                            <span @class([
                                'text-sm',
                                'font-semibold text-brand-900' => $step['current'] || $step['reached'],
                                'text-gray-500' => !$step['current'] && !$step['reached'],
                            ])>{{ $step['label'] }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <dl class="mt-6 grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pickup location</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $activePickup->pickup_location }}</dd>
                </div>
                @if ($activePickup->contact_phone)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Contact phone</dt>
                        <dd class="mt-1 text-sm text-gray-800">{{ $activePickup->contact_phone }}</dd>
                    </div>
                @endif
                @if ($activePickup->container_count)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Containers to store</dt>
                        <dd class="mt-1 text-sm text-gray-800">{{ $activePickup->container_count }}</dd>
                    </div>
                @endif
                @if ($activePickup->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your notes</dt>
                        <dd class="mt-1 text-sm text-gray-800">{{ $activePickup->notes }}</dd>
                    </div>
                @endif
            </dl>
        @elseif ($eligible)
            {{-- Eligible: scheduling form --}}
            <p class="text-sm text-gray-600">
                Storage is included with your plan. Choose your preferred pickup date and confirm the details below — our team
                will reach out to confirm.
            </p>

            @error('storage')
                <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
            @enderror

            <form action="{{ route('student.move-tracking.end-of-year-pickup', $container) }}" method="POST"
                class="mt-5 space-y-5"
                data-confirm="This sends your end-of-year pickup request to our team for confirmation."
                data-confirm-title="Request end-of-year pickup?"
                data-confirm-button="Yes, request pickup"
                data-confirm-icon="question">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Preferred pickup date <span class="text-red-500">*</span></label>
                        <x-form.flatpickr-input
                            name="requested_pickup_date"
                            :value="old('requested_pickup_date')"
                            placeholder="Select a date"
                            icon="calendar"
                            class="mt-1.5 block h-[42px] w-full rounded-xl border border-gray-300 px-3 pr-10 text-sm shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200"
                            :options="[
                                'dateFormat' => 'Y-m-d',
                                'altInput' => true,
                                'altFormat' => 'F j, Y',
                                'minDate' => $minDate,
                            ]"
                        />
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700">Contact phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone"
                            value="{{ old('contact_phone', $container->studentProfile->phone) }}"
                            placeholder="Best number to reach you"
                            class="mt-1.5 block w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="pickup_location" class="block text-sm font-medium text-gray-700">Pickup location (dorm / building / room) <span class="text-red-500">*</span></label>
                        <input type="text" id="pickup_location" name="pickup_location" required maxlength="255"
                            value="{{ old('pickup_location', $defaultLocation) }}"
                            placeholder="e.g. West Hall, Room 214"
                            class="mt-1.5 block w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400" />
                    </div>

                    <div x-data="{ count: {{ (int) old('container_count', $defaultContainerCount) }}, min: 1, max: 99 }">
                        <label for="container_count" class="block text-sm font-medium text-gray-700">Number of containers</label>
                        <div class="mt-1.5 flex w-full items-stretch overflow-hidden rounded-xl border border-gray-300 shadow-sm focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-200">
                            <button type="button"
                                x-on:click="count = Math.max(min, count - 1)"
                                class="flex w-11 shrink-0 items-center justify-center border-r border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                x-bind:disabled="count <= min"
                                aria-label="Decrease container count">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                            </button>
                            <input type="number" id="container_count" name="container_count" min="1" max="99"
                                x-model.number="count"
                                x-on:input="if (count !== '') count = Math.min(max, Math.max(min, count))"
                                class="w-full min-w-0 border-0 px-3 py-2.5 text-center text-sm focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                            <button type="button"
                                x-on:click="count = Math.min(max, count + 1)"
                                class="flex w-11 shrink-0 items-center justify-center border-l border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                x-bind:disabled="count >= max"
                                aria-label="Increase container count">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Prefilled from your package allowance — adjust if needed.</p>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes for our team</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="2000"
                        placeholder="Access instructions, timing preferences, anything we should know…"
                        class="mt-1.5 block w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400">{{ old('notes') }}</textarea>
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                        Schedule end-of-year pickup
                    </button>
                    <p class="mt-2 text-xs text-gray-500">We'll notify our team and confirm your pickup date.</p>
                </div>
            </form>
        @else
            {{-- Not eligible: upsell the storage add-on --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-900">Storage isn't part of your plan yet</p>
                        <p class="mt-1 text-sm text-amber-800">
                            To schedule an end-of-year dorm pickup, add summer storage to your plan. Once it's active you'll be
                            able to pick your pickup date right here.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                @if ($storageAddOn)
                    <a href="{{ $storageAddOn['url'] }}" target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        Add {{ $storageAddOn['name'] }} · {{ $storageAddOn['formatted_price'] }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H9M17 7v8" /></svg>
                    </a>
                @endif
                <a href="{{ route('student.add-ons') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-brand-200 bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    View all add-ons
                </a>
            </div>
        @endif
    </div>
</div>
