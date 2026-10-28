# TFFK repository audit

Audit date: 2026-10-16  
Scope: read-only review of the checked out `main` worktree and locally available refs `origin/main`, `origin/prod`, `origin/test`, `origin/sendy`. This review did not inspect the separate WordPress installation, production environment, runtime database, or remote server.

## Executive summary

The repository contains a Laravel 12 application with a Filament 4 administrative panel for managing clothing requests, agencies, makers, collectors, user accounts, invitations, and yearly archives. Composer pins the Laravel framework to 12.0.0 in `composer.lock`; Filament resolves to 4.7.1. The codebase has a small API surface (only Sanctum's authenticated `/api/user` example route) and no repository evidence of a WordPress integration.

The main risks are inconsistent authorization, weak invitation-token generation, permissive import validation and deployment-time migrations without a visible test gate. `User::isAdmin()` unconditionally returns true (`app/Models/User.php:74-82`), but its only visible caller currently returns an array whose only privileged action is commented out (`app/Filament/Resources/Clothing/Pages/EditClothing.php:24-33`); this helper alone does not establish an active privilege escalation.

## Repository and framework inventory

### Verified facts

- Current checkout: `main` at `c488871` (`origin/main`); initial `git status` was clean.
- Available branch refs are remote-tracking refs only; there are no local `prod`, `test`, or `sendy` branches. The audit did not switch branches.
- PHP requirement is `^8.1` (`composer.json:7`); deployment workflow configures PHP 8.4 (`.github/workflows/main.yml:38-42`). Actual production PHP runtime was not inspected.
- `laravel/framework` constraint is `12`; lock resolves `v12.0.0` (`composer.json:21`, `composer.lock` package entry).
- `filament/filament` constraint is `~4.0`; lock resolves `v4.7.1` (`composer.json:12`, `composer.lock:1581-1582`). Shield resolves 4.x; Sanctum resolves 4.3.1; PHPUnit is constrained to 10.x.
- Major supporting packages include Spatie Permission 6.24.1, Spatie Activitylog 4.x, Maatwebsite Excel 3.1.67, Filament Excel, Laravel Sanctum 4.3.1, Rappasoft Authentication Log 6.0.1, and Laravel Archivable 1.12.0.
- Front-end build tooling is Vite 7, Tailwind CSS 4 and Axios 1 (`package.json`).
- `README.md` is a one-line Dutch description; no fuller user/developer documentation was found in the repository. No `docs/` tree existed before this audit.
- Application is organized as a conventional Laravel app with Filament resources/pages, Eloquent models, policies, imports, one mail class, and Laravel migrations. No separate domain/service/action layer or application job/command classes were found.

### Branch inventory and comparison

All branch observations below refer to local remote-tracking refs; they are not proof of current server deployment state.

| Ref | Tip | Tip date | Relationship/evidence | Interpretation |
| --- | --- | --- | --- | --- |
| `origin/main` | `c488871` | 2026-05-08 | Current checkout and default remote HEAD | Current development/integration tip by Git metadata; active status is inferred from latest timestamp. |
| `origin/prod` | `5d76566` | 2026-04-30 | Diverged from main: 9 commits unique to prod, 1 unique to main; `test` is an ancestor of prod | Deployment branch by workflow mapping; tree differs from main only in workflow. |
| `origin/test` | `2eb7f25` | 2026-04-30 | Ancestor of prod; 4 commits unique to prod; 1 commit unique to main | Deployment branch by workflow mapping; tree differs from main only in workflow. |
| `origin/sendy` | `781211e` | 2026-05-08 | Contains main; 2 commits unique to sendy; tree differs from main only in workflow | Deployment target associated with a Sendy-named directory; the purpose of the application instance is unclear. |

`prod` and `test` differ in history but have the same tree. `main` and `sendy` have the same application tree, with workflow-only changes. `main` and `prod` are divergent; branch names do not establish promotion order. This is evidence of deployment workflow configuration, not proof of a live target or deployed commit. `origin/main` latest activity is May 8, 2026; the check is October 16, 2026, so repository activity appears stale relative to this audit date. Remote refs were not fetched during this read-only audit, so commits created after the locally available May 8 refs are unknown.

### Documentation and conventions

- Composer scripts run package discovery and `filament:upgrade` after autoload dump, and publish Laravel assets after update (`composer.json:48-62`).
- PHPUnit discovers `tests/Unit` and `tests/Feature`; test DB configuration is commented out (`phpunit.xml:7-30`).
- CI/deployment is a single GitHub Actions workflow (`.github/workflows/main.yml`), triggered by pushes to `prod`, `test`, and `sendy`; it does not run tests or build assets before deploying.
- Laravel config uses environment variables with framework defaults. `.env` is not included in this audit and was not read.

## Architecture and functional outline

The default Filament panel uses the root path and discovers resources under `app/Filament/Resources` and pages under `app/Filament/Pages` (`app/Providers/Filament/AdminPanelProvider.php:30-65`). The panel enables login, profile editing, password reset, Filament Shield and session/CSRF middleware. `User::canAccessPanel()` permits active users (`app/Models/User.php:66-72`). The main business entity is `Clothing`, stored in a single `clothing` table. Several role-oriented models (`Creator`, `Collector`, `Offer`, `Completed`, `Confirm`, `Management`, `CreatorHistory`) inherit from `Clothing` and point to the same table.

The principal workflow tracks clothing requests through statuses from `Ingevoerd` to `Afgehandeld` and associates requests with agencies and maker users (`app/Models/Clothing.php`, `app/Models/Agency.php`). Filament provides agency, clothing, offer, creator, collector, completion, history, user, invitation, and authentication-log resources. There are pages for maker information, agency reports, registration and a dashboard; the `CreatorChart` import in the panel provider could not be located in the tracked source inventory, so that widget registration merits runtime verification.

## WordPress integration assessment

### Verified facts

- `routes/api.php` defines only `GET /api/user` protected by `auth:sanctum` (`routes/api.php:17-19`).
- Repository searches found no WordPress URL, `wp-json` route, webhook receiver, shared-database setup, WordPress plugin or explicit WordPress client code. Generic service config and a commented webhook example in authentication-log config are not evidence of an active integration.
- No application-written outbound HTTP call or application webhook route was found. Mail is sent through Laravel's configured mail system for user invitations (`app/Filament/Resources/UserInvitationResource/Pages/ManageUserInvitations.php:42-44`).

### Inference

The backend appears to be a standalone administrative application whose principal data is held in its Laravel database. It cannot be concluded from this repository whether WordPress embeds links, submits forms directly to another service, imports exports manually, uses a separately installed plugin, or shares infrastructure outside the codebase.

### Unknowns requiring WordPress/environment inspection

- Whether the WordPress installation has custom plugins, theme code, scheduled tasks, or forms that exchange TFFK data.
- Data ownership and source-of-truth rules across systems, including contact, request and maker records.
- Any API credentials, database sharing, file exchange, manual Excel workflow, hosting-level proxy or integration not represented here.
- Whether CORS or Sanctum stateful-domain settings are used by a browser client; current CORS config permits API paths, but routes/configuration alone do not show a client integration.

## Technical health and security summary

### Verified risks

1. `isAdmin()` grants true for every user, including where called in the clothing edit page (`app/Models/User.php:74-82`, `app/Filament/Resources/Clothing/Pages/EditClothing.php:24-33`).
2. Access policy is fragmented across `active`, name-based helper methods, role checks and generated permissions (`app/Models/User.php`, `app/Providers/AuthServiceProvider.php:29-39`, `config/filament-shield.php`, `app/Policies/`).
3. Invitation code uses `md5(rand(0,9) . email . time())`, rather than a cryptographically secure random token (`app/Filament/Resources/UserInvitationResource/Pages/ManageUserInvitations.php:25-27`).
4. The import declares no validation rules and `uniqueBy()` returns `email`, which is not an evident column in the clothing model/table (`app/Imports/ClothingsImport.php:69-82`).
5. Deployment workflow runs `php artisan migrate` and `db:seed --class=ShieldSeeder` after syncing files (`.github/workflows/main.yml:54-88`), with no test or migration review gate visible in this repository.
6. Only example unit and feature tests are present; no business, authorization, import, integration or migration tests were found (`tests/`).
7. Schema and model show weak referential integrity for maker/user references: `clothing.maakster_id` is an integer without a declared foreign key, while `agency_id` is a foreign key (`database/migrations/2023_02_24_220429_create_clothing_table.php:16-29`).
8. Some later data migrations have empty `down()` methods (`database/migrations/2026_01_12_clothing_archive_23-24.php`, `database/migrations/2026_02_14_selectedyear.php`), limiting rollback. Their operational impact depends on deployed data/state.

### Limits

No runtime app boot, test run, dependency installation, database connection, migration, seeder, production credential use, security scanner, or remote deployment inspection was performed. Consequently package runtime compatibility, active DB schema state, actual production authorization behavior, mail delivery, and deployment success remain unverified.

## Recommended next steps

1. Confirm intended authorization roles and trace each resource/policy path; address the unconditional admin helper as a priority.
2. Review deployment access and migration/seeder process against production operations, including backup and rollback expectations.
3. Inspect the separate WordPress repository/installation and agree on data ownership and integration contracts.
4. Review invitation-token lifecycle, import validation/upsert semantics, and schema integrity with representative data expectations.
5. Establish a repeatable test environment and add coverage around authorization, status transitions, imports and invitations before framework/package upgrades.

See [TFFK_FUNCTIONAL_INVENTORY.md](TFFK_FUNCTIONAL_INVENTORY.md) and [TFFK_TECHNICAL_BACKLOG.md](TFFK_TECHNICAL_BACKLOG.md) for detailed inventories and prioritized findings.
