@props([
    'passwordName' => 'password',
    'confirmName' => 'password_confirmation',
    'passwordLabel' => 'New Password',
    'confirmLabel' => 'Confirm Password',
    'showCurrent' => false,
    'currentName' => 'current_password',
    'currentLabel' => 'Current Password',
    'variant' => 'default',
])

@php
    $isAuth = $variant === 'auth';
    $requirements = $isAuth
        ? \App\Support\PasswordPolicy::authRequirements()
        : \App\Support\PasswordPolicy::requirements();

    $inputClass = $isAuth
        ? 'h-12 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-hidden transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10'
        : 'h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-200';

    $passwordInputClass = $inputClass . ($errors->has($passwordName)
        ? ($isAuth ? ' border-error-300 focus:border-error-300' : ' border-error-300 focus:border-error-300 focus:ring-error-200')
        : '');

    $confirmInputClass = $inputClass . ($errors->has($confirmName)
        ? ($isAuth ? ' border-error-300 focus:border-error-300' : ' border-error-300 focus:border-error-300 focus:ring-error-200')
        : '');

    $labelClass = $isAuth
        ? 'mb-1.5 block text-sm font-medium text-gray-700'
        : 'mb-1 block text-sm font-medium text-gray-700';
@endphp

<div
    x-data="{
        password: '',
        confirm: '',
        showPassword: false,
        showConfirm: false,
        requirements: @js($requirements),
        get checks() {
            const value = this.password;
            return {
                minLength: value.length >= 8,
                uppercase: /[A-Z]/.test(value),
                lowercase: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                symbol: /[^A-Za-z0-9]/.test(value),
            };
        },
        get allMet() {
            return Object.values(this.checks).every(Boolean);
        },
        get confirmMatch() {
            return this.confirm.length > 0 && this.password === this.confirm;
        },
        get canSubmit() {
            return this.allMet && this.confirmMatch;
        },
    }"
    @class([
        'grid gap-4' => ! $isAuth,
        'space-y-5' => $isAuth,
        'max-w-xl' => ! $isAuth,
    ])
>
    @if ($showCurrent)
        <div>
            <label for="{{ $currentName }}" class="{{ $labelClass }}">{{ $currentLabel }}</label>
            <input
                type="password"
                id="{{ $currentName }}"
                name="{{ $currentName }}"
                autocomplete="current-password"
                class="{{ $inputClass }}"
            />
        </div>
    @endif

    <div>
        <label for="{{ $passwordName }}" class="{{ $labelClass }}">
            {{ $passwordLabel }}@if ($isAuth)<span class="text-error-500"> *</span>@endif
        </label>
        <div class="relative">
            <input
                :type="showPassword ? 'text' : 'password'"
                id="{{ $passwordName }}"
                name="{{ $passwordName }}"
                x-model="password"
                autocomplete="new-password"
                @if ($isAuth) placeholder="Enter your password" @endif
                class="{{ $passwordInputClass }} @if ($isAuth) pr-11 @endif"
            />
            @if ($isAuth)
                <x-form.password-visibility-toggle target="showPassword" />
            @endif
        </div>
    </div>

    @if ($isAuth)
        <div>
            <label for="{{ $confirmName }}" class="{{ $labelClass }}">
                {{ $confirmLabel }}<span class="text-error-500"> *</span>
            </label>
            <div class="relative">
                <input
                    :type="showConfirm ? 'text' : 'password'"
                    id="{{ $confirmName }}"
                    name="{{ $confirmName }}"
                    x-model="confirm"
                    autocomplete="new-password"
                    placeholder="Retype your password"
                    class="{{ $confirmInputClass }} pr-11"
                />
                <x-form.password-visibility-toggle target="showConfirm" />
            </div>
            <p
                x-show="confirm.length > 0 && !confirmMatch"
                x-cloak
                class="mt-1.5 text-sm text-error-600"
            >
                Passwords do not match.
            </p>
        </div>

        <ul class="my-4 grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2" aria-live="polite">
            <template x-for="(label, key) in requirements" :key="key">
                <li class="flex items-center gap-2.5 text-sm">
                    <span
                        class="flex h-5 w-5 shrink-0 items-center justify-center transition-colors"
                        :class="checks[key] ? 'text-success-500' : 'text-gray-300'"
                        aria-hidden="true"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <span
                        class="transition-colors"
                        :class="checks[key] ? 'font-medium text-gray-800' : 'text-gray-400'"
                        x-text="label"
                    ></span>
                </li>
            </template>
        </ul>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4" aria-live="polite">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Password must include</p>
            <ul class="mt-3 space-y-2">
                <template x-for="(label, key) in requirements" :key="key">
                    <li class="flex items-start gap-2 text-sm">
                        <span
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                            :class="checks[key] ? 'bg-success-100 text-success-700' : 'bg-gray-200 text-gray-500'"
                            aria-hidden="true"
                        >
                            <svg x-show="checks[key]" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg x-show="!checks[key]" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="4" />
                            </svg>
                        </span>
                        <span :class="checks[key] ? 'text-gray-800' : 'text-gray-500'" x-text="label"></span>
                    </li>
                </template>
            </ul>
            <p
                x-show="password.length > 0 && allMet"
                x-cloak
                class="mt-3 text-sm font-medium text-success-700"
            >
                Password meets all requirements.
            </p>
        </div>

        <div>
            <label for="{{ $confirmName }}" class="{{ $labelClass }}">{{ $confirmLabel }}</label>
            <input
                type="password"
                id="{{ $confirmName }}"
                name="{{ $confirmName }}"
                x-model="confirm"
                autocomplete="new-password"
                class="{{ $confirmInputClass }}"
            />
            <p
                x-show="confirm.length > 0 && !confirmMatch"
                x-cloak
                class="mt-1.5 text-sm text-error-600"
            >
                Passwords do not match.
            </p>
            <p
                x-show="confirmMatch"
                x-cloak
                class="mt-1.5 text-sm font-medium text-success-700"
            >
                Passwords match.
            </p>
        </div>
    @endif

    <div {{ $attributes->merge(['class' => $isAuth ? 'space-y-5 pt-1' : '']) }}>
        {{ $slot }}
    </div>
</div>
