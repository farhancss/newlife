import Alpine from 'alpinejs';

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const registerUserAvatarStore = () => {
    document.addEventListener('alpine:init', () => {
        const body = document.body;
        const url = body.dataset.userAvatarUrl ?? '';

        Alpine.store('userAvatar', {
            url: url !== '' ? url : null,
            initials: body.dataset.userInitials ?? 'NL',
            setAvatar(avatarUrl) {
                this.url = avatarUrl || null;
            },
        });
    });
};

export const registerProfileAvatar = () => {
    registerUserAvatarStore();

    Alpine.data('profileAvatar', () => ({
        preview: null,
        uploading: false,
        error: null,
        updateUrl: '',
        destroyUrl: '',

        init() {
            this.updateUrl = this.$el.dataset.updateUrl ?? '';
            this.destroyUrl = this.$el.dataset.destroyUrl ?? '';
        },

        uploadLabel() {
            return this.$store.userAvatar.url ? 'Change photo' : 'Upload photo';
        },

        async uploadAvatar(file) {
            if (!file || !this.updateUrl) {
                return;
            }

            this.error = null;
            const previewUrl = URL.createObjectURL(file);
            this.preview = previewUrl;
            this.uploading = true;

            const formData = new FormData();
            formData.append('avatar', file);
            formData.append('_token', csrfToken());

            try {
                const response = await fetch(this.updateUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.errors?.avatar?.[0] ?? data.message ?? 'Upload failed.');
                }

                URL.revokeObjectURL(previewUrl);
                this.$store.userAvatar.setAvatar(data.avatar_url ?? null);
                this.preview = null;
            } catch (error) {
                URL.revokeObjectURL(previewUrl);
                this.preview = null;
                this.error = error?.message ?? 'Upload failed.';
            } finally {
                this.uploading = false;

                if (this.$refs.avatarInput) {
                    this.$refs.avatarInput.value = '';
                }
            }
        },

        async removeAvatar() {
            if (!this.destroyUrl || !confirm('Remove your profile photo?')) {
                return;
            }

            this.error = null;
            this.uploading = true;

            try {
                const response = await fetch(this.destroyUrl, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message ?? 'Remove failed.');
                }

                this.$store.userAvatar.setAvatar(null);
                this.preview = null;
            } catch (error) {
                this.error = error?.message ?? 'Remove failed.';
            } finally {
                this.uploading = false;
            }
        },
    }));
};
