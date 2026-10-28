# TFFK integrations and WordPress boundary

Audit date: 2026-10-16. Laravel-side static evidence only. The WordPress source is outside this repository and was not accessed.

## Integration inventory

| Integration | Laravel evidence | Data/direction/auth | Assessment |
| --- | --- | --- | --- |
| WordPress public site | No WordPress URL/client, `wp-json` route, webhook handler, plugin or shared DB configuration found. | Unknown; no data owner or direction can be inferred. No shared authentication evidence. | **Not established by this repo.** Inspect wp-admin and cPanel. |
| Laravel API | `routes/api.php:17-19`: `GET /api/user`, `auth:sanctum`. | Returns current authenticated user; token/session behavior is Sanctum/framework-managed. | Minimal example endpoint; not a TFFK business API. |
| Invitation email | `Mail::to(...)->send(new UserInvitationMail(...))` (`ManageUserInvitations.php:42-44`, resend in `UserInvitationResource.php:78-85`). | Laravel mail config and environment decide transport; recipient is invite email. | Visible application email; provider, deliverability and sender/domain validation unknown. |
| Authentication logging | Rappasoft package and custom resource/config (`config/authentication-log.php`, `app/Models/AuthenticationLog.php`). | Tracks login-related metadata; package may support webhook configuration but configured URL list appears commented. | Logging configured; external notification/webhook use not evidenced. |
| Social login | Dependency/config and `socialite_users` migration; panel provider imports but does not register `FilamentSocialitePlugin`. | Config mentions Facebook provider, but no active panel plugin wiring evident. | Partial/stale or unused; verify intent and runtime before claiming available. |
| Excel import/export | Maatwebsite Excel and Filament Excel actions/resources. | Files flow into local import process or out to authenticated user download. | Implemented code, validation and export authorization require review. |
| Queue/broadcast | Queue config and jobs migrations; broadcast provider and default user channel. | Queue defaults sync; no app job classes or app-specific channel behavior. | Framework capability present, no substantive integration found. |

## What repository evidence does and does not show

Verified: the Laravel app exposes one Sanctum-protected endpoint and sends invitation emails using Laravel's mail abstraction. There are no application-specific HTTP client calls, WordPress endpoints or webhook receivers in tracked source. `config/cors.php` contains permissive API CORS defaults but is not proof of an active frontend integration.

Reasonable inference: the Laravel backend can currently operate as an independent administration system, and WordPress may simply link to it. This is not proof that operators do not use a WordPress plugin, manual spreadsheet transfer, database access outside this repo, or hosting-level proxy.

Unknown: canonical owner for agency, request, user and maker records; whether WordPress collects public requests; any shared identifiers; synchronization direction/frequency; reconciliation/conflict rules; identities and credentials; manual processes; error monitoring; and data retention across both systems.

## Filament login link from WordPress

`app/Providers/Filament/AdminPanelProvider.php:30-38` configures the default panel at path `''` and enables `->login()`. The expected public login URL is therefore `https://<backend-host>/login`; the exact configured host and generated route were not queried at runtime. The backend host should be confirmed by the system owner from the public domain/`APP_URL` configuration without copying configuration secrets.

A WordPress button can point to the absolute backend login URL with an ordinary link. This requires no Laravel code change and does not authenticate a WordPress user in Laravel. The browser will present Laravel's own login/session. No evidence supports shared credentials, SSO, shared cookies, or account provisioning. If one-login behavior is required, define identity provider, account lifecycle, role mapping, MFA, logout, recovery and audit requirements first.

## wp-admin/cPanel inspection checklist

Use read-only inspection and do not copy secrets or personal records into audit notes.

1. In wp-admin, list active plugins and inspect custom/site-specific plugins, snippets, theme integrations, form plugins, scheduled task plugins and REST API settings.
2. Search plugin/theme files in cPanel for the Laravel host, `/api`, `wp_remote_*`, HTTP clients, webhook URLs, database calls and scheduled tasks. Record file paths and behavior only; redact credentials and personal data.
3. Review forms and form action destinations: identify whether they create requests in WordPress, email operators, write to Laravel, or trigger another service.
4. Check WordPress and Laravel domains, HTTPS, redirects, reverse proxies and CORS expectations. Do not test credentials or tokens.
5. Establish record ownership, stable IDs, sync direction, retry/reconciliation and who resolves conflicts with TFFK stakeholders.
6. Check privacy notices, deletion/retention and exports in both systems.
7. Confirm the canonical Laravel login URL with the deployment owner. Test only an ordinary browser link using a normal account; do not assume SSO.

## Possible future integration patterns

No pattern is selected. If WordPress only links to Filament, keep the interaction as a normal external link. If it submits business data, first define a versioned Laravel API or signed webhook contract, authentication, replay protection, validation, idempotency, rate limiting, audit trail, error handling and data ownership. Avoid direct shared-database coupling unless both systems' owners explicitly accept schema coupling and operational risks.
