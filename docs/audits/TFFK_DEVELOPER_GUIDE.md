# TFFK developer onboarding guide

This guide combines repository-verified conventions with proposed safe development steps. It is not a record of commands executed during the audit. Never point local/testing commands at a production database or use production credentials.

## 1. Local setup

### Repository facts

- Laravel 12, Filament 4, Composer and Vite are used. CI configures PHP 8.4; Composer root constraint says `^8.1`, but locked core dependencies require PHP 8.2+. Use PHP 8.4 for parity after confirming required extensions.
- `.env.example` exists. `composer.json` defines package discovery and Filament upgrade scripts; `package.json` defines `npm run dev` and `npm run build`.
- The app uses MySQL-specific query/migration syntax, so SQLite may not support the full application/test suite. The tracked migrations also omit creation of `agencies` and Spatie role/permission tables, so the intended clean-install schema process must be confirmed before setup.

### Proposed safe local sequence

1. Obtain a repository checkout and install PHP 8.4 plus the extensions required by Laravel, Filament, Excel/PhpSpreadsheet, and the chosen local DB. Confirm exact extensions against Composer's platform check in a development environment.
2. Run `composer install` and `npm ci` locally. These install dependencies and may run Composer lifecycle scripts; never use production secrets.
3. Copy `.env.example` to `.env` and set a disposable local database, local `APP_URL`, local mail catcher/array, `QUEUE_CONNECTION=sync`, and safe local session/cache drivers. Generate a fresh local app key with `php artisan key:generate`.
4. Before running any migration, inspect the resolved DB name and host and confirm the DB is local/disposable. The schema contains data moving migrations and omits some table creation from the tracked migration set; first confirm how the missing base schema is provisioned. Prefer a clean disposable database, not a production dump. For old migrations that require MySQL, use an isolated local MySQL DB rather than assuming SQLite compatibility.
5. Run `php artisan migrate` only against that disposable DB. Do not run `db:seed` without inspecting each seeder: `DatabaseSeeder` includes a hard-coded weak account seeder.
6. Start local app with `php artisan serve` and Vite with `npm run dev`; this is a proposed workflow, not checked against the current dependencies. Use synthetic data only.

Do not copy `mailpit.env` or any local/production environment file into tickets/docs. No values from env files belong in source control.

## 2. Understand the architecture

- Entrypoint/bootstrap: `public/index.php`, `bootstrap/app.php`, `app/Http/Kernel.php`.
- Route registration: `app/Providers/RouteServiceProvider.php`, `routes/web.php`, `routes/api.php`.
- Panel configuration: `app/Providers/Filament/AdminPanelProvider.php`; default panel is rooted at `/` and login is expected at `/login` on the configured backend host.
- UI/workflows: `app/Filament/Resources`, `app/Filament/Pages`, `app/Livewire`.
- Core data and rules: `app/Models/Clothing.php`, `Agency.php`, `User.php`; status/code side effects live in Clothing model hooks.
- Persistence: `database/migrations`; permission seeds in `database/seeders`.
- Imports/mail: `app/Imports`, `app/Filament/Admin/Resources/ClothingResource/Actions`, `app/Mail`.
- Permissions: `app/Policies`, `AuthServiceProvider`, Filament Shield config, role checks and resource query scopes. Review these together.
- Architecture and workflow detail: [TFFK_ARCHITECTURE.md](TFFK_ARCHITECTURE.md) and [TFFK_MODULES_AND_WORKFLOWS.md](TFFK_MODULES_AND_WORKFLOWS.md).

## 3. Locate feature code

For a proposed feature, start from the user-visible entry point, trace its Filament page/action to model and migration, then trace permissions and tests:

1. Search labels and route/resource identifiers with `rg`.
2. Read resource `form()`, `table()`, `getEloquentQuery()`, page actions and policies.
3. Read model relationships, casts, fillable fields, scopes and lifecycle hooks.
4. Read migrations and package config for persistence/side effects.
5. Inspect similar tests; currently there are no business tests, so create tests along with feature work after test DB isolation is established.
6. Ask TFFK to confirm undocumented business rules such as status transitions, deadline calculations, retention, role ownership and WordPress data ownership.

## 4. Feature example: WordPress-to-Filament login button (analysis only)

The panel provider uses `->path('')` and built-in `->login()`, so the expected target is `https://<backend-host>/login`. The host must be confirmed with the deployment owner; the runtime route was not generated during this audit. A normal WordPress anchor/button linking to this URL requires no backend change. The user logs into Laravel separately. Do not imply shared login, SSO or automatic account creation. Before adding any of those, gather identity, role mapping, MFA, logout, recovery, account provisioning, audit and privacy requirements.

## 5. Feature branch and implementation procedure

The following is proposed. The task owner must select the correct base branch because deployment branches are divergent and local tracking refs may be stale.

1. Confirm current base commit/branch with the maintainer; inspect `git status` and preserve existing work.
2. Create a short feature branch from the agreed integration branch.
3. Record expected behavior, roles, affected data, failure cases, migration compatibility and rollback/recovery approach before editing.
4. Keep the change small. Prefer shared domain rules/policies over duplicating logic across resource callbacks. Do not make a schema change without a data migration/compatibility plan.
5. Add tests for success, invalid input, role boundaries, record ownership and relevant failure paths. Use synthetic data and isolated DB only.
6. Run focused tests, then the full isolated suite; run Pint/static checks if present. Review generated diff for accidental secrets, unrelated changes and migration risk.
7. Request review from a maintainer familiar with TFFK business rules, especially authorization and data migrations.

## 6. Tests and verification

`phpunit.xml` discovers `tests/Unit` and `tests/Feature`, but currently contains no confirmed DB isolation. Do not run tests until `DB_CONNECTION` and `DB_DATABASE` are explicitly configured for a disposable local database. Existing two ExampleTests do not verify application features. Recommended order: authorization matrix, request lifecycle/code generation, offer claim, invitation, imports, maker/collector isolation, reports/archive, password reset.

## 7. Review and deployment

Verified workflow: `.github/workflows/main.yml` deploys pushes to `prod`, `test`, `sendy`, runs migrations and `ShieldSeeder`, but has no visible test/build/health gate. Do not push or deploy from a feature branch. Proposed release checks:

- confirm exact target branch and deployed SHA with the maintainer;
- obtain reviewed code and migration plan, passing tests and build artifact;
- confirm backups, storage persistence, migration duration and forward recovery;
- deploy to test environment first and verify health, mail and permissions with synthetic/non-sensitive checks;
- deploy to production only through maintainer-approved release process;
- verify health and logs, monitor queue/scheduler if configured, and document recovery.

Branch names do not prove deployment state. The audit did not verify hosting, backup, queue worker, scheduler, mail or rollback configuration. Do not use the workflow's destructive sync or migration commands against an unconfirmed host.

## 8. Open onboarding questions

Before the next developer starts, obtain answers for: approved dev DB engine/version; synthetic fixture strategy; canonical role matrix; active status meaning; status transition/deadline policy; owner of `Code`; data retention and archive semantics; branch/review flow; deployment approvals/backups; mail and file storage operations; WordPress integration/data ownership; and canonical backend hostname.
