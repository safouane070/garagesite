# Sitebeoordeling — 25 november 2026

**Status na uitvoering:** de hieronder beoordeelde fouten en de gevraagde visuele verbeteringen zijn lokaal aangepakt. Zie de nacontrole onderaan. De oorspronkelijke beoordeling blijft als historie staan. Er is nog niets van deze ronde gepusht of gedeployd.

Mijn oordeel: een sterke basis, ongeveer 8/10 voor uitstraling. De donkere kleuren, rode accenten, eigen fotografie, consistente typografie en duidelijke autoprijzen passen goed bij dit autobedrijf. Vooral de autodetailpagina voelt verzorgd. De grootste winst zit nu in een paar functionele fouten, duidelijkere routes naar een aanvraag en meer persoonlijk vertrouwen.

## Wat daadwerkelijk is bekeken

- De nieuwste lokale werkversie, inclusief het verwijderen van de verkoopteller en de zichtbare demo-teksten. Die wijzigingen staan nog niet op Render.
- Negen publieke pagina's op 1440×900, 768×1024, 390×844 en 320×740: **36 pagina/formaatcontroles**. Alle pagina's gaven HTTP 200, zonder horizontale pagina-overflow.
- Homepage, aanbod, diensten, financial lease, over ons, Volkswagen-specialist, contact, privacybeleid en algemene voorwaarden.
- Klikroutes, zoeken, nul resultaten, filters combineren/wissen, 24→48 auto's laden, vier sorteermogelijkheden, prijs- en bouwjaarsliders met het toetsenbord en filterbehoud na herladen.
- Mobiel menu, filterpaneel, galerijminiaturen, laatste foto, vergroting, pijltjestoetsen, Escape, proefritselectie en het verbergen van het datumveld bij een algemene vraag. Galerijcontroles herhaald met expliciete wachttijden voor geladen foto's en afgelopen animaties.
- Aanvullende filtercontroles op 360×640, 375×667, 390×844, 430×932 en 844×390.
- Contactanker, juiste voorselectie vanuit de leasepagina, lege-formuliervalidatie, FAQ en het laden van Google Maps na een klik.
- De drie PDF-links: HTTP 200 en application/pdf. De inhoud is niet juridisch beoordeeld.
- Bestaande ingelogde beheersessie: voorraad, aanvragenoverzicht, nieuw-autoformulier en hulp bekeken. Geen auto's, aanvragen of accountinstellingen gewijzigd.
- Op Render de CUPRA-fout afzonderlijk gereproduceerd, de lease-aanbodlink en de uitgelogde login gecontroleerd. De indexeringsblokkade is bevestigd: `X-Robots-Tag: noindex, nofollow` en `robots.txt` met `Disallow: /`.

De lokale voorraad bevatte 102 aangeboden auto's; Render bevatte 143. Dat zijn verschillende databases, geen bewijs van een filterfout op zichzelf. De enige consolefout in de brede paginaronde hoorde bij de bewust bezochte 404-route; geen JavaScript-exceptions geregistreerd.

## Eerst oplossen

### 1. CUPRA-link verbergt de aanwezige voorraad

**Lokaal én live bevestigd.** Op de Volkswagen-specialistpagina stuurt `CUPRA Bekijken` naar `?brand=CUPRA`. Het aanbod gebruikt de merkwaarde `Cupra`. De link geeft nul resultaten, terwijl de dropdown lokaal 11 en live 12 Cupra's toont.

Advies: normaliseer het merkfilter onafhankelijk van hoofdletters en laat merklinks uit dezelfde merkgegevens ontstaan als de dropdown. Alleen de zichtbare merknaam mag als CUPRA worden geschreven.

Bron: `resources/views/pages/vw-specialist.blade.php`, merkenlijst vanaf regel 34.

Bewijs: [live fout](../.unlazy/review/nov25-audit/live-cupra-bug.png).

### 2. Filter toepassen werkt niet op 320 px

**Herhaald op 320×740.** Open Filters, zoek Porsche en klik op `Toon 1 resultaten`. De knop raakt overlapt door de inhoud/onderkant van het paneel. Playwright kan hem niet aanklikken doordat andere elementen de klik opvangen. Er is dus een echt bedieningsprobleem, hoewel de pagina zelf geen horizontale overflow heeft.

Op 360×640, 375×667, 390×844, 430×932 en 844×390 werkt dezelfde route wel.

