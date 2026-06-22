import Swal from 'sweetalert2';

/**
 * Show server-side flash messages (success / error / warning) and validation
 * errors as SweetAlert popups instead of inline banners. The payload is set by
 * the layout in `window.flashMessages`.
 */
function showFlashMessages() {
    const flash = window.flashMessages || {};

    const errors = Array.isArray(flash.errors) ? flash.errors.filter(Boolean) : [];

    if (errors.length > 0) {
        Swal.fire({
            icon: 'error',
            title: errors.length > 1 ? 'Please fix the following' : 'Something needs attention',
            html: errors.length > 1
                ? '<ul style="text-align:left;margin:0;padding-left:1.2em;">' +
                    errors.map((e) => `<li>${escapeHtml(e)}</li>`).join('') +
                    '</ul>'
                : escapeHtml(errors[0]),
            confirmButtonColor: '#0827be',
        });
        return;
    }

    if (flash.success) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: flash.success,
            confirmButtonColor: '#0827be',
            timer: 4000,
            timerProgressBar: true,
        });
        return;
    }

    if (flash.error) {
        Swal.fire({ icon: 'error', title: 'Error', text: flash.error, confirmButtonColor: '#0827be' });
        return;
    }

    if (flash.warning) {
        Swal.fire({ icon: 'warning', title: 'Heads up', text: flash.warning, confirmButtonColor: '#0827be' });
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', showFlashMessages);
} else {
    showFlashMessages();
}
