import Alpine from 'alpinejs';
import '../css/tailwind.css';

/**
 * Mobile navigation drawer (burger toggle).
 */
Alpine.data('stolzeNav', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    },
}));

/**
 * Time-table item: reveal the artist image at a random horizontal offset on
 * hover. The CSS shows the image
 * container on :hover at >=1024px; we only set its left offset here.
 */
Alpine.data('timetableItem', () => ({
    left: 0,
    init() {
        const w = window.innerWidth || 1200;
        this.left = Math.round(Math.random() * w * 0.75);
    },
}));

/**
 * Video section: swap the YouTube thumbnail for the autoplay embed on click.
 */
Alpine.data('videoSection', () => ({
    showVideo: false,
    load() {
        this.showVideo = true;
    },
}));

/**
 * /artists search filter + live count.
 */
Alpine.data('artistSearch', (total) => ({
    query: '',
    total,
    get count() {
        if (!this.query) return this.total;
        return Array.from(this.$root.querySelectorAll('[data-artist-name]')).filter(
            (el) => el.dataset.artistName.includes(this.query.toLowerCase())
        ).length;
    },
    matches(name) {
        return !this.query || name.includes(this.query.toLowerCase());
    },
}));

/**
 * Festival grid: regroup the flat list of cells into rows that match the
 * current column count (4 / 3 / 2 / 1 by width, capped at data-max-cols), so
 * each `.grid-row` is exactly one visual row. That lets the row's full-width
 * border-bottom draw an edge-to-edge horizontal line — mirroring the original
 * site, which did the same chunking in React.
 */
Alpine.data('festivalGrid', () => ({
    maxCols: 4,
    singleMobile: false,
    cols: 0,
    cells: [],
    init() {
        this.maxCols = parseInt(this.$el.dataset.maxCols || '4', 10);
        this.singleMobile = this.$el.dataset.singleMobile === '1';
        this.cells = Array.from(
            this.$el.querySelectorAll('.grid-item, .grid-item-credits')
        );
        // Defer the first regroup so nested Alpine bindings (e.g. the gallery
        // lightbox buttons) finish initialising before we reparent them.
        this.$nextTick(() => this.relayout());
        let raf;
        window.addEventListener('resize', () => {
            cancelAnimationFrame(raf);
            raf = requestAnimationFrame(() => this.relayout());
        });
    },
    computeCols() {
        const w = window.innerWidth;
        if (this.singleMobile && w <= 768) return 1;
        const c = w > 1024 ? 4 : w > 768 ? 3 : w > 480 ? 2 : 1;
        return Math.min(c, this.maxCols);
    },
    relayout() {
        const cols = this.computeCols();
        if (cols === this.cols) return;
        this.cols = cols;
        this.$el.querySelectorAll('.grid-row').forEach((r) => r.remove());
        for (let i = 0; i < this.cells.length; i += cols) {
            const row = document.createElement('div');
            row.className = 'grid-row';
            for (let j = i; j < i + cols && j < this.cells.length; j++) {
                row.appendChild(this.cells[j]);
            }
            this.$el.appendChild(row);
        }
    },
}));

/**
 * Gallery lightbox: open by index, navigate, close. Image URLs are read from
 * the `data-images` JSON on the root element.
 */
Alpine.data('galleryLightbox', () => ({
    images: [],
    alts: [],
    index: 0,
    open: false,
    init() {
        try {
            this.images = JSON.parse(this.$root.dataset.images || '[]');
            this.alts = JSON.parse(this.$root.dataset.alts || '[]');
        } catch (e) {
            this.images = [];
            this.alts = [];
        }
    },
    show(i) {
        this.index = i;
        this.open = true;
    },
    close() {
        this.open = false;
    },
    next() {
        this.index = (this.index + 1) % this.images.length;
    },
    prev() {
        this.index = (this.index + this.images.length - 1) % this.images.length;
    },
    onKey(e) {
        if (!this.open) return;
        if (e.key === 'Escape') this.close();
        if (e.key === 'ArrowRight') this.next();
        if (e.key === 'ArrowLeft') this.prev();
    },
    get current() {
        return this.images[this.index] || '';
    },
    get currentAlt() {
        return this.alts[this.index] || '';
    },
}));

window.Alpine = Alpine;
Alpine.start();

/**
 * Turn WooCommerce variation <select>s into clickable pills.
 *
 * The native selects stay in the DOM (hidden) because WC's variation engine
 * reads/writes them; the pills just mirror + drive them, dispatching a native
 * `change` so WC updates price / availability / the add-to-cart button.
 */
function initVariationPills() {
    document.querySelectorAll('.variations select').forEach((select) => {
        if (select.dataset.pillified) return;
        select.dataset.pillified = '1';

        const group = document.createElement('div');
        group.className = 'variation-pills';

        const build = () => {
            group.innerHTML = '';
            Array.from(select.options).forEach((opt) => {
                if (!opt.value) return; // skip the "Choose an option" entry
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'variation-pill';
                btn.textContent = opt.textContent;
                btn.dataset.value = opt.value;
                if (opt.disabled) btn.classList.add('is-disabled');
                if (select.value === opt.value) btn.classList.add('is-active');
                btn.addEventListener('click', () => {
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                group.appendChild(btn);
            });
        };

        build();
        select.insertAdjacentElement('afterend', group);

        // Keep the active pill in sync when the select changes (incl. "Clear").
        select.addEventListener('change', () => {
            group.querySelectorAll('.variation-pill').forEach((p) => {
                p.classList.toggle('is-active', p.dataset.value === select.value);
            });
        });

        // Rebuild when WC re-evaluates which options are available.
        if (window.jQuery) {
            window
                .jQuery(select)
                .closest('form.variations_form')
                .on('woocommerce_update_variation_values reset_data', () =>
                    setTimeout(build, 0)
                );
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVariationPills);
} else {
    initVariationPills();
}
// Re-run once everything (incl. WC scripts) has settled.
window.addEventListener('load', initVariationPills);
