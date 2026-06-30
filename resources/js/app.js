import './bootstrap';
import './confirm';
import './flash';
import { extractTrackingNumber } from './tracking-number';
import { registerContainerPhotoPicker } from './container-photo-picker';
import { registerProfileAvatar } from './profile-avatar';
import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';

window.Alpine = Alpine;
window.flatpickr = flatpickr;

/**
 * Reusable state for the retail-package tracking inputs: when a tracking URL is
 * pasted, the tracking number is extracted and mirrored into its field while
 * remaining manually editable.
 */
Alpine.data('trackingFields', (initialUrl = '', initialNumber = '') => ({
    url: initialUrl ?? '',
    number: initialNumber ?? '',
    syncFromUrl() {
        const extracted = extractTrackingNumber(this.url);

        if (extracted) {
            this.number = extracted;
        }
    },
}));

registerContainerPhotoPicker();
registerProfileAvatar();

Alpine.start();

/** Load heavier page modules only when their markup is present. */
function loadPageModules() {
    if (document.querySelector('[data-portal-datatable]')) {
        import('./portal-datatable');
    }

    if (document.querySelector('.glightbox')) {
        import('./lightbox');
    }

    if (document.querySelector('[data-chart]')) {
        import('./dashboard-charts');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadPageModules);
} else {
    loadPageModules();
}
