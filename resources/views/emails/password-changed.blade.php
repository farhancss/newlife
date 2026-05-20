@php
    $brandName = config('brand.name');
    $supportEmail = config('brand.support.email');
    $firstName = trim(\Illuminate\Support\Str::before($user->name ?? '', ' ')) ?: $user->name;
    $heading = $wasFirstReset
        ? 'Password set — you are ready to go'
        : 'Your password was just updated';
    $preheader = $wasFirstReset
        ? 'Your ' . $brandName . ' password is set. Finish your profile to unlock the dashboard.'
        : 'A password change was just made on your ' . $brandName . ' account.';
@endphp

<x-email.layout
    :title="$brandName . ' — password updated'"
    :preheader="$preheader"
    :heading="$heading"
>
    <p style="margin:0 0 12px 0;">Hi {{ $firstName ?: 'there' }},</p>

    @if ($wasFirstReset)
        <p style="margin:0 0 12px 0;">
            Your {{ $brandName }} Portal password has been set successfully. The next step is to complete your student profile so we can prepare everything for your move-in.
        </p>
    @else
        <p style="margin:0 0 12px 0;">
            Your {{ $brandName }} Portal password was just updated. If this was you, no further action is needed.
        </p>
        <p style="margin:0 0 12px 0;font-weight:600;color:#101828;">
            If this wasn’t you, please contact support immediately{!! $supportEmail ? ' at <a href="mailto:' . e($supportEmail) . '" style="color:#0827be;text-decoration:none;">' . e($supportEmail) . '</a>' : '' !!}.
        </p>
    @endif

    <x-email.button :href="$loginUrl">
        Sign in to the portal
    </x-email.button>

    <p style="margin:16px 0 0 0;font-size:13px;color:#6b7280;line-height:20px;">
        This is an automated confirmation from {{ $brandName }}.
    </p>
</x-email.layout>
