import './bootstrap';
import './portal-datatable';
import './confirm';
import './flash';
import './lightbox';
import './dashboard-charts';
import { extractTrackingNumber } from './tracking-number';
import { registerContainerPhotoPicker } from './container-photo-picker';
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

Alpine.start();
