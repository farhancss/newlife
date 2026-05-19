@props([
    'perPage' => 10,
    'perPageSelect' => '5,10,15,25,50',
    'tableClass' => '',
    'compact' => false,
    'flush' => false,
    'searchPlaceholder' => null,
])

@php
    $wrapperClass = collect([
        'portal-datatable-wrapper overflow-hidden bg-white',
        $compact ? 'portal-datatable-compact' : '',
        $flush ? 'portal-datatable-flush border-0 shadow-none rounded-none' : 'rounded-2xl border border-gray-200 shadow-theme-xs',
    ])->filter()->implode(' ');

    $effectivePerPage = $compact && $perPage === 10 ? 5 : $perPage;
    $effectivePerPageSelect = $compact ? '5,10,15' : $perPageSelect;
@endphp

<div
    {{ $attributes->merge(['class' => $wrapperClass]) }}
    data-portal-datatable
    data-per-page="{{ $effectivePerPage }}"
    data-per-page-select="{{ $effectivePerPageSelect }}"
    @if($searchPlaceholder) data-search-placeholder="{{ $searchPlaceholder }}" @endif
>
    <table @class([
        'portal-datatable w-full text-left',
        $tableClass,
    ])>
        {{ $slot }}
    </table>
</div>
