import Swal from 'sweetalert2';

window.Swal = Swal;

/**
 * Progressive-enhancement confirm dialog. Any form carrying a `data-confirm`
 * attribute shows a themed SweetAlert prompt before submitting, replacing the
 * native browser confirm(). Optional attributes:
 *   - data-confirm-title:  heading (default "Are you sure?")
 *   - data-confirm-button: confirm button label (default "Confirm")
 *   - data-confirm-icon:   swal icon (default "question")
 */
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
        return;
    }

    if (form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    Swal.fire({
        title: form.dataset.confirmTitle || 'Are you sure?',
        text: form.dataset.confirm,
        icon: form.dataset.confirmIcon || 'question',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'Confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0827be',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        buttonsStyling: true,
    }).then((result) => {
        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    });
});
