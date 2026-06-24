import { DataTable } from 'simple-datatables';

const defaultLabels = {
    placeholder: 'Search...',
    searchTitle: 'Search table',
    perPage: 'per page',
    noRows: 'No entries found',
    noResults: 'No results match your search',
    info: 'Showing {start} to {end} of {rows} entries',
};

const buildPerPageSelect = (perPage, perPageSelectRaw) => {
    const values = perPageSelectRaw
        .split(',')
        .map((value) => Number.parseInt(value.trim(), 10))
        .filter((value) => !Number.isNaN(value));

    if (!values.includes(perPage)) {
        values.push(perPage);
    }

    return [...new Set(values)].sort((a, b) => a - b);
};

const createPortalTableTemplate = (layout) => (options, dom) => {
    const perPageControl =
        options.paging && options.perPageSelect
            ? `<div class="portal-dt-perpage ${options.classes.dropdown}">
                <span class="portal-dt-perpage-text">Show</span>
                <select class="${options.classes.selector} portal-dt-select"></select>
                <span class="portal-dt-perpage-text">${options.labels.perPage}</span>
            </div>`
            : '';

    const searchControl = options.searchable
        ? `<div class="portal-dt-search ${options.classes.search}">
            <svg class="portal-dt-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M9.16667 15.8333C12.8486 15.8333 15.8333 12.8486 15.8333 9.16667C15.8333 5.48477 12.8486 2.5 9.16667 2.5C5.48477 2.5 2.5 5.48477 2.5 9.16667C2.5 12.8486 5.48477 15.8333 9.16667 15.8333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17.5 17.5L14.5833 14.5833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input
                class="${options.classes.input} portal-dt-search-input"
                placeholder="${options.labels.placeholder}"
                type="search"
                title="${options.labels.searchTitle}"
                ${dom.id ? `aria-controls="${dom.id}"` : ''}
            >
        </div>`
        : '';

    const titleControl = layout.title
        ? `<h2 class="portal-dt-title">${layout.title}</h2>`
        : '';

    const toolbarControls = layout.perPageAtBottom
        ? `${titleControl}${searchControl}`
        : `${titleControl}${perPageControl}${searchControl}`;

    const footerStart = [
        options.paging ? `<div class="${options.classes.info}"></div>` : '',
        layout.perPageAtBottom ? perPageControl : '',
    ].filter(Boolean).join('');

    const paginationControl = options.paging && !layout.hidePagination
        ? `<nav class="${options.classes.pagination}"></nav>`
        : '';

    return `<div class="portal-dt-toolbar ${options.classes.top}">
        ${toolbarControls}
    </div>
    <div class="portal-dt-table-wrap ${options.classes.container}"${options.scrollY.length ? ` style="height: ${options.scrollY}; overflow-y: auto;"` : ''}></div>
    <div class="portal-dt-footer ${options.classes.bottom}">
        <div class="portal-dt-footer-start">${footerStart}</div>
        ${paginationControl}
    </div>`;
};

const initPortalDataTables = () => {
    document.querySelectorAll('[data-portal-datatable]').forEach((wrapper) => {
        const table = wrapper.querySelector('table');

        if (!table || table.dataset.datatableInitialized === 'true') {
            return;
        }

        const perPage = Number.parseInt(wrapper.dataset.perPage ?? '10', 10);
        const perPageSelect = buildPerPageSelect(
            perPage,
            wrapper.dataset.perPageSelect ?? '5,10,15,25,50',
        );

        const paging = wrapper.dataset.paging !== 'false';
        const layout = {
            title: wrapper.dataset.title ?? '',
            perPageAtBottom: wrapper.dataset.perPageAtBottom === 'true',
            hidePagination: wrapper.dataset.hidePagination === 'true',
        };

        const labels = {
            ...defaultLabels,
            ...(wrapper.dataset.searchPlaceholder
                ? { placeholder: wrapper.dataset.searchPlaceholder }
                : {}),
            ...(layout.hidePagination
                ? { info: 'Showing {start} to {end} of {rows}' }
                : {}),
        };

        const nonSortableIndexes = [...table.querySelectorAll('thead th')].reduce((indexes, th, index) => {
            if (th.dataset.sortable === 'false') {
                indexes.push(index);
            }

            return indexes;
        }, []);

        const columnOptions = nonSortableIndexes.map((select) => ({
            select,
            sortable: false,
        }));

        new DataTable(table, {
            searchable: true,
            sortable: true,
            paging,
            perPage,
            perPageSelect,
            labels,
            template: createPortalTableTemplate(layout),
            nextPrev: true,
            firstLast: false,
            ...(columnOptions.length ? { columns: columnOptions } : {}),
        });

        table.dataset.datatableInitialized = 'true';
        wrapper.classList.add('portal-datatable-ready');

        if (layout.perPageAtBottom) {
            wrapper.classList.add('portal-datatable-perpage-bottom');
        }

        if (layout.hidePagination) {
            wrapper.classList.add('portal-datatable-no-pagination');
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPortalDataTables);
} else {
    initPortalDataTables();
}
