// Fotogalerij op de detailpagina, met schermvullende weergave (lightbox).
// Pijltjes, vegen en Escape werken in beide weergaven. De native dialoog maakt
// de hele achtergrond inert, inclusief WhatsApp en de skiplink.

export default (config) => ({
    images: config.images,
    srcsets: config.srcsets ?? [],
    i: 0,
    full: false,
    touchX: null,

    // Actieve miniatuur in beeld houden (alleen horizontaal: de pagina zelf mag niet verspringen).
    init() {
        this.$watch('i', (n) => {
            const strip = this.$refs.thumbs;
            const thumb = strip?.children[n];
            if (thumb) strip.scrollTo({ left: thumb.offsetLeft - (strip.clientWidth - thumb.clientWidth) / 2, behavior: 'smooth' });
        });
    },

    get current() { return this.images[this.i]; },
    next() { if (this.images.length > 1) this.i = (this.i + 1) % this.images.length; },
    prev() { if (this.images.length > 1) this.i = (this.i - 1 + this.images.length) % this.images.length; },
    go(n) { this.i = n; },

    touchStart(e) { this.touchX = e.changedTouches[0].clientX; },
    touchEnd(e) {
        if (this.touchX === null) return;
        const dx = e.changedTouches[0].clientX - this.touchX;
        if (Math.abs(dx) > 40) { dx < 0 ? this.next() : this.prev(); }
        this.touchX = null;
    },

    open() {
        if (!this.images.length) return;
        this.full = true;
        document.documentElement.classList.add('overflow-hidden');
        this.$nextTick(() => {
            this.$refs.lightbox.showModal();
            this.$refs.closeFull.focus();
        });
    },

    close() {
        if (!this.full) return;
        this.full = false;
        document.documentElement.classList.remove('overflow-hidden');
        this.$refs.lightbox.close();
        // Safari focust een aangeklikte knop niet altijd. Keer expliciet terug
        // naar de galerijknop, ook wanneer de foto zelf de dialoog opende.
        this.$refs.openFull.focus({ preventScroll: true });
    },
});
