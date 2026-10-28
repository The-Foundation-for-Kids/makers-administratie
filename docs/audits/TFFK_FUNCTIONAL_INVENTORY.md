# TFFK functional inventory

Audit date: 2026-10-16. This inventory describes code present in the checked out repository. “Implemented” means code/configuration exists, not that every path was executed or confirmed in production.

## Application entry points

| Area | Status | Evidence and notes |
| --- | --- | --- |
| Filament admin panel | Implemented | Root path panel with login, password reset, profile, resource/page discovery, Shield plugin and session middleware. `app/Providers/Filament/AdminPanelProvider.php:30-65`. |
| Web routes | Implemented, minimal | Signed registration route only. `routes/web.php:17-20`. Filament routes are registered by the panel provider/package. |
| API | Minimal/example | Sanctum-authenticated `/api/user` route only. No TFFK business API routes. `routes/api.php:17-19`. |
| Websocket channels | Unclear | `routes/channels.php` exists; no application channel definitions were identified. |

## Filament panels, resources, pages and widgets

One panel is configured: `admin`, default, path `''`. Its resources are discovered in `app/Filament/Resources`; pages are discovered in `app/Filament/Pages` (`app/Providers/Filament/AdminPanelProvider.php:39-48`). `widgets([])` explicitly leaves panel widgets empty, though there are resource-local widgets and a discovery path for `app/Filament/Widgets`.

| Resource/page | Implemented capability | Notes / evidence |
| --- | --- | --- |
| Clothing (`ClothingResource`) | Request management, status changes, assignments, import, export, stats | `app/Filament/Resources/Clothing/`; import actions under `app/Filament/Admin/Resources/ClothingResource/Actions/`. Status workflow also in `app/Models/Clothing.php`. |
| Agencies (`AgencyResource`) | Agency/contact/delivery details, associated clothing and exports | `app/Filament/Resources/Agencies/AgencyResource.php`; relation manager `.../RelationManagers/ClothingRelationManager.php`. Agency import appears commented out in `Pages/ListAgencies.php`. |
| Creators | Maker-facing request lists, status slices and export | `app/Filament/Resources/Creators/CreatorResource.php`; query scoping includes current maker for some views. |
| Creator histories | Historical/archive request view and export | `app/Filament/Resources/CreatorHistories/CreatorHistoryResource.php`. |
| Collectors | Collector-oriented request lists and exports | `app/Filament/Resources/Collectors/CollectorResource.php`; query derives agencies by `verzamel_user_id`. |
| Offers | Offer/assignment workflow over clothing records | `app/Filament/Resources/Offers/OfferResource.php`. |
| Completeds | Completed/confirmation-oriented request workflow | `app/Filament/Resources/Completeds/CompletedResource.php`. |
| Users | User management, active flag, role-related stats and exports | `app/Filament/Resources/Users/`. User stats widget `app/Filament/Resources/Users/Widgets/UserStats.php`. |
| User invitations | Create invitation, email invitation, manage invitations | `app/Filament/Resources/UserInvitationResource/`. Registration consumes code in `app/Filament/Pages/Register.php`. |
| Authentication logs | Review authentication events | `app/Filament/Resources/AuthenticationLogs/`; package config in `config/authentication-log.php`. |
| Dashboard | Custom dashboard page | `app/Filament/Pages/Dashboard.php`; content/runtime behavior not fully verified. |
| Agency report | Reporting page | `app/Filament/Pages/AgencyReport.php` and `resources/views/filament/pages/agency-report.blade.php`. |
| Maker information | Informational page | `app/Filament/Pages/InformatieMaaksters.php` and matching Blade view. |
| Registration/profile | Custom invitation registration and profile | `app/Filament/Pages/Register.php`, `app/Filament/Pages/Auth/EditProfile.php`. |
| Clothing stats widget | Status/request overview | `app/Filament/Resources/Clothing/Widgets/StatsOverview.php`. |
| CreatorChart | Apparently unused/legacy | Panel provider imports `App\Filament\Widgets\CreatorChart`, but no matching tracked file was found and panel explicitly registers no widgets (`AdminPanelProvider.php:15`, `:48-49`). Runtime discovery was not attempted. |

## Data model and relationships

| Model | Table/role | Relationships and observations |
| --- | --- | --- |
| `User` | `users` | Has many agencies through `verzamel_user_id`; has many clothing through `maakster_id`; HasRoles, API tokens, notifications and authentication logging. `app/Models/User.php:12-98`. |
| `Agency` | `agencies` | Belongs to collector/assigned user; has many clothing; status and month scopes. `app/Models/Agency.php`. |
| `Clothing` | `clothing` | Core request data with status, year, agency, maker, sizes/preferences, dates, shipping, notes, priority and activity; belongs to agency and maker; uses activity logging and archive trait. `app/Models/Clothing.php`; initial schema `database/migrations/2023_02_24_220429_create_clothing_table.php`. |
| `Creator`, `CreatorHistory`, `Collector`, `Offer`, `Completed`, `Confirm`, `Management` | All map to `clothing` | Workflow-oriented subclasses of `Clothing`; scope behavior primarily in corresponding Filament resources. |
| `UserInvitation` | `user_invitations` | Stores email and code; HasRoles trait, but no explicit user/role relation. `app/Models/UserInvitation.php`; migration `database/migrations/2026_01_08_create_user_invitation table.php`. |
| `AuthenticationLog` | package-configured table | Authentication log model/package behavior. |
| `AgencyPlanning` | `agency_planning` | Model exists with date/notification fields and BelongsTo Agency; migration was not found, and agency planning relation is commented out in `Agency`. Active usage is unclear. |

