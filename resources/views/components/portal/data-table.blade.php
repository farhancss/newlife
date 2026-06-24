@props([
    'perPage' => 10,
    'perPageSelect' => '5,10,15,25,50',
    'tableClass' => '',
    'compact' => false,
    'flush' => false,
    'searchPlaceholder' => null,
    'title' => null,
    'paging' => true,
    'perPageAtBottom' => false,
    'hidePagination' => false,
    'variant' => null,
])

@php
    $wrapperClass = collect([
        'portal-datatable-wrapper overflow-hidden bg-white',
        $compact ? 'portal-datatable-compact' : '',
        $variant === 'retail' ? 'portal-datatable-retail' : '',
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
    data-paging="{{ $paging ? 'true' : 'false' }}"
    @if($title) data-title="{{ $title }}" @endif
    @if($perPageAtBottom) data-per-page-at-bottom="true" @endif
    @if($hidePagination) data-hide-pagination="true" @endif
    @if($searchPlaceholder) data-search-placeholder="{{ $searchPlaceholder }}" @endif
>
    <table @class([
        'portal-datatable w-full text-left',
        $tableClass,
    ])>
        {{ $slot }}
    </table>
</div>
