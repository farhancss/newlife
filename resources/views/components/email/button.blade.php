@props([
    'href',
    'variant' => 'primary',
])

@php
    $primary = '#0827be';

    $styles = match ($variant) {
        'secondary' => [
            'bg' => '#ffffff',
            'border' => '1px solid #d0d5dd',
            'color' => '#1f2937',
        ],
        default => [
            'bg' => $primary,
            'border' => 'none',
            'color' => '#ffffff',
        ],
    };
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn" style="margin:24px 0;">
    <tr>
        <td align="center" bgcolor="{{ $styles['bg'] }}" style="border-radius:10px;">
            <a href="{{ $href }}"
               target="_blank"
               style="display:inline-block;padding:12px 28px;font-family:'Outfit',Arial,sans-serif;font-size:15px;font-weight:600;line-height:20px;color:{{ $styles['color'] }} !important;text-decoration:none;border-radius:10px;background-color:{{ $styles['bg'] }};border:{{ $styles['border'] }};">
                <span style="color:{{ $styles['color'] }} !important;text-decoration:none;">{{ $slot }}</span>
            </a>
        </td>
    </tr>
</table>
