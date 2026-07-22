import '../accessibility-settings';
import '../locale-switch';
import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('landingHeader', () => ({
        floating: false,
        headerHeight: 0,

        init() {
            this.measure();
            this.onScroll();
            window.addEventListener('resize', () => this.measure(), { passive: true });
        },

        measure() {
            this.headerHeight = this.$refs.header?.offsetHeight ?? 0;
        },

        onScroll() {
            const shouldFloat = window.scrollY > 64;

            if (shouldFloat !== this.floating) {
                this.floating = shouldFloat;

                if (shouldFloat) {
                    this.measure();
                }
            }
        },
    }));

    Alpine.data('landingCarousel', (total = 5) => ({
        active: Math.min(2, Math.max(0, total - 1)),
        total,
        timer: null,

        init() {
            if (this.total < 1) {
                this.total = 1;
            }

            if (this.active >= this.total) {
                this.active = 0;
            }

            this.startAutoplay();
        },

        startAutoplay() {
            this.stopAutoplay();
            this.timer = window.setInterval(() => this.next(), 5500);
        },

        stopAutoplay() {
            if (this.timer) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
        },

        next() {
            this.active = (this.active + 1) % this.total;
        },

        prev() {
            this.active = (this.active - 1 + this.total) % this.total;
        },

        go(index) {
            this.active = index;
            this.startAutoplay();
        },

        offset(index) {
            let diff = index - this.active;

            if (diff > this.total / 2) {
                diff -= this.total;
            }
            if (diff < -this.total / 2) {
                diff += this.total;
            }

            return diff;
        },

        slideClass(index) {
            const diff = this.offset(index);

            if (diff === 0) {
                return 'is-active';
            }
            if (diff === -1) {
                return 'is-prev';
            }
            if (diff === 1) {
                return 'is-next';
            }
            if (diff === -2) {
                return 'is-far-prev';
            }
            if (diff === 2) {
                return 'is-far-next';
            }

            return 'is-hidden';
        },
    }));
});

if (!window.Alpine?.started) {
    window.Alpine = Alpine;
    requestAnimationFrame(() => Alpine.start());
}
