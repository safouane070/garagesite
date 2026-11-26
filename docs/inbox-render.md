# Aanvragen-inbox op Render

Aanvragen worden altijd opgeslagen en verschijnen achter login op `/admin/aanvragen`.
`BRAND_DEMO=true` schakelt de opslag niet meer uit. Deze instelling voorkomt alleen
automatische klantbevestigingen uit naam van de echte zaak en zet het standaardadres
voor meldingen uit.

In de inbox kan de beheerder het **E-mailadres voor meldingen** opslaan of leegmaken.
Dit verandert het openbare contactadres niet. Zonder verzendende maildienst blijven
de meldingen uit; de inbox toont dat expliciet. `log` en `array` versturen geen mails
en krijgen ook geen persoonsgegevens van aanvragen aangeboden.

## Gratis Render-opstelling

- Webservice: `garagesite-demo`, Free.
- Database: `garagesite-inbox`, Free Postgres, Oregon.
- Aangemaakt op 26 november 2026. Render vermeldt vervaldatum **26 december 2026**.
- Geen betaalde compute, opslag of automatische upgrade ingesteld.
- Render Free Postgres verloopt na 30 dagen. Exporteer de database vóór de vervaldatum
  als de aanvragen bewaard moeten blijven. Zonder handmatige upgrade verwijdert Render hem.

De webservice gebruikt `DB_CONNECTION=pgsql`, `DB_URL` (interne Render-URL),
`DB_SSLMODE=require` en een vaste `APP_KEY`. Geheimen staan alleen in Render,
niet in deze repository.

`docker/start.sh` voert migraties uit op de blijvende database. Daarna koppelt
`demo:restore-catalog` de in de image gebouwde voorraad en galerijen. Bestaande
auto-ID's, aanvragen, beheerders en inboxinstellingen worden behouden.
Nieuwe auto's worden op `dealer_slug` herkend, zodat een andere volgorde in de
build bestaande aanvragen niet aan een andere auto koppelt.

De fotobestanden staan nog in de tijdelijke container. De galerijen uit de build
worden bij elke deploy opnieuw gekoppeld. Handmatig na de build geüploade foto's
hebben zonder externe bestandsopslag geen blijvende opslag op Render Free.

## E-mail later activeren

Naast een ontvanger vereist externe e-mail een werkende mailprovider en toegestane
afzender (`MAIL_MAILER`, providergegevens en `MAIL_FROM_ADDRESS`). Render Free blokkeert
SMTP-poorten 25, 465 en 587. Gebruik voor deze opstelling een mailprovider met HTTP-API
en de bijbehorende Laravel-transportconfiguratie. Het invullen van een ontvanger
alleen activeert nog geen maildienst. Verifieer de bezorging met een echte testaanvraag
voordat e-mail als werkend wordt beschouwd.

Officiële beperkingen: https://render.com/docs/free

## Controle

- Aanvraagtests controleren opslag in de demo, de beheerinbox, ontvangerkeuze,
  uitschakelen van meldingen, validatie en toegangscontrole.
- De redeploytest koppelt een voorraad tweemaal, met afwijkende auto-ID's in de
  build. Bestaande aanvragen, prijzen en instellingen blijven behouden.
- Browserproef gebruikt een aparte testdatabase en fictieve `example.com`-adressen.
  Er worden geen echte e-mails verstuurd.
