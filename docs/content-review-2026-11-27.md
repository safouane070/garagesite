# Vormgeving en tekst — 27 november 2026

Op verzoek nagekeken op generieke verkooppraat, decoratieve vinkjes, herhaling en
de mobiele uitsnede van de homepagefoto. Beoordeeld met de frontend-design-skill.

## Aangepast

- Homepage: volledige breedte van de showroomfoto, vaste verhouding 16:10 op mobiel,
  fade onder de auto's. De drie vinkjes zijn vervangen door één zin.
- Homepage: diensten omschreven met de handeling die een bezoeker wil uitvoeren.
- Over ons: algemene teksten over passie, vriendschap en uitzonderlijke service
  vervangen door informatie over voorraad, bezoek en inruil. Vier herhaalde
  voordelen met icooncirkels vervangen door adres, openingstijden en telefoonnummer.
- Diensten: termen als ‘ontzorgen’, ‘droomauto’ en ‘zonder gedoe’ vervangen door
  concrete uitleg over verkopen, kopen en benodigde gegevens.
- Volkswagen-specialist: minder algemene kwaliteitsclaims; uitleg verwijst naar
  onderhoudshistorie, uitvoering en de beschikbare merkfilters.
- Financial lease: decoratieve vinkjescirkels verwijderd. Rekenvoorbeeld,
  voorwaarden, FAQ en aanvraagmogelijkheden behouden.

## Bekeken en behouden

Aanbod, contact, privacybeleid en algemene voorwaarden zijn visueel gecontroleerd.
Echte reviews blijven ongewijzigd. Functionele iconen, merktypografie en
actieknoppen blijven staan. Deze ronde is geen juridische of financiële controle.

## Verificatie

Playwright: negen pagina's op 320 en 1440 px in Chromium, plus de vier gewijzigde
hoofdpagina's op 390 px in WebKit. Alle 22 weergaven laden met HTTP 200, zonder
horizontale uitloop of JavaScript-fouten. De link naar de inkoopaanvraag blijft
werken. Homepagefoto apart bekeken op 320, 390, 530 en 1440 px.

Productiebuild geslaagd. Bestaande homepageassertie bijgewerkt naar de nieuwe
doorlopende zin; de andere regressiecontroles blijven behouden.
