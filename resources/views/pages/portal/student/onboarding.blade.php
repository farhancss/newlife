@extends('layouts.app')

@section('content')
    @php
        $shipping = $profile->shippingAddress;
        $parent = $profile->parentGuardian;
        $housing = $profile->housingInfo;
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Complete Your Profile</h1>
                    <p class="mt-1 text-sm text-gray-600">Step {{ $step }} of 4 — required before using the portal</p>
                </div>
                @if ($profile->package_tier)
                    <span class="w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                        Package: {{ ucfirst($profile->package_tier) }}
                    </span>
                @endif
            </div>
            <div class="mt-4 grid gap-2 sm:grid-cols-4">
                @for ($i = 1; $i <= 4; $i++)
                    <div class="h-2 rounded-full {{ $i <= $step ? 'bg-brand-600' : 'bg-gray-200' }}"></div>
                @endfor
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <form method="POST" action="{{ route('student.onboarding.submit') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <input type="hidden" name="step" value="{{ $step }}" />

                @if ($step === 1)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">Student Information</h2>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $profile->first_name) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $profile->last_name) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $profile->phone) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">School</label>
                        <input type="text" name="school" value="{{ old('school', $profile->school) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Incoming Year / Class</label>
                        <input type="text" name="incoming_year" value="{{ old('incoming_year', $profile->incoming_year) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" value="{{ $user->email }}" disabled
                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600" />
                    </div>
                @endif

                @if ($step === 2)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">Parent / Guardian</h2>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="parent_name" value="{{ old('parent_name', $parent?->name) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Relationship</label>
                        <input type="text" name="parent_relationship" value="{{ old('parent_relationship', $parent?->relationship) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="parent_email" value="{{ old('parent_email', $parent?->email) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="parent_phone" value="{{ old('parent_phone', $parent?->phone) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                @endif

                @if ($step === 3)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">Home Address</h2>
                        <p class="mt-1 text-sm text-gray-500">Where you live now — we pick up your items here before your move to campus.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Street Address</label>
                        <input type="text" name="line1" value="{{ old('line1', $shipping?->line1) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Apt, Suite, or Unit (optional)</label>
                        <input type="text" name="line2" value="{{ old('line2', $shipping?->line2) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">City</label>
                        <input type="text" name="city" value="{{ old('city', $shipping?->city) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">State</label>
                        <input type="text" name="region" value="{{ old('region', $shipping?->region) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ZIP Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $shipping?->postal_code) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" name="country_code" value="{{ old('country_code', $shipping?->country_code ?? 'US') }}" maxlength="2"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Home Pickup Notes (optional)</label>
                        <textarea name="shipping_notes" rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('shipping_notes', $shipping?->shipping_notes) }}</textarea>
                    </div>
                @endif

                @if ($step === 4)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">University Dorm</h2>
                        <p class="mt-1 text-sm text-gray-500">Where you are moving on campus — your dorm delivery destination.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">University</label>
                        <input type="text" name="university" value="{{ old('university', $housing?->university) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Dorm / Residence Hall</label>
                        <input type="text" name="residence_hall" value="{{ old('residence_hall', $housing?->residence_hall) }}" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Building (optional)</label>
                        <input type="text" name="building" value="{{ old('building', $housing?->building) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Room Number (optional)</label>
                        <input type="text" name="room" value="{{ old('room', $housing?->room) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="move_in_date">Move-in Date</label>
                        <x-form.flatpickr-input
                            name="move_in_date"
                            :value="old('move_in_date', $housing?->move_in_date?->format('Y-m-d'))"
                            placeholder="Select a date"
                            icon="calendar"
                            :options="[
                                'dateFormat' => 'Y-m-d',
                                'altInput' => true,
                                'altFormat' => 'F j, Y',
                                'minDate' => 'today',
                            ]"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="move_in_window">Move-in Time Window (optional)</label>
                        <x-form.flatpickr-input
                            name="move_in_window"
                            :value="old('move_in_window', $housing?->move_in_window)"
                            placeholder="Select a time"
                            icon="clock"
                            :options="[
                                'enableTime' => true,
                                'noCalendar' => true,
                                'dateFormat' => 'h:i K',
                                'time_24hr' => false,
                                'minuteIncrement' => 15,
                                'defaultHour' => 10,
                                'defaultMinute' => 0,
                            ]"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Dorm Delivery Notes (optional)</label>
                        <textarea name="delivery_notes" rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('delivery_notes', $housing?->delivery_notes) }}</textarea>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3 md:col-span-2">
                    @if ($step > 1)
                        <button type="submit" name="action" value="back"
                            class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Back
                        </button>
                    @endif
                    @if ($step < 4)
                        <button type="submit" name="action" value="next"
                            class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            Save & Continue
                        </button>
                    @else
                        <button type="submit" name="action" value="complete"
                            class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            Complete Profile
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
