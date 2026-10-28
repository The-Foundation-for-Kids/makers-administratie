# TFFK security, authorization and data integrity

Audit date: 2026-10-16. Static review only; no exploitation, live data, production credentials or external service access. Findings distinguish directly visible code from impact that depends on runtime configuration.

## Authorization and account access

| Finding | Evidence and preconditions | Impact / severity | Safe verification and remediation |
| --- | --- | --- | --- |
| `isAdmin()` is always true | `app/Models/User.php:74-82`; only visible caller is the edit page (`app/Filament/Resources/Clothing/Pages/EditClothing.php:24-33`), whose returned header action list currently contains only a commented deletion. | **Medium**: faulty authorization helper is verified, but no active privilege escalation is established from current call sites. | Test effective actions with synthetic users; replace with explicit permission/policy if retained. TFFK-AUD-001. |
| Authorization mechanisms conflict | `AuthServiceProvider.php:29-39` grants `Super Admin` via Gate before and returns role comparison from Gate after. Generated policies live in `app/Policies/`; resource code separately tests `super_admin`, `hasrole`, and names. | **High**, confirmed inconsistent design; exact bypass depends on framework Gate semantics and call path. | Build permission inventory and route/action tests for anonymous, inactive, maker, collector, admin and super-admin fixtures. Review every resource's `can*`, policy mapping and query scope. TFFK-AUD-002. |
| Panel access is only active flag | `User::canAccessPanel()` returns whether `active` is true (`User.php:66-72`). | **Medium**, if role should limit access to panel. All active accounts can reach panel auth boundary, even if resources later hide pages. | Test active/inactive access and ensure every page/action has server-side authorization. |
| Query scopes vary by role | Creator and history scope to current maker (`CreatorResource.php:296-305`, `CreatorHistoryResource.php:165-198`); collector query scopes via assigned agency (`CollectorResource.php:141-149`); offer/report/admin resources differ. | **High**, potential cross-user data exposure if direct record routes or mutations bypass list scopes. There is no dynamic proof of bypass. | With test fixtures, request list, view, edit, export and bulk actions as each role; verify record IDs cannot be loaded across ownership. TFFK-AUD-003. |
| Admin user form excludes a role by name only | `app/Filament/Resources/Users/UserResource.php:93-95` filters `super_admin`, while gates use `Super Admin` and other code also uses Dutch role names. | **Medium**, role name drift may allow assignment or block intended access. | Confirm deployed roles/guards using a sanitized schema/role export, not personal rows; centralize role names. |

## Authentication, sessions and recovery

- Laravel web guard uses server-side session driver selected by `SESSION_DRIVER`, default database (`config/auth.php`, `config/session.php`). Panel middleware enables session and CSRF protections (`AdminPanelProvider.php:49-65`).
- Panel includes password reset. `config/auth.php` selects `password_reset_tokens`, but only `database/migrations/2014_10_12_100000_create_password_resets_table.php` is visible and creates `password_resets`. Verify reset end-to-end in a fresh local database before relying on recovery (`config/auth.php:81-90`).
- User model does not implement `MustVerifyEmail`; custom register page raises `Registered` but uses a container bind for verification listener (`app/Filament/Pages/Register.php:62-66`). Whether accounts are meant to be verified is a stakeholder decision. Registration is rate-limited at page level with `rateLimit(2)` (`:37-55`).
- `LogSuccessfulLogin` updates `last_login_at` for every authenticated panel request (`app/Http/Middleware/LogSuccessfulLogin.php:18-24`), making the label “last login” inaccurate and adding writes. Authentication-log package separately listens to login/failure/logout events.
- Session cookie security flags depend on config/environment and were not inspected at runtime. Verify HTTPS, `SESSION_SECURE_COOKIE`, `APP_DEBUG=false`, trusted proxy handling and session driver on each deployed host without copying `.env` values into docs.

## Invitation onboarding

`ManageUserInvitations` generates a token using `md5(rand(0, 9) . email . time())` (`app/Filament/Resources/UserInvitationResource/Pages/ManageUserInvitations.php:25-27`). The invitation schema permits nullable email and code and has no unique index (`database/migrations/2026_01_08_create_user_invitation table.php:11-17`). Registration looks up the code, assigns its roles, deletes the row and logs in (`Register.php:28-72`). Expiry and atomic one-time consumption are not visible. This is a verified weak token construction; exploitation requires an attacker to obtain or guess a valid code and is not demonstrated.

Remediation: use cryptographically random token material, ensure unique non-null storage, expire invitations, consume once in a transaction/conditional delete, and never log token values. Safe tests: valid, expired, duplicate, wrong email, replayed and concurrent redemption with synthetic accounts.

## Input validation, mass assignment and uploads

