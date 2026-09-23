// Aanbodpagina: filteren en sorteren gebeurt client-side (instant, zonder
// page reload). De server rendert alle kaarten; dit component toont/verbergt
// en herordent ze, en houdt de filters in de URL (deelbare links).
export default (config) => ({
    bounds: { priceMin: config.priceMin, priceMax: config.priceMax, yearMin: config.yearMin, yearMax: config.yearMax },
    search: '',
    brand: config.brand || '',
    body: config.body || '',
    fuels: config.fuel ? [config.fuel] : [],
    price: [config.priceMin, config.priceMax],
    year: [config.yearMin, config.yearMax],
    sort: 'newest',
    sortOpen: false,
    sortOptions: [
        { v: 'newest', l: 'Nieuwste eerst' },
        { v: 'price_asc', l: 'Prijs (laag naar hoog)' },
        { v: 'price_desc', l: 'Prijs (hoog naar laag)' },
        { v: 'mileage_asc', l: 'Laagste kilometerstand' },
        { v: 'year_desc', l: 'Nieuwste bouwjaar' },
    ],
    drawerOpen: false,
    count: config.total,
    cards: [],

    sortLabel() { return (this.sortOptions.find((o) => o.v === this.sort) || {}).l || 'Sorteren'; },
    setSort(v) { this.sort = v; this.sortOpen = false; this.apply(); },

    init() {
        const grid = this.$refs.grid;
        this.cards = Array.from(grid.querySelectorAll('[data-car]')).map((el) => ({
            el,
            name: el.dataset.name,
            brand: el.dataset.brand,
            body: el.dataset.body,
            fuel: el.dataset.fuel,
            price: +el.dataset.price,
            year: +el.dataset.year,
            mileage: +el.dataset.mileage,
            created: +el.dataset.created,
            featured: el.dataset.featured === '1',
        }));
        this.hydrateFromUrl();
        this.apply();
    },

    // Leest de filters uit de URL zodat een gedeelde/bookmarked link
    // dezelfde weergave herstelt. De URL is hier de bron van waarheid.
    hydrateFromUrl() {
        const p = new URLSearchParams(location.search);
        // Onzin in de URL (bv. price_min=abc) negeren i.p.v. "€ NaN" tonen.
        const clamp = (v, lo, hi, d) => Number.isFinite(v) ? Math.min(Math.max(v, lo), hi) : d;
        if (p.has('q')) this.search = p.get('q');
        if (p.has('brand')) this.brand = p.get('brand');
        if (p.has('body_type')) this.body = p.get('body_type');
        if (p.has('fuel_type')) this.fuels = p.get('fuel_type').split(',').filter(Boolean);
        if (p.has('price_min')) this.price[0] = clamp(+p.get('price_min'), this.bounds.priceMin, this.bounds.priceMax, this.price[0]);
        if (p.has('price_max')) this.price[1] = clamp(+p.get('price_max'), this.bounds.priceMin, this.bounds.priceMax, this.price[1]);
        if (p.has('year_min')) this.year[0] = clamp(+p.get('year_min'), this.bounds.yearMin, this.bounds.yearMax, this.year[0]);
        if (p.has('year_max')) this.year[1] = clamp(+p.get('year_max'), this.bounds.yearMin, this.bounds.yearMax, this.year[1]);
        if (p.has('sort')) this.sort = p.get('sort');
        // Omgedraaid bereik (min > max) rechtzetten.
        this.price.sort((a, b) => a - b);
        this.year.sort((a, b) => a - b);
    },

    // Schrijft de actieve filters terug naar de URL (zonder page reload).
    syncUrl() {
        const p = new URLSearchParams();
        if (this.search) p.set('q', this.search);
        if (this.brand) p.set('brand', this.brand);
        if (this.body) p.set('body_type', this.body);
        if (this.fuels.length) p.set('fuel_type', this.fuels.join(','));
        if (this.price[0] != this.bounds.priceMin) p.set('price_min', this.price[0]);
        if (this.price[1] != this.bounds.priceMax) p.set('price_max', this.price[1]);
        if (this.year[0] != this.bounds.yearMin) p.set('year_min', this.year[0]);
        if (this.year[1] != this.bounds.yearMax) p.set('year_max', this.year[1]);
        if (this.sort !== 'newest') p.set('sort', this.sort);
        const qs = p.toString();
        history.replaceState(null, '', qs ? `${location.pathname}?${qs}` : location.pathname);
    },

    toggleFuel(f) {
        this.fuels = this.fuels.includes(f) ? this.fuels.filter((x) => x !== f) : [...this.fuels, f];
        this.apply();
    },

    matches(c) {
        if (this.search && !c.name.includes(this.search.toLowerCase())) return false;
        if (this.brand && c.brand !== this.brand) return false;
        if (this.body && c.body !== this.body) return false;
        if (this.fuels.length && !this.fuels.includes(c.fuel)) return false;
        if (c.price < this.price[0] || c.price > this.price[1]) return false;
        if (c.year < this.year[0] || c.year > this.year[1]) return false;
        return true;
    },

    apply() {
        const grid = this.$refs.grid;
        const visible = this.cards.filter((c) => this.matches(c));

        visible.sort((a, b) => {
            switch (this.sort) {
                case 'price_asc': return a.price - b.price;
                case 'price_desc': return b.price - a.price;
                case 'mileage_asc': return a.mileage - b.mileage;
                case 'year_desc': return b.year - a.year;
                default: return (b.featured - a.featured) || (b.created - a.created);
            }
        });

        this.count = visible.length;

        // Verbergen via inline display (wint van de flex-class op de kaart).
        this.cards.forEach((c) => { c.el.style.display = 'none'; c.el.classList.remove('cf-in'); });
        void grid.offsetWidth; // reflow -> herstart animatie
        visible.forEach((c, i) => {
            c.el.style.display = '';
            c.el.style.animationDelay = Math.min(i * 25, 300) + 'ms';
            c.el.classList.add('cf-in');
            grid.appendChild(c.el); // herordenen volgens sortering
        });

        this.syncUrl();
    },

    reset() {
        this.search = '';
        this.brand = '';
        this.body = '';
        this.fuels = [];
        this.price = [this.bounds.priceMin, this.bounds.priceMax];
        this.year = [this.bounds.yearMin, this.bounds.yearMax];
        this.sort = 'newest';
        this.apply();
    },

    removeChip(k) {
        if (k === 'search') this.search = '';
        else if (k === 'brand') this.brand = '';
        else if (k === 'body') this.body = '';
        else if (k === 'price') this.price = [this.bounds.priceMin, this.bounds.priceMax];
        else if (k === 'year') this.year = [this.bounds.yearMin, this.bounds.yearMax];
        else if (k.startsWith('fuel:')) this.fuels = this.fuels.filter((f) => f !== k.slice(5));
        this.apply();
    },

    fmt(n) { return Number(n).toLocaleString('nl-NL'); },

    get activeCount() {
        let n = 0;
        if (this.search) n++;
        if (this.brand) n++;
        if (this.body) n++;
        n += this.fuels.length;
        if (this.price[0] != this.bounds.priceMin || this.price[1] != this.bounds.priceMax) n++;
        if (this.year[0] != this.bounds.yearMin || this.year[1] != this.bounds.yearMax) n++;
        return n;
    },

    get hasActiveFilters() { return this.activeCount > 0; },

    get chips() {
        const c = [];
        if (this.search) c.push({ k: 'search', label: '"' + this.search + '"' });
        if (this.brand) c.push({ k: 'brand', label: this.brand });
        if (this.body) c.push({ k: 'body', label: this.body });
        this.fuels.forEach((f) => c.push({ k: 'fuel:' + f, label: f }));
        if (this.price[0] != this.bounds.priceMin || this.price[1] != this.bounds.priceMax)
            c.push({ k: 'price', label: '€ ' + this.fmt(this.price[0]) + ' – € ' + this.fmt(this.price[1]) });
        if (this.year[0] != this.bounds.yearMin || this.year[1] != this.bounds.yearMax)
            c.push({ k: 'year', label: this.year[0] + ' – ' + this.year[1] });
        return c;
    },
});
