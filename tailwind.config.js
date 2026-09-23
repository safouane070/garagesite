import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Ook PHP-bestanden scannen: onze CarStatus-enum bevat Tailwind-klassen.
        './app/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                // Luxe donker: warm antraciet als canvas (geen plat zwart),
                // oplopende warm-donkere oppervlaklagen erboven.
                ink: '#15130F',        // paginacanvas
                paper: '#1C1A15',      // kaart/paneel
                graphite: {
                    DEFAULT: '#1B1915',
                    800: '#1B1915',    // subtiel oppervlak boven de grond
                    700: '#221F19',    // kaart
                    600: '#2C2920',    // rand/verdeler op donker
                    500: '#3A3629',    // tracks / gedempte lijnen
                },
                // Warm off-white als leestekst op donker.
                cream: '#F2EEE4',
                // Eén vergrendeld accent: het merkrood van Autobedrijf Rijswijk.
                brass: {
                    300: '#F26178',    // lichter rood (accent-tekst / hover op donker)
                    400: '#E63E58',
                    500: '#D90429',    // primair merkrood
                    600: '#AE0320',
                    700: '#820218',
                },
                // Nog donkere "eilanden" boven foto's: scrim + lichte tekst erop.
                scrim: '#0C0B08',
                onscrim: '#F2EEE4',
                // Fijne verdeler op donker (lichte alpha).
                hairline: 'rgba(242,238,228,0.12)',
            },
            fontFamily: {
                // Display: koppen, prijzen, het merkembleem.
                display: ['"Space Grotesk Variable"', '"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                // UI/lopende tekst.
                sans: ['"Inter Variable"', 'Inter', ...defaultTheme.fontFamily.sans],
                // Mono: technische spec-labels — geeft het "spec sheet"-karakter.
                mono: ['"JetBrains Mono Variable"', '"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: {
                // Diepe kaartschaduw, afgestemd op een donker canvas.
                card: '0 1px 2px rgba(0,0,0,0.45), 0 20px 44px -22px rgba(0,0,0,0.75)',
                glow: '0 0 0 1px rgba(217,4,41,0.35), 0 12px 34px -10px rgba(217,4,41,0.30)',
            },
            maxWidth: {
                container: '80rem',
            },
            transitionTimingFunction: {
                premium: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },

    plugins: [forms],
};
