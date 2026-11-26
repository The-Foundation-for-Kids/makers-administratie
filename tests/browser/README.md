# Browsertests

Playwright-tests die de site in een echte browser doorklikken, per rol.

## Wanneer ze draaien

Bij elke push naar `prod`, `test` of `sendy` (zie `.github/workflows/main.yml`):

1. **`browser-tests`**: GitHub start een eigen kopie van de site met een lege
   MySQL-database, gevuld met `BrowserTestSeeder`. Alle tests draaien daartegen.
   **Faalt er een test, dan wordt er niet gedeployd.**
2. **`build`**: de bestaande deploy, alleen als stap 1 geslaagd is.
3. **`test-site-check`**: alleen na een deploy naar `test`. Draait de tests met
   `@alleen-lezen` tegen de test-site zelf. Deze wijzigen niets.

Bij een mislukte run staat onder de run in GitHub een download
`browsertests-rapport` met screenshots, video's en een opname (trace) per
mislukte test. Een trace bekijk je met `npx playwright show-trace <bestand.zip>`.

## Wat er getest wordt

| Bestand | Wat |
|---|---|
| `toegang.spec.ts` | Menu en pagina's per rol, verboden pagina's (403), verkeerd wachtwoord, SPA-navigatie |
| `maakster.spec.ts` | Oppakken via knop en detailpagina, Klaar, Verzonden, archief; twee maaksters tegelijk op dezelfde aanvraag |
| `beheer.spec.ts` | Bulkactie Opgepakt => Verzonden, statuswissel van/naar Afgehandeld, venster bij een instantie, inzamelpunt Ontvangen, export Instanties, import uit Excel |

`auth.setup.ts` logt vooraf één keer per rol in via het inlogformulier en
bewaart de sessie; dat test meteen het inloggen. Filament staat 5
inlogpogingen per minuut per adres toe. Wordt een test daardoor tijdelijk
geblokkeerd, dan wacht hij een minuut en probeert het opnieuw (zie
`probeerInloggen` in `helpers.ts`).

Filament-meldingen verdwijnen na een paar seconden. `helpers.ts` laat de
browser elke melding onthouden, zodat `verwachtMelding` hem ook vindt als hij
alweer weg is. Gebruik in nieuwe tests altijd `test` uit `helpers.ts`, en
`kies()` voor keuzelijsten die direct opslaan.

## Testaccounts voor de test-site

Maak op de test-site gewone accounts aan voor de rollen die je wilt
controleren, en zet ze in GitHub onder *Settings → Secrets and variables →
Actions*:

| Secret | Rol |
|---|---|
| `TEST_ADMIN_EMAIL` / `TEST_ADMIN_PASSWORD` | super_admin |
| `TEST_MAAKSTER1_EMAIL` / `TEST_MAAKSTER1_PASSWORD` | Maakster |
| `TEST_INZAMELPUNT_EMAIL` / `TEST_INZAMELPUNT_PASSWORD` | Verzamelpunt |

Ontbreekt een account, dan worden de tests voor die rol overgeslagen. Een ander
adres dan `https://kledingmakentest.thefoundationforkids.com` stel je in met de
variabele `TEST_SITE_URL`.

## Lokaal draaien (Windows/Herd)

Eenmalig:

```bash
npm install
npx playwright install chromium
```

Testdatabase opbouwen (elke keer opnieuw vóór een volledige run, want de tests
wijzigen gegevens). Je gewone database wordt niet aangeraakt:

```bash
DB_DATABASE=makers_browsertest php artisan migrate:fresh --force --seed --seeder=BrowserTestSeeder
```

De database `makers_browsertest` moet bestaan. Heb je de config gecachet, draai
dan eerst `php artisan config:clear`.

App starten op poort 8123 (in een apart venster, vanuit de map `public`):

```bash
cd public
DB_DATABASE=makers_browsertest APP_URL=http://127.0.0.1:8123 CACHE_STORE=file DEBUGBAR_ENABLED=false MAIL_MAILER=log \
  php -S 127.0.0.1:8123 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Tests draaien:

```bash
npm run test:browser                  # alles, tegen http://127.0.0.1:8123
npx playwright test maakster          # één bestand
npx playwright test --ui              # met een venster waarin je meekijkt
```

Tegen de test-site (alleen-lezen):

```bash
BASE_URL=https://kledingmakentest.thefoundationforkids.com \
TEST_MAAKSTER1_EMAIL=... TEST_MAAKSTER1_PASSWORD=... \
npm run test:browser:test-site
```

Gebruik altijd `--project=ci` of `--project=test-site` (de npm-scripts doen dat
al); zonder project draait Playwright beide.
