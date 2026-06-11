@php
    $brandName = config('brand.name');
    $firstName = trim(\Illuminate\Support\Str::before($user->name ?? '', ' ')) ?: $user->name;
@endphp

<x-email.layout
    :title="$brandName . ' — reset your password'"
    :preheader="'Use this link to choose a new password for your ' . $brandName . ' account.'"
    :heading="'Reset your password'"
>
    <p style="margin:0 0 12px 0;">
        Hi {{ $firstName ?: 'there' }}, we received a request to reset the password for your {{ $brandName }} account ({{ $user->email }}).
    </p>

    <x-email.button :href="$resetUrl">
        Reset password
    </x-email.button>

    <p style="margin:16px 0 0 0;font-size:13px;color:#6b7280;line-height:20px;">
        This link expires in {{ config('auth.passwords.users.expire', 60) }} minutes. If you did not request a reset, you can ignore this email — your password will stay the same.
    </p>
</x-email.layout>
