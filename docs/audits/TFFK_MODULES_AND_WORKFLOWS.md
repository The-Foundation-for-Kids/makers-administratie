# TFFK modules and business workflows

Audit date: 2026-10-16. Business meaning is inferred from Dutch labels and code paths; confirm terminology, rules and role expectations with TFFK stakeholders. Presence of a UI component is not runtime verification.

## Functional modules

| Module / purpose | Entry points and code path | Data and rules | Authorization / validation | Status and gaps |
| --- | --- | --- | --- | --- |
| Request intake and administration | Filament `ClothingResource`, `CreateClothing`, `EditClothing`, `ListClothing`; agency relation manager; `Clothing` model | `clothing`, linked to `agencies` and maker `users`; statuses in `Clothing::validStatus()`; `setStatus()` applies dates/deadline and clears assignment for early states. Create hook generates `Code`, defaults status, normalizes some input. | Filament resource policy plus Shield and field conditions. Required fields in resource forms; import path has no active row validation. | Implemented in code, insufficiently tested; status transitions are not centrally enforced; code generation races; duplicated forms and callbacks. |
| Offer / assignment queue | `OfferResource`, `ListOffers`, `Offer` subclass over `clothing` | Query shows `Aangeboden` requests subject to advance-allocation date/user (`OfferResource.php:265-278`). Individual/bulk “Oppakken” changes status and sets maker ID/name (`:210-245`). | Role/policy and query scoping need confirmation. No visible transaction/row lock around simultaneous assignment. | Implemented but contention, role isolation and exact pre-allocation semantics need tests/stakeholder confirmation. |
| Maker work and history | `CreatorResource`, `CreatorHistoryResource`, corresponding pages | Maker current list filters by `maakster_id = current user` and statuses `Opgepakt`, `Klaar` (`CreatorResource.php:296-305`). History filters same user, excludes those active states and includes archived (`CreatorHistoryResource.php:165-198`). | Query scope is a useful server-side boundary for reads, but all view/edit/create/export routes and policies need per-role tests. | Implemented but insufficiently verified; history classification excludes statuses rather than applying an explicit terminal set. |
| Collector work | `CollectorResource` and list/create pages | Resolves agency IDs by `verzamel_user_id = current user`, then queries clothing belonging to those agencies in selected statuses (`CollectorResource.php:141-149`). | Server-side list scope visible; verify direct record URLs, relation operations and bulk exports. `Agency::verzamel_user()` likely wrong key but not used in this query. | Implemented but authorization boundary requires safe tests. |
| Agency directory/reporting | `AgencyResource`, `AgencyReport`, `ClothingRelationManager` | Agency contact/delivery details plus clothing relations. Report computes totals and 12 monthly counts based on `datummaakster`, with year filter and archived records (`AgencyReport.php:65-120+`). | Agency form has some required/email fields. Report query has no clear user scope; requires confirmation that only administrators should see it. | Implemented but query performance, year behavior, MySQL-specific SQL and access matrix unclear. |
| Completed / confirmation handling | `CompletedResource` and pages; `Completed` subclass | Query filters `Status = Afgehandeld` (`CompletedResource.php:226-228`); forms also expose status and linked fields. | Resource policies plus form-level restrictions; delete/bulk actions are available in source. | Implemented, but changing status can make record leave its current resource; deletion semantics with archive package need verification. |
| User and role administration | `UserResource`, pages, UserStats; Spatie Permission/Shield | `users`, roles and permission pivot tables. Form hashes password and associates roles; active toggle and bulk delete present (`UserResource.php:70-95,102-188`). | Shield-generated policy and role choice excludes `super_admin` in form. `isAdmin()` helper bypass risk remains; super-admin role naming varies. | Implemented but highly sensitive; no tested administrator/non-admin capability matrix. |
| Invitation and onboarding | `UserInvitationResource`, `ManageUserInvitations`, `UserInvitationMail`, `Register` page | Invitation row contains email/code; create sends email synchronously. Signed `/register` route receives token, finds invitation and pre-fills readonly email; registration rate-limited, user created, role names assigned, invitation deleted (`Register.php:28-72`). | Email required/unique among invitations; token generation weak; no visible expiry; registration rate-limits at page handler. | Partially implemented; token, expiry, duplicate race, role source and mail-failure handling need work. |
| Authentication monitoring | Rappasoft package, custom AuthenticationLog resource and middleware | Login/failure/logout event logging package config; custom last-login timestamp update on authenticated requests (`LogSuccessfulLogin.php:18-24`). | Panel resources have generated permissions; logs contain IP/device/user-agent style metadata depending package. | Implemented by package/config; retention, log access and whether last-login is accurate are unknown. |
| Import/export | Filament Excel and Maatwebsite Excel | Clothing import maps spreadsheet headings; many resources expose XLSX exports. | Upload field requires a file and marks visibility private; import row rules are empty, email is false apparent upsert key; export authorization inherits resource/action behavior. | Partially implemented; errors are ignored in UI and success is reported regardless of failure count. No tests. |
| Dashboard/help/year selection | `Dashboard`, `InformatieMaaksters`, `SelectYear` Livewire and Blade | Dashboard text differs by role presence. SelectYear mutates the user's `selected_year` field; options hard-coded 2023–2026. | Dashboard/page access depends on panel and page shield settings; Livewire mutation has no visible explicit range validation. | Implemented but year selection is stale-prone; audience behavior needs stakeholder confirmation. |
| Agency planning | `AgencyPlanning` model | Table name `agency_planning`; fields include name and notification/execution dates; `Agency` relation is commented out. No migration/usage found. | Unknown. | Legacy/unused or externally provisioned; confirm before modifying. |

