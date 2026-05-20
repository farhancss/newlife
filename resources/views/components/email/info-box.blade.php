@props([
    'label' => null,
])

@php
    $primaryLight = '#e9edfe';
    $border = '#d3dafc';
    $navy = '#040f5c';
    $muted = '#6b7280';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background-color:{{ $primaryLight }};border:1px solid {{ $border }};border-radius:12px;">
    <tr>
        <td style="padding:16px 20px;">
            @if ($label)
                <div style="font-family:'Outfit',Arial,sans-serif;font-size:11px;font-weight:600;color:{{ $muted }};letter-spacing:0.6px;text-transform:uppercase;margin-bottom:4px;">
                    {{ $label }}
                </div>
            @endif
            <div style="font-family:'Outfit',Arial,sans-serif;font-size:15px;line-height:22px;color:{{ $navy }};word-break:break-word;">
                {{ $slot }}
            </div>
        </td>
    </tr>
</table>
