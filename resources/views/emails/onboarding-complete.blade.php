@php
    $brandName = config('brand.name');
    $firstName = trim(\Illuminate\Support\Str::before($user->name ?? '', ' ')) ?: $user->name;
@endphp

<x-email.layout
    :title="'You are all set with ' . $brandName"
    :preheader="'Your profile is complete. Your ' . $brandName . ' dashboard is ready.'"
    :heading="'You are all set, ' . ($firstName ?: 'there') . '!'"
>
    <p style="margin:0 0 12px 0;">
        Your {{ $brandName }} Portal profile is complete. You now have full access to your dashboard where you can track packages, review your delivery schedule, and manage every detail of your move-in.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;">
        <tr>
            <td style="padding:16px 20px;background-color:#f9fafb;border:1px solid #e4e7ec;border-radius:12px;">
                <div style="font-family:'Outfit',Arial,sans-serif;font-size:11px;font-weight:600;color:#6b7280;letter-spacing:0.6px;text-transform:uppercase;margin-bottom:6px;">
                    What's next
                </div>
                <ul style="margin:0;padding-left:18px;font-family:'Outfit',Arial,sans-serif;font-size:14px;line-height:22px;color:#1f2937;">
                    <li>Track your shipments in real time</li>
                    <li>Review your move-in date and shipping address</li>
                    <li>Get updates the moment your items arrive on campus</li>
                </ul>
            </td>
        </tr>
    </table>

    <x-email.button :href="$dashboardUrl">
        Open your dashboard
    </x-email.button>

    <p style="margin:16px 0 0 0;">
        We’re excited to be part of your move to campus. If anything comes up, our team is just one reply away.
    </p>
</x-email.layout>
