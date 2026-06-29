import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

/**
 * Wire up image galleries: any anchor with the `glightbox` class opens its
 * target image in an in-page lightbox (with gallery navigation) instead of a
 * new browser tab. Grouping is controlled by each anchor's `data-gallery`.
 */
let instance = null;

function initLightbox() {
    if (!document.querySelector('.glightbox')) {
        return;
    }

    if (instance) {
        instance.reload();
        return;
    }

    instance = GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true,
        zoomable: true,
        openEffect: 'fade',
        closeEffect: 'fade',
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLightbox);
} else {
    initLightbox();
}
