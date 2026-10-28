# TFFK testing, reliability and deployment audit

Audit date: 2026-10-16. Source/configuration review only; no test, deployment, DB or external service command was executed.

## Test coverage and test safety

Verified test files: `tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`, `tests/TestCase.php`, `tests/CreatesApplication.php`. `phpunit.xml` configures Unit and Feature directories but has DB connection/database lines commented (`phpunit.xml:7-30`). It sets mail array, queue sync, session array and cache array. No business-specific tests, factories beyond `UserFactory`, API auth tests, Filament authorization tests, import tests, migration tests or mail assertions were identified.

Tests were skipped: configured test DB isolation is not verified. Running a suite with an unset DB target could use a developer-configured database; no test command is safe until a disposable test database is explicitly configured and confirmed. Additionally, tracked migrations omit several schema tables used by the app, so the clean test schema bootstrap is not known. No setup command was run.

### Prioritized testing strategy

1. **Authorization matrix:** panel access by active flag, roles/capabilities, each resource and page; record ownership for maker/collector; direct URL, edit, bulk action and export checks.
2. **Core request lifecycle:** creation code uniqueness, allowed status transitions, deadline/date side effects, actor attribution and archive behavior.
3. **Offer claiming concurrency:** two users attempt to claim the same offered request; only one assignment should persist.
4. **Invitation lifecycle:** token strength format without asserting token value, expiry, single-use/replay, email uniqueness, correct role assignment, inactive-account rules, and mail failure.
5. **Import behavior:** malformed file/type, missing headers/agency, invalid row values, duplicate rows, partial failure notification and safe rollback expectations.
6. **Collector/maker isolation and report correctness:** synthetic dataset across multiple users/agencies/year/status, including archived rows.
7. **Password reset and session behavior:** fresh migrated test DB, throttling, email transport fake, secure cookie config review.
8. **Data integrity/migrations:** migrate and rollback only disposable DB; legacy data transformations tested against anonymized fixtures; assert constraints and FK actions.
9. **Performance:** query-count and representative explain plans for AgencyReport, resource filters, badges and export paths on a synthetic dataset.

## Reliability observations

- App schedule is empty except a commented `inspire` example (`app/Console/Kernel.php:13-18`); no application job classes were found.
- Queue default is `sync` (`config/queue.php:16`). Thus any future queued work runs synchronously unless environment overrides it. No worker/scheduler setup is documented.
- Invitation mail is sent synchronously. Import performs potentially many model inserts and may partially apply rows; no explicit transaction or reconciliation is visible.
- `Clothing` model hook requires authenticated user for create attribution; background/CLI create paths may fail or produce missing actor context (`app/Models/Clothing.php:194-236`).
- Error handler is Laravel default with no custom report policy; no structured domain error handling or monitoring integration is evident (`app/Exceptions/Handler.php`).
- Logger defaults are environment-configured; production log level, retention, alerting and access are unknown. Activity log may duplicate free-text `Activity` history.

## CI and deployment workflow

`.github/workflows/main.yml` runs on pushes to `prod`, `test`, `sendy` and maps each branch to a named cPanel directory (`:1-29`). It uses Ubuntu, PHP 8.4, Composer install with `--no-dev`, tars `./*`, uploads over SSH using third-party actions, removes/recreates a staging directory, copies an externally stored `.env`, then `rsync --delete`s into the destination and runs `artisan migrate`, `db:seed --class=ShieldSeeder`, `config:cache` and `route:cache` (`:30-83`).

Verified workflow risks: no tests, npm asset build, explicit health check, backup, maintenance mode, release symlink/atomic switch, migration approval or rollback in workflow. Uses action tags including `@latest` and major tags rather than commit hashes. `tar ./*` does not include dotfiles by shell glob; a separate `.env` is copied into the staging release. The actual deployment directory, branch protection, secret scope, SSH host and server state are not inspected.

Potential operational concerns requiring owner verification:

- `rsync --delete` can remove files in target; confirm user uploads/storage and symlink layout are excluded or outside target.
- Migrations run on every deployed environment; schema changes need backup, lock and forward recovery plan.
- `ShieldSeeder` is run at deploy; verify it is idempotent and doesn't overwrite user-managed roles/permissions.
- Config/route cache depends on environment and route serializability; no run result inspected.
- No evidence of storage symlink creation, queue worker restart, scheduler installation, PHP-FPM reload, backup policy, health monitor or retention.
- Branch tips are divergent and local refs may be stale. A branch name is not proof of target contents.

## Branch comparison (read-only local refs)

| Ref | Tip/date | Commit relationship | Tree observation |
| --- | --- | --- | --- |
| `origin/main` | `c488871`, 2026-05-08 | One commit unique versus prod/test ancestry; is ancestor of sendy. | Current checked-out source. |
| `origin/prod` | `5d76566`, 2026-04-30 | 9 commits reachable from prod but not main; main has 1 unique commit. | Diff from main is workflow-only (3 lines changed). |
| `origin/test` | `2eb7f25`, 2026-04-30 | Ancestor of prod; 4 commits reachable from prod but not test. Main has 1 unique commit versus test. | Diff from main is workflow-only (3 lines changed); same application tree as prod. |
| `origin/sendy` | `781211e`, 2026-05-08 | Contains main; 2 commits unique to sendy. | Diff from main is workflow-only (3 lines changed). |

These graphs are surprising given the branch labels; verify fresh remote refs and deployed commit SHA with maintainers before release planning. No fetch or checkout was done.

## Runtime, dependencies and environment assumptions

- `composer.json` says PHP `^8.1`, but locked Laravel/Filament dependencies require PHP `^8.2`; GitHub workflow and remote PATH select PHP 8.4. Align documented/root PHP requirement with actual minimum and production runtime.
- Laravel 12 lock is `v12.0.0`; Filament is `v4.7.1`; several package constraints are broad. As of audit date Laravel 12 bug-fix support is ended but security support continues to 2027-02-24; PHP 8.1 is unsupported. Upgrade current patch/dependencies with testing. Official references and dates are recorded in `TFFK_DEEP_AUDIT.md`.
- Vite/Tailwind scripts exist, but workflow does not run `npm ci` or `npm run build`. Determine whether compiled assets are committed, generated elsewhere or supplied by the server.
- DB driver/runtime, backup schedule, environment values, mail transport, public storage, queue connection, log channel/rotation and application URL are not verified. Do not copy `.env`/secret values into onboarding docs.
- `.env.example` exists; it was not opened. Treat it as a template, not evidence of production settings.

## Safe next actions

1. Agree on an isolated test DB and synthetic fixture data; then run the narrow test suite after confirming the configured DB name/host is local and disposable.
2. Add CI steps for lint/static checks and business tests only after isolation and required PHP extensions are documented.
3. Establish a deployment release checklist with immutable artifact SHA, explicit approval/branch protection, DB backup, migration review, health check and recovery procedure.
4. Confirm backups, storage persistence, scheduler/queue needs, mail health and monitoring with cPanel/deployment owner without sharing secrets.
