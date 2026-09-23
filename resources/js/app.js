// Fonts zelf gehost (AVG: geen bezoekers-IP's naar Google, en sneller).
import '@fontsource-variable/inter';
import '@fontsource-variable/space-grotesk';
import '@fontsource-variable/jetbrains-mono';

import Alpine from 'alpinejs';
import carCatalog from './components/car-catalog';
import counter from './components/counter';
import gallery from './components/gallery';

window.Alpine = Alpine;

// Herbruikbare componenten (worden in Blade gebruikt via x-data="naam(...)").
Alpine.data('carCatalog', carCatalog);
Alpine.data('counter', counter);
Alpine.data('gallery', gallery);

// Gekozen onderwerp van het aanvraagformulier; de snelknoppen op de
// detailpagina zetten 'm, het formulier leest 'm.
Alpine.store('lead', { type: 'vraag' });

Alpine.start();