- `Clothing::$fillable` contains many fields, including status, agency/maker IDs, actor IDs and activity text (`app/Models/Clothing.php:25-68`). This is broad; current Filament forms and import are trusted only if their schemas enforce proper role and relationship constraints. Model hooks also set some fields. Review every mass-assignment entrypoint before adding API endpoints.
- Form validation exists on some agency/user/invitation fields and selected clothing fields, but is inconsistent across parallel resource schemas. A field being `disabled()` or hidden is a UI rule, not authorization.
- Import rules are empty (`app/Imports/ClothingsImport.php:69-75`); relationship IDs and imported values lack explicit constraints. `uniqueBy()` points at `email` (`:77-82`) despite mapping clothing attributes.
- Upload form uses `FileUpload`, `preserveFilenames()`, `storeFiles(false)`, `visibility('private')`, and accepted types (`app/Filament/Admin/Resources/ClothingResource/Actions/ClothingImportAction.php:53-59`). No size limit or explicit error path is visible. `storeFiles(false)` means persistence behavior should be checked against the installed Filament importer path, not inferred from the file input alone.
- Password inputs are hashed in UserResource; `Password::defaults()` applies min 8, mixed case and uncompromised (`app/Filament/Resources/Users/UserResource.php:70-88`, `app/Providers/AppServiceProvider.php:28-39`). Numbers and symbols are commented out, which is policy choice rather than proof of a defect.

## HTML rendering / XSS

**Likely conditional stored-XSS risk (High):** model and Filament callbacks construct `HtmlString` by concatenating stored fields. Examples include `Clothing::adres()` (`app/Models/Clothing.php:151-160`), `ClothingResource` activity rendering (`app/Filament/Resources/Clothing/ClothingResource.php:202-210`), and maker/offer/agency views (`CreatorResource.php:74-103`, `OfferResource.php:80-105`, `ClothingRelationManager.php`). Agency address, clothing wish, notes or activity fields may contain user/import controlled text. If rendered without escaping and an account with write access can insert HTML, other viewers could receive executable markup. No exploit was tested. Escape dynamic content and create only trusted structural markup. Verify using harmless markup in an isolated local fixture and inspect the rendered DOM; don't use script payloads.

## API, webhooks and cross-origin

- API defines only `GET /api/user` with `auth:sanctum` (`routes/api.php:17-19`). No business API or webhook endpoint was identified.
- Sanctum tokens trait and personal token migration exist, but no token issuance flow is evident. Do not assume a supported external API.
- `config/cors.php:18-34` allows wildcard origins, methods and headers for API/CSRF paths, while credentials are false. This is permissive but does not itself bypass Sanctum. Revisit before exposing sensitive browser API or cookie-based cross-origin auth.
- CSRF middleware is in web and panel middleware. No Laravel webhook route was found; webhook signature/authentication is therefore not applicable to observed endpoints.

## Data integrity, retention and query behavior

- `clothing.agency_id` has a foreign key; `maakster_id` and legacy actor IDs are plain integer columns, no FK (`database/migrations/2023_02_24_220429_create_clothing_table.php:20,28,44-60`).
- The tracked migration set contains no table-creation migration for `agencies`, `agency_planning`, or the Spatie `roles`/`permissions`/pivot tables. `clothing` nevertheless references `agencies`; the app and `ShieldSeeder` depend on role/permission storage. This is verified repository evidence, but external schema provisioning or omitted migrations may exist. A clean install is not demonstrably reproducible from tracked files alone.
- `Agency::verzamel_user()` infers `user_id`, but actual column and inverse relation use `verzamel_user_id` (`app/Models/Agency.php:32,41-45`, `User.php:38-41`). Likely relationship mismatch; verify usages and schema.
- Clothing `Code` is nullable and unconstrained unique. Model hook reads latest matching code then increments (`Clothing.php:194-214`); concurrent creates can collide. Offer claiming similarly has read-modify-save without lock/transaction (`OfferResource.php:225-245`).
- `Clothing` uses archive package plus DB `softDeletes()` and has migration data updates to `archived_at`. Actual retention/purge behavior is unclear. No explicit retention schedule was found. Activity log records changed fillable fields, which may include private request/contact data.
- User deletions and clothing references have no documented cascade/retention strategy. Verify package archive behavior and FK policies in schema-only review; do not inspect production personal rows.
- Report code repeats monthly relation counts and uses MySQL-specific `month()` / `ISNULL()`; no relevant compound indexes are visible in migrations. Query performance is unmeasured.
- Data-moving migrations copy 2023/2024 rows to `clothing` with empty `down()` methods; archive/year migrations also lack rollback. Rehearse only on disposable anonymized copies.

## Privacy and logging

The schema/model handles child-related request attributes, wishes, ages, clothing preferences, agency contacts and shipping details (`Clothing.php`, initial clothing migration, `Agency.php`). Avoid including values in logs/audit artifacts. Activitylog's `logFillable()` may capture changes to notes, tracking fields or other private fields. Define least-privilege access, export restrictions and retention/deletion policy with TFFK. Authentication log retention and notification/webhook settings are package config; no cleanup schedule is visible in app schedule.

## Safe verification matrix

1. Build a local database with synthetic fixture data; verify DB name is local and not a production mirror before running any test.
2. Exercise role/resource and record ownership boundaries over HTTP/Livewire, including direct view/edit URLs, bulk actions and exports.
3. Test invitation issuance/expiry/replay/role assignment, import malformed/duplicate rows, concurrent code allocation/claim, and user deletion effects.
4. Check rendered output using harmless marker input; verify content is escaped.
5. Verify reset flow, HTTPS cookie flags, mail transport and CORS from configuration without exposing values.
6. Inspect schema metadata and query plans only against anonymized/disposable data.

Related backlog IDs: TFFK-AUD-001–TFFK-AUD-011, TFFK-AUD-014–TFFK-AUD-018.