Advies: geef de kop en knoppenbalk een vaste ruimte binnen het paneel, laat uitsluitend de filterinhoud scrollen en zorg dat de knoppen op 320 px binnen de beschikbare breedte blijven passen. Controleer `min-height: 0` voor het scrollgebied en voorkom dat de knoppenbalk krimpt.

Bron: `resources/views/cars/index.blade.php`, mobiele filterdrawer.

Bewijs: [filterpaneel op 320 px](../.unlazy/review/nov25-audit/320-filter-before-apply.png).

### 3. Toegankelijkheid van panelen afronden

- Het sluitkruisje van het filterpaneel heeft geen tekst of `aria-label`. Geef het bijvoorbeeld de naam `Filters sluiten`.
- In de vergrote galerij kan de desktop-tabfocus naar de WhatsApp-link en de skiplink buiten het venster gaan. Escape werkt wel. De huidige `inert`-instelling omvat header, main en footer, maar niet alle overige focusbare elementen. Voeg een sluitende focuslus toe of maak de volledige achtergrond inert.

Bronnen: `resources/views/cars/index.blade.php` en `resources/js/components/gallery.js`.

## Wat ik aan de ervaring zou verbeteren

### Geef elke dienst een eigen aanvraagroute

Op de homepage gaan Inkoop, Verkoop, Aankoop en Zoekopdracht allemaal naar dezelfde dienstenpagina. Daar volgt vooral een zoekopdrachtformulier. Wie zijn eigen auto wil verkopen, moet zelf uitzoeken hoe hij verdergaat.

Maak concrete knoppen, zoals `Verkoop je auto` en `Bespreek aankoopbegeleiding`, met een passend voorgeselecteerd onderwerp en relevante vragen. Verkoop van een occasion kan direct naar het aanbod leiden.

### Laat uitvoeringen op mobiel beter vergelijken

De compacte mobiele autolijst is een goede keuze. Wel worden namen en uitvoeringen vaak na één regel afgekapt. Daardoor lijken verschillende Q3's, Golfs of Formentors te veel op elkaar. Geef de uitvoering twee regels, vooral wanneer juist S-Line, motorisering of een uitvoering het verschil maakt.

### Maak Over ons persoonlijker

De kantoor- en specialistfoto's voelen eigen en geloofwaardig, maar ik mis gezichten en namen. Een echte foto van de mensen achter het bedrijf, hun rol en hoe een bezichtiging verloopt kan meer vertrouwen geven dan nog een algemene alinea over passie en betrouwbaarheid.

### Versterk de navigatie boven de homepagefoto

De lichte, relatief kleine navigatietekst valt op delen van de hero weg tegen de foto. Een subtiele donkere bovenlaag of stevigere headerachtergrond verbetert de leesbaarheid. De hoofdknop en grote titel zijn duidelijker.

### Breng desktopbezoekers sneller bij de auto's

De aanbodpagina gebruikt veel hoogte voor de titel, toelichting, telling en witruimte voordat de auto's beginnen. De mobiele variant is compacter. Een kortere desktopintro brengt meer voorraad direct in beeld.

### Maak de lease-aanbodlink nauwkeurig

`Alle auto's met leaseprijs` opent het gewone aanbod, inclusief auto's zonder maandbedrag. Kies `Bekijk het volledige aanbod` of voeg een echt filter voor auto's met een lease-indicatie toe.

## Formulieren en grenzen van deze review

De formulierbediening, voorselecties en lege-formuliervalidatie zijn bekeken. Er zijn geen geldige aanvragen verstuurd en geen e-mails naar het bedrijf verzonden.

Render gebruikt nog `BRAND_DEMO=true`. In de gecontroleerde code geeft `LeadController` dan een ontvangstbevestiging terug zonder de aanvraag op te slaan of te mailen. Het verwijderen van de demobalk verandert dat niet. Voor werkelijk gebruik door klanten moet de ontvangst van aanvragen daarom apart worden ingericht en van begin tot eind gecontroleerd. Een zichtbare succesmelding bewijst geen aflevering.

Deze ronde is geen beveiligingsaudit, juridische controle, financieringscontrole of volledige test van beheerwijzigingen. Er is geen auditfix gedeployd. De eerder gevraagde verwijderingen staan nog lokaal.

## Volgorde die ik zou kiezen

