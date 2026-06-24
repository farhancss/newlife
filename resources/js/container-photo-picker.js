import Alpine from 'alpinejs';

export const isValidContainerPhoto = (file) => {
    if (!file) {
        return false;
    }

    const type = (file.type ?? '').toLowerCase();

    if (type.startsWith('image/')) {
        return true;
    }

    return /\.(jpe?g|png)$/i.test(file.name ?? '');
};

export const registerContainerPhotoPicker = () => {
    Alpine.data('containerPhotoPicker', (remaining = 0) => ({
        remaining: Number(remaining) || 0,
        pendingFiles: [],

        canAddMore() {
            return this.pendingFiles.length < this.remaining;
        },

        addFiles(event) {
            const input = event.target;
            const selected = Array.from(input.files ?? []).filter((file) => isValidContainerPhoto(file));
            const slotsLeft = this.remaining - this.pendingFiles.length;

            if (slotsLeft <= 0 || selected.length === 0) {
                input.value = '';
                return;
            }

            const nextItems = selected.slice(0, slotsLeft).map((file, index) => ({
                id: `${Date.now()}-${index}-${file.name}`,
                file,
                preview: URL.createObjectURL(file),
            }));

            this.pendingFiles = [...this.pendingFiles, ...nextItems];
            this.syncInput();
            input.value = '';
        },

        removePending(id) {
            const item = this.pendingFiles.find((entry) => entry.id === id);

            if (!item) {
                return;
            }

            URL.revokeObjectURL(item.preview);
            this.pendingFiles = this.pendingFiles.filter((entry) => entry.id !== id);
            this.syncInput();
        },

        syncInput() {
            const input = this.$refs.fileInput;

            if (!input) {
                return;
            }

            const dataTransfer = new DataTransfer();
            this.pendingFiles.forEach(({ file }) => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
        },

        async submitUpload(event) {
            event.preventDefault();

            if (this.pendingFiles.length === 0) {
                return;
            }

            const form = event.target;
            const formData = new FormData();
            const token = form.querySelector('input[name="_token"]')?.value;
            const acknowledged = form.querySelector('input[name="acknowledge"]')?.checked;

            if (token) {
                formData.append('_token', token);
            }

            if (acknowledged) {
                formData.append('acknowledge', '1');
            }

            this.pendingFiles.forEach(({ file }) => {
                formData.append('photos[]', file);
            });

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                redirect: 'manual',
            });

            if (response.status === 302 || response.status === 303) {
                const location = response.headers.get('Location');

                window.location.href = location || window.location.href;

                return;
            }

            if (response.ok) {
                window.location.reload();

                return;
            }

            window.location.reload();
        },
    }));
};