## Traced workflows

### A. Create a request (source path)

1. Authenticated operator opens `ClothingResource` create page and submits schema fields (`app/Filament/Resources/Clothing/Pages/CreateClothing.php`, `ClothingResource.php`).
2. Filament validates marked-required fields; exact validation for all business fields is inconsistent across parallel resource forms.
3. Eloquent creates `Clothing`. `Clothing::creating` looks up agency code and year, queries the latest matching request code, assigns next code, defaults status, normalizes some text/numeric age fields and reads authenticated user for actor/activity (`app/Models/Clothing.php:194-236`).
4. Insert writes to `clothing`; `agency_id` has DB FK in initial migration, but maker and actor IDs do not (`database/migrations/2023_02_24_220429_create_clothing_table.php:16-63`).
5. Activitylog package logs dirty fillable values on updates. Filament renders response/notification.

Risks: code lookup+insert not atomic; no unique Code index; auth-dependent model hook may fail outside request; duplicate activity logging; no test. No external side effect is visible in this path.

### B. Claim an offered request

1. Maker opens `OfferResource`, whose base query restricts to `Aangeboden` and allows items without an advance reservation, expired reservations, or reservation matching current user (`OfferResource.php:265-278`).
2. Individual or bulk “Oppakken” action checks current status, calls `setStatus('Opgepakt')`, writes maker ID/name, saves, and sends an in-panel notification (`OfferResource.php:~200-245`).
3. `setStatus` timestamps `DatumMaakster`, sets deadline to 43 days if wish text includes birthday/December, otherwise 29 days, if no deadline already exists (`Clothing.php:127-137`). Update hook appends to activity text; activitylog logs changed fillable fields.

Risk: concurrent users may both see the row as offered and attempt claim; no transaction/lock or conditional update is visible. Role authorization and exact deadline business rule are not tested.

### C. Maker views active requests and archive

1. `CreatorResource::getEloquentQuery()` restricts to current maker and active statuses; resource badge repeats the query (`CreatorResource.php:296-305`).
2. `CreatorHistoryResource` includes archived rows and filters to same maker while excluding active statuses (`CreatorHistoryResource.php:165-198`); exports selected rows to XLSX (`:143-160`).

Risks: explicit actor-specific query scope is positive evidence for read isolation, but record-specific mutations/export routes have not been tested; `withArchived()` plugin behavior and intended archive definition need confirmation.

### D. Import clothing spreadsheet

1. Admin action form asks for agency, year and an uploaded file; optional “special” tab adds maker/status (`ClothingImportAction.php:40-79`).
2. Action passes uploaded path and form data to `ClothingsImport`, which maps heading names to fields and creates `Clothing` models (`app/Imports/ClothingsImport.php:27-57`).
3. Import implements `WithValidation` but rules array is empty; `uniqueBy()` uses `email`, inconsistent with table fields (`:69-82`).
4. Failures are iterated but not displayed; action always sends success (`ClothingImportAction.php:91-99`).

Risks: malformed data, partial success without useful feedback, errors in model hook if agency absent, and duplicate generation under repeated imports. File parser behavior and actual uploaded-file path are not runtime-tested.

### E. Invite and register a user

1. Authorized panel user creates invitation, token assigned from time/email/small random value, duplicate existing-user email is halted, invitation email sent synchronously (`ManageUserInvitations.php:19-50`).
2. Signed route `/register` loads custom Filament registration; `mount()` looks up the submitted code and uses `firstOrFail()` (`Register.php:28-34`).
3. Register action applies Livewire rate limit 2, obtains form state, creates user, assigns invitation role names, deletes invitation, logs in and regenerates session (`Register.php:37-72`). Email is readonly, required, unique (`:75-84`).

Risks: weak/nullable/non-unique token; no expiration; token isn't visibly rotated or hashed; registration and consume are not in a transaction; mail failures and role assignment failures may leave invitation state. `active` default is not part of the shown creation payload—whether it defaults active depends on schema/default behavior.

### F. WordPress links to Filament

1. Panel provider uses root path and built-in `->login()` (`AdminPanelProvider.php:30-38`), so expected link is the backend host's `/login` path.
2. WordPress can render an ordinary external link/button pointing there. The user logs into Laravel with Laravel credentials. No WordPress changes to Laravel are implied.
3. SSO, shared accounts, automatic provisioning, return URL or cross-site session sharing are not evidenced and require separate identity/security design.

## Verification status labels

- **Implemented in source:** class/config/action exists and code path has been statically traced.
- **Insufficiently tested:** no business tests demonstrate correctness or access boundaries.
- **Partial / unclear:** code exists but required lifecycle/config/schema or stakeholder rule is unknown.
- **Potential legacy:** model/config/import exists without use/migration or is commented out.
- No business workflow is designated fully verified through tests because none of the business workflows has test coverage.