1. CUPRA-link en de filterknop op 320 px herstellen.
2. Toegankelijke sluitknop en focusgedrag van de galerij afronden.
3. Aanvraagroutes per dienst verbeteren; aflevergedrag van formulieren expliciet regelen voor werkelijk klantgebruik.
4. Uitvoeringen op mobiel leesbaarder maken.
5. Echte teamfoto/namen, leesbaardere heronavigatie en compactere desktopintro toevoegen.

De huidige visuele richting kan blijven. De site heeft vooral gerichte afwerking nodig.

Ruwe meetgegevens en screenshots: `.unlazy/review/nov25-audit/`.

## Nacontrole van de gevraagde fixes

| Acceptatiepunt | Uitvoering | Bewijs |
|---|---|---|
| CUPRA-link | Het cataloguscomponent herstelt de merkwaarde onafhankelijk van hoofdletters naar de voorraadwaarde. Dropdown en URL gebruiken daarna dezelfde waarde. | Klik op de bestaande specialistlink geeft lokaal 11 Cupra's, in Chromium en WebKit. |
| Filterknop op 320 px | De filter-/sorteerbalk rekt de viewport niet meer op zodra de filterteller verschijnt. Kop en knoppenbalk blijven vast; alleen de filterinhoud scrolt. | Viewport blijft 320×740, resultaatknop staat op y=680 met hoogte 44 en is aanklikbaar. Ook op 390 en 768 px getest. |
| Benoemde sluitknop | Het kruisje heet voor hulpsoftware nu `Filters sluiten`. | Playwright sluit het paneel via die toegankelijke naam. |
| Galerijfocus | Een native `<dialog>` vervangt handmatige gedeeltelijke achtergrondblokkade. Sluiten focust expliciet de galerijknop, ook in Safari. | Tab/Shift+Tab bereiken geen achtergrondlinks; pijltjes, Escape, sluitknop en focusterugkeer gecontroleerd. |
| Gerichte diensten | Inkoop, aankoop en zoekopdracht krijgen een eigen voorselectie en passende formulieruitleg. Verkoop opent het aanbod. | Klikroutes op vier schermformaten; integratietest bewijst opslag met het juiste onderwerp en ingeplande mail via een mailfake. |
| Mobiele uitvoeringen | Uitvoeringen krijgen twee regels; merk/model mag mobiel doorlopen. | Rendercontrole en screenshots op 320 en 390 px. |
| Leesbare heronavigatie | Donkere headerachtergrond met 90% dekking en lichtere navigatietekst. | Berekende achtergrond `rgba(21, 19, 15, 0.9)` en desktopscreenshot. |
| Compactere aanbodkop | Minder verticale ruimte en een bredere, kortere toelichting. | Eerste autokaart begint op y=400 in de 1440×900-weergave. |
| Teamfoto en namen | Geen geverifieerde namen met bijbehorende teamfoto gevonden op de oorspronkelijke Over-ons-pagina. | Op expliciet verzoek achterwege gelaten; niets verzonnen. |

Gekozen aanpak: bestaande componenten hergebruiken en de native browserdialoog toepassen. Er zijn geen nieuwe pakketten of databasemigraties nodig.

Validatie: 232 PHP-tests geslaagd, 828 assertions. Productiebuild geslaagd. Playwright gebruikt Chromium en WebKit op 320×740, 390×844, 768×1024 en 1440×900; gerichte hercontroles na wijzigingen. De volledige WebKit-ronde bevat 32 benoemde controles. De dienstaanvragen zijn in de testdatabase met afgeschermde mailafhandeling getest; geen echte klantaanvragen of e-mails verstuurd.

Nieuwe regressiecontrole: `python scripts/review-check.py` tegen een draaiende lokale server met Cupra- en Porsche-voorraad. De bestaande live-check is aangepast aan de native galerij.

Screenshots van de uiteindelijke vormgeving staan in `.unlazy/review/final-fixes/`. De eerdere verwijderingen van de verkoopteller en demo-teksten zijn behouden. De aanvraagafhandeling op Render en de indexeringsblokkade zijn niet gewijzigd.

Checklist: 9/9 acceptatiepunten afgehandeld, waarvan het teamonderdeel vervalt op verzoek. Geen onafgehandelde implementatiepunten. Publicatie valt buiten deze lokale wijzigingsronde.
