document.addEventListener('alpine:init', () => {
    window.Alpine.data('appKebab', () => ({
        open: false,
        menuStyle: '',

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.placeMenu());
            }
        },

        close() {
            this.open = false;
        },

        onOutside(event) {
            if (! this.open) {
                return;
            }

            const target = event.target;
            if (this.$refs.trigger?.contains(target) || this.$refs.menu?.contains(target)) {
                return;
            }

            this.close();
        },

        placeMenu() {
            const trigger = this.$refs.trigger;
            const menu = this.$refs.menu;

            if (! trigger || ! menu) {
                return;
            }

            const gap = 6;
            const pad = 8;
            const rect = trigger.getBoundingClientRect();
            const menuHeight = menu.offsetHeight;
            const menuWidth = menu.offsetWidth;

            let top = rect.bottom + gap;
            if (top + menuHeight > window.innerHeight - pad) {
                top = Math.max(pad, rect.top - menuHeight - gap);
            }

            let left = rect.right - menuWidth;
            if (left < pad) {
                left = pad;
            }
            if (left + menuWidth > window.innerWidth - pad) {
                left = Math.max(pad, window.innerWidth - menuWidth - pad);
            }

            this.menuStyle = `position:fixed;top:${Math.round(top)}px;left:${Math.round(left)}px;z-index:80;`;
        },
    }));
});
