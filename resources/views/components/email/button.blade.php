@props([
    'href',
    'variant' => 'primary',
])

@php
    $primary = '#0827be';
    $primaryDark = '#061f98';

    $styles = match ($variant) {
        'secondary' => [
            'bg' => '#ffffff',
            'border' => '#d0d5dd',
            'color' => '#1f2937',
        ],
        default => [
            'bg' => $primary,
            'border' => $primary,
            'color' => '#ffffff',
        ],
    };
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn" style="margin:24px 0;">
    <tr>
        <td align="center" bgcolor="{{ $styles['bg'] }}" style="border-radius:10px;border:1px solid {{ $styles['border'] }};">
            <a href="{{ $href }}"
               target="_blank"
               style="display:inline-block;padding:12px 28px;font-family:'Outfit',Arial,sans-serif;font-size:15px;font-weight:600;line-height:20px;color:{{ $styles['color'] }};text-decoration:none;border-radius:10px;">
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>
