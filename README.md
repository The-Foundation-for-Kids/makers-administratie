
## Kledingmaken administratie

Interne webapplicatie voor het beheren van kledingaanvragen voor kinderen: operatoren voeren aanvragen in, maaksters maken de kleding op maat, en verzamelaars verzorgen de logistiek. De applicatie is volledig Nederlandstalig en draait op Laravel met een Filament-beheerpaneel en een MySQL-database.

## Functionaliteit in het kort

- Aanvragen (kledingwensen) vastgekoppeld aan een instelling ("agency") met een automatisch gegenereerde aanvraagcode.
- Workflow per status: Ingevoerd → Aangeboden → Opgepakt → Klaar → Verzonden → Ontvangen → Afgehandeld.
- Rolgebaseerde weergaven voor operatoren, maaksters en verzamelaars binnen één Filament-paneel.
- Uitnodigingen per e-mail met een openbare registratieroute (signed URL).
- Import en export van XLSX-bestanden, maandelijkse instellingsrapportage en activiteitenlog.

## Technische versies

| Onderdeel | Versie |
| --- | --- |
| PHP (composer.json) | `^8.1` |
| PHP (deployment workflow) | 8.4 |
| Laravel | 12 (`v12.0.0` in composer.lock) |
| Filament | `~4.0` (`v4.7.1` in composer.lock) |
| Laravel Sanctum | 4.3.1 |
| Spatie Permission | 6.24.1 |
| Spatie Activitylog | 4.x |
| Maatwebsite Excel | 3.1.67 |
| Rappasoft Authentication Log | 6.0.1 |
| Laravel Archivable | 1.12.0 |
| Vite / Tailwind CSS / Axios | 7 / 4 / 1 |

## Architectuur

De applicatie volgt de standaard Laravel-structuur. Er zijn vrijwel geen eigen routes of controllers: de bedrijfslogica zit in Filament Resource-klassen (die tabellen, formulieren en acties genereren) en in Eloquent model hooks. De centrale tabel is `clothing`, met één rij per aanvraag. Workflow-klassen zoals `Offer`, `Creator` en `Collector` zijn geen aparte tabellen maar Eloquent-subklassen van `Clothing` die dezelfde tabel filteren op status of toegewezen gebruiker. Zo krijgt elke rol een eigen scherm op dezelfde dataset.

## Lokaal draaien

1. Vereisten: PHP 8.2+ met de extensies die Laravel, Filament en PhpSpreadsheet nodig hebben, Composer, Node.js en een lokale MySQL-database.
2. `composer install && npm ci`
3. Kopieer `.env.example` naar `.env`, stel een lokale database en `APP_URL` in, en genereer een app-sleutel met `php artisan key:generate`.
4. Draai migraties alleen tegen een lokale, wegwerp-database: `php artisan migrate`.
5. Start de applicatie met `php artisan serve` en de front-end met `npm run dev`.
