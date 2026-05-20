@php
    $brandName = config('brand.name');
    $firstName = trim(\Illuminate\Support\Str::before($user->name ?? '', ' ')) ?: $user->name;
@endphp

<x-email.layout
    :title="$brandName . ' — your account is ready'"
    :preheader="'Your ' . $brandName . ' Portal account is set up. Sign in to finish your profile.'"
    :heading="'Welcome aboard, ' . ($firstName ?: 'there') . '!'"
>
    <p style="margin:0 0 12px 0;">
        Your {{ $brandName }} Portal account has been created. Sign in with the credentials below — you will be asked to set a new password and complete your profile on first login.
    </p>

    <x-email.info-box label="Email">
        <strong style="font-weight:600;">{{ $user->email }}</strong>
    </x-email.info-box>

    <x-email.info-box label="Temporary password">
        <span style="font-family:'SF Mono','Roboto Mono',Menlo,Consolas,monospace;font-size:16px;letter-spacing:0.5px;font-weight:600;">{{ $temporaryPassword }}</span>
    </x-email.info-box>

    <x-email.button :href="$loginUrl">
        Sign in to the portal
    </x-email.button>

    <p style="margin:16px 0 0 0;font-size:13px;color:#6b7280;line-height:20px;">
        For your security, change your temporary password right after signing in. If you did not purchase a package with {{ $brandName }}, you can safely ignore this email.
    </p>
</x-email.layout>