### Request lifecycle

`Clothing::validStatus()` lists `Ingevoerd`, `Aangeboden`, `Opgepakt`, `Klaar`, `Verzonden`, `Ontvangen`, `Afgehandeld` (`app/Models/Clothing.php`). `setStatus()` writes the status, sets a pickup date/deadline on `Opgepakt`, and clears maker assignment/deadline on `Ingevoerd` or `Aangeboden`. A notable ambiguity: the method returns `true` even when the requested status is not in the allowed list (`Clothing.php`, `setStatus()`); callers and form constraints should be reviewed together.

## Authentication, authorization and permissions

- Filament panel authentication is enabled; active users pass `canAccessPanel()` (`app/Providers/Filament/AdminPanelProvider.php:36-65`, `app/Models/User.php:66-72`).
- Shield plugin is installed/configured; many resource policies are present under `app/Policies/` and check generated permission names.
- `AuthServiceProvider` maps only the Role policy explicitly and uses Gate before/after callbacks for role `Super Admin` (`app/Providers/AuthServiceProvider.php:20-39`).
- User invitations assign the invitation's role names when a new user registers (`app/Filament/Pages/Register.php:57-62`).
- Additional authorization uses `isAdmin()`, `isBeheerder()`, `hasrole()` and hard-coded user names in resources. `isAdmin()` currently always returns true (`app/Models/User.php:74-82`); `isBeheerder()` matches a literal name (`:84-90`).
- Exact role naming is inconsistent across code (`Super Admin`, `super_admin`, generated policy capabilities) and needs to be confirmed against seeded roles and deployed database state.
- Public signed registration route checks an invitation code on mount, rate-limits registration, then deletes invitation after creating the account (`app/Filament/Pages/Register.php:28-72`). The token generation method is weak (see backlog).

## Workflows, imports, exports, mail, schedules and external services

| Capability | Status | Evidence / uncertainty |
| --- | --- | --- |
| Clothing import | Implemented, validation unclear | `ClothingsImport` maps spreadsheet heading values to a new request; `rules()` returns no active rules. `uniqueBy()` returns `email`, which does not match the visible clothing schema. Import action notes row error display TODO. `app/Imports/ClothingsImport.php:27-82`, `app/Filament/Admin/Resources/ClothingResource/Actions/ClothingImportAction.php`. |
| Agency import | Partial/unclear | An `ImportAction` configuration appears commented out in `app/Filament/Resources/Agencies/Pages/ListAgencies.php`. |
| Export | Implemented | Filament Excel exports are configured on agencies, collectors, creators/history, users and clothing relation/resource surfaces. |
| Invitation email | Implemented | `Mail::to(...)->send(new UserInvitationMail(...))`; Blade template `resources/views/mail/auth/invitation.blade.php`. Synchronous send call is visible; mail provider/environment unknown. |
| Other email | Unclear | Authentication-log package has notification settings; active configured mail transport and delivery were not inspected. |
| Scheduled tasks | None found in app schedule | `app/Console/Kernel.php:13-18` contains only a commented example. Package-defined schedules may exist. |
| Background jobs | No app jobs found | Queue configuration and migrations exist; default queue connection is `sync` (`config/queue.php:16`). No application job classes found. |
| External integrations | Unclear/minimal evidence | Dependencies include Sanctum and Filament Socialite-related config/migration; no application-specific outbound API or webhook implementation found. |
| Social login | Partial/unclear | Socialite config and `socialite_users` migration exist, but panel provider does not add the socialite plugin (it is imported but unused); behavior needs runtime/config verification. |

## WordPress evidence

Repository evidence does not establish a Laravel-to-WordPress integration. No WordPress-specific endpoint, plugin, webhook, client, or shared DB configuration was found. The only API route is `/api/user` guarded by Sanctum. Data ownership, synchronization direction, cross-system identity, credential exchange, and consistency behavior remain unknown until the separate WordPress installation and any deployment-level integration are inspected.

## Test coverage

Only default/example test files were found: `tests/Unit/ExampleTest.php` and `tests/Feature/ExampleTest.php`. No tests were found for domain workflows, role access, invitation tokens, imports, exports, integration routes, migrations or schedules. Test commands were not run as this is an audit-only pass and the project test environment is not configured in `phpunit.xml` beyond generic defaults.
