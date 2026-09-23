// Telt een cijfer op zodra het in beeld komt. Het echte getal staat al in de
// HTML (zonder JS / voor zoekmachines); bij "verminderde beweging" geen animatie.
export default (target) => ({
    target,
    display: target.toLocaleString('nl-NL'),
    done: false,
    observe() {
        // Geen animatie bij "verminderde beweging": het echte getal blijft staan.
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        this.display = '0';
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting && !this.done) {
                    this.done = true;
                    this.run();
                    io.disconnect();
                }
            });
        }, { threshold: 0.4 });
        io.observe(this.$el);
    },
    run() {
        const dur = 1200, start = performance.now();
        const tick = (now) => {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            this.display = Math.round(this.target * eased).toLocaleString('nl-NL');
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    },
});
