# WeblexAI Community Edition Implementation Plan

## 1. Purpose

This document is the implementation backlog for creating **WeblexAI Community Edition** as a clean, self-hosted, open-source application in the `weblex-opensource` directory.

Tickets are written so they can be moved into Jira, GitHub Issues, or another tracker. Each ticket includes its dependencies, scope, completion definition, tests, edge cases, and acceptance criteria.

## Implementation Status

Status as of 2026-06-08: the Community Edition rollout described in this plan has been implemented in this directory.

Completed rollout gates:

- Clean Apache-2.0 Community Edition packaging, trademark, support, security, contribution, release, and third-party notices.
- Billing, subscriptions, plans, usage limits, Google login, Google Analytics, Telegram logging, LibreTranslate, invites, and email workflows removed from runtime scope.
- Admin-managed users, admin project creation, direct project membership, self password changes, admin resets, and CLI password reset are in place.
- Translation API authentication requires a project API key plus exact accepted origin and page URL origin validation.
- Google Cloud Translation, OpenAI, OpenRouter, Gemini, and Qwen are configured as separate browser-managed providers.
- PostgreSQL and Redis are the supported production data services, with project config, translation, and glossary cache invalidation paths covered.
- Docker, traditional hosting docs, browser installer, backup/restore scripts, worker/scheduler services, signed release metadata, and update documentation are present.
- Docker E2E now covers installer, accepted-origin auth, denied-origin auth, translation through a mock OpenAI-compatible provider, backup, restore, Horizon, and scheduler checks.

Current verification baseline:

- `vendor/bin/pint --test`
- `php artisan test`
- `composer analyse`
- `npm run lint:check`
- `npm run typecheck`
- `npm run format:check`
- `npm run test:sdk`
- `npm run build`
- `composer audit --locked`
- `npm audit --audit-level=high`
- `docker compose --env-file tests/e2e/docker.env.example -p weblex-e2e -f docker-compose.yml -f tests/e2e/docker-compose.e2e.yml config --quiet`
- `powershell -ExecutionPolicy Bypass -File tests/e2e/run.ps1`

## 2. Confirmed Product Decisions

- The application is released under the **Apache License 2.0**.
- The official distribution is branded **WeblexAI Community Edition**.
- WeblexAI names and logos remain protected trademarks.
- The repository is a clean repository without private repository Git history.
- The product is one Laravel monolith containing:
  - The Vue/Inertia user dashboard.
  - The Filament admin panel.
  - The translation API.
  - The browser translation SDK source and compiled assets.
- Supported production infrastructure:
  - FrankenPHP.
  - PostgreSQL.
  - Redis.
  - Linux through Docker or traditional hosting.
- Windows and macOS are supported for development, not production.
- There is no public registration.
- Administrators create users and projects.
- Administrators manage one or more accepted origins for each project.
- Translation API requests are authenticated by both project API key and an exact accepted origin match.
- A project has exactly one owner: the administrator who created the project.
- Project roles remain:
  - Owner.
  - Manager.
  - Translator.
  - Viewer.
- Invitations are removed. Administrators directly assign existing users to projects.
- Users can change their own passwords.
- Administrators can reset any user's password.
- An emergency command-line password reset remains available.
- Billing, subscriptions, plans, quotas, and usage limits are completely removed.
- Google login, Google Analytics, Telegram logging, and LibreTranslate are removed.
- Supported translation providers:
  - Google Cloud Translation.
  - OpenAI.
  - OpenRouter.
  - Gemini.
  - Qwen.
- Provider credentials are configured through the browser and stored encrypted in the database.
- Infrastructure configuration is written to `.env` by the browser installer.
- Local filesystem storage is the default.
- S3-compatible storage and Cloudinary are optional.
- Email is not required by the initial Community Edition.
- Anonymous telemetry is not included.
- Update notifications and one-click updates are required.
- Community support uses GitHub Issues, GitHub Discussions, and private security reporting.
- Contributors must accept a CLA that permits use in both Community and commercial editions.

## 3. Explicitly Out of Scope

- Migration from the hosted/commercial WeblexAI database.
- Public user registration.
- User invitation emails.
- Password reset emails.
- Billing or payment processing.
- Subscription lifecycle management.
- Plan-based feature restrictions.
- Translation word or request quotas.
- Google OAuth.
- Product analytics or anonymous telemetry.
- LibreTranslate.
- SQLite or non-Redis production deployments in the first release.
- Kubernetes deployment in the first release.
- Windows or macOS production hosting.
- Automatic migration from the private repositories.

## 4. Target Repository Structure

The final repository should remain one application, not a collection of nested applications.

```text
weblex-opensource/
|-- app/
|-- bootstrap/
|-- config/
|-- database/
|-- docker/
|-- docs/
|-- public/
|   `-- wlai/
|-- resources/
|   |-- js/
|   `-- sdk/
|-- routes/
|-- storage/
|-- tests/
|-- .github/
|-- docker-compose.yml
|-- Dockerfile
|-- LICENSE
|-- NOTICE
|-- TRADEMARKS.md
|-- CONTRIBUTING.md
|-- SECURITY.md
`-- README.md
```

The exact SDK source location may change during implementation, but it must build as part of the same repository and must not become a separately deployed service.

## 5. Delivery Phases

| Phase | Goal |
|---|---|
| Phase 0 | Rotate credentials and establish a clean repository |
| Phase 1 | Create the monolith and remove commercial functionality |
| Phase 2 | Implement Community Edition account and project ownership |
| Phase 3 | Redesign translation providers and caching |
| Phase 4 | Build the browser installer and storage configuration |
| Phase 5 | Make Docker and traditional installations production-ready |
| Phase 6 | Implement secure updates |
| Phase 7 | Complete documentation, legal files, CI, and release validation |

---

# Epic SEC: Security and Repository Foundation

## SEC-001: Rotate All Exposed Credentials

**Priority:** Blocker  
**Dependencies:** None

### Description

Credentials are present in private repository remotes and tracked files. Treat all discovered credentials as compromised before publishing any Community Edition code.

### Tasks

- Revoke and replace the exposed GitHub personal access token.
- Remove credentials from all local Git remote URLs.
- Rotate Google service-account credentials.
- Rotate Telegram credentials even though Telegram support will be removed.
- Rotate Paystack credentials even though billing will be removed.
- Rotate MaxMind credentials.
- Rotate Cloudinary credentials.
- Rotate database, Redis, SMTP, and application secrets found in tracked environment files.
- Rotate production `APP_KEY` values if they were tracked.
- Remove or securely archive `PAT.txt`.
- Record credential rotation completion without recording the replacement secrets.

### Done Means

No credential found during the audit remains valid, and Git remotes use credential-manager or SSH authentication without embedded secrets.

### Tests

- Verify revoked credentials fail authentication.
- Run a secret scanner against every private repository.
- Inspect `git remote -v` in every repository.

### Edge Cases

- Credentials may exist in Git history even if removed from the current tree.
- A single service account may be used by production systems outside this workspace.
- Rotating `APP_KEY` can invalidate encrypted database settings and existing sessions.

### Acceptance Criteria

- [ ] Every identified credential has been rotated or revoked.
- [ ] No Git remote contains a username, token, or password.
- [ ] Production services use replacement credentials.
- [ ] A written internal rotation checklist is complete.

---

## SEC-002: Create a Clean Community Edition Repository

**Priority:** Blocker  
**Dependencies:** SEC-001

### Description

Initialize `weblex-opensource` as a new repository without copying private Git history. The initial source import must include only reviewed files.

### Tasks

- Initialize a new Git repository in `weblex-opensource`.
- Use `dashboard` as the architectural base without copying its `.git` directory.
- Copy only required source files.
- Exclude `.env`, `.env.global`, credentials, generated assets, dependencies, logs, and private documentation.
- Add a comprehensive `.gitignore` and `.dockerignore`.
- Configure a neutral remote without embedded credentials when the public repository exists.
- Add a pre-commit or CI secret scan.

### Done Means

The repository has a new root commit, contains no private history, and passes secret scanning.

### Tests

- Run Gitleaks or an equivalent scanner against the working tree and full repository history.
- Confirm `git log` contains only Community Edition commits.
- Confirm `vendor`, `node_modules`, `.env`, and service credentials are untracked.

### Edge Cases

- Generated public SDK bundles may contain embedded endpoint URLs.
- Example files can accidentally contain real-looking credentials.
- Git submodules can reintroduce private history.

### Acceptance Criteria

- [ ] The repository has independent history.
- [ ] Secret scanning reports no unresolved findings.
- [ ] No private remote URL is present.
- [ ] Dependency directories and runtime files are excluded.

---

## SEC-003: Add Apache-2.0 and Trademark Documentation

**Priority:** High  
**Dependencies:** SEC-002

### Description

Apply Apache License 2.0 to source code while reserving WeblexAI trademarks and official brand assets.

### Tasks

- Add the unmodified Apache License 2.0 text as `LICENSE`.
- Add `NOTICE` with project and copyright information.
- Add `TRADEMARKS.md`.
- Define permitted use of the “WeblexAI Community Edition” name.
- Require forks and third-party hosted offerings to avoid implying official affiliation.
- Identify logo and brand assets that are not licensed under Apache-2.0.
- Update Composer and npm package license metadata.
- Add source headers only where legally required or useful.
- Obtain legal review before the first public release.

### Done Means

The repository clearly distinguishes source-code rights from trademark and brand-asset rights.

### Tests

- Verify package metadata reports `Apache-2.0`.
- Run a license compliance scanner.
- Confirm every bundled dependency has a compatible redistributable license.

### Edge Cases

- Fonts, flags, illustrations, and icons may use separate licenses.
- “WeblexAI” references in forks cannot technically be removed by the software license alone.
- Third-party notices may be required in `NOTICE` or a separate document.

### Acceptance Criteria

- [ ] `LICENSE`, `NOTICE`, and `TRADEMARKS.md` exist.
- [ ] Composer and npm metadata use `Apache-2.0`.
- [ ] Brand assets have documented licensing status.
- [ ] Legal review findings are resolved.

---

## SEC-004: Establish Secret Management and Security Defaults

**Priority:** High  
**Dependencies:** SEC-002

### Description

Create secure defaults so future contributors cannot accidentally commit credentials or expose sensitive settings.

### Tasks

- Add `.env.example` containing placeholders only.
- Add automated secret scanning to CI.
- Ensure provider credentials use Laravel encrypted casts or encrypted settings.
- Mask credentials in UI, logs, exceptions, and debug output.
- Disable debug mode by default.
- Remove credential values from migrations and seeders.
- Add log redaction for authorization headers and provider keys.
- Document credential rotation and backup implications for `APP_KEY`.

### Done Means

Secrets are accepted only through environment variables or encrypted browser configuration and are never emitted in normal application output.

### Tests

- Submit provider keys and verify they are encrypted at rest.
- Trigger validation and provider errors and inspect logs.
- Attempt to commit a fixture containing a test secret pattern and confirm CI blocks it.

### Edge Cases

- Losing `APP_KEY` makes encrypted settings unreadable.
- Database backups contain encrypted credentials and still require secure handling.
- Filament form state can expose secrets if fields are hydrated incorrectly.

### Acceptance Criteria

- [ ] No secret has a non-empty default.
- [ ] Sensitive values are redacted in logs.
- [ ] CI blocks known secret patterns.
- [ ] Credential fields do not return stored values to the browser.

---

# Epic ARC: Monolith Consolidation

## ARC-001: Import and Normalize the Dashboard Base

**Priority:** Blocker  
**Dependencies:** SEC-002

### Description

Create the Community Edition application from the current dashboard while removing private environment assumptions and outdated project metadata.

### Tasks

- Import Laravel, Vue/Inertia, and Filament source.
- Rename package metadata and application defaults to WeblexAI Community Edition.
- Remove private AI-agent instruction files and private operational notes.
- Normalize PHP, Node.js, Laravel, and FrankenPHP version requirements.
- Remove generated Filament assets that can be installed or built.
- Verify the application boots with placeholder configuration.

### Done Means

The imported application installs from lock files and reaches a controlled “not installed” state without requiring production services.

### Tests

- Run `composer install`.
- Run `npm ci`.
- Run static boot checks with no `.env`.
- Run formatting and linting.

### Edge Cases

- Composer packages currently use `dev-master` or custom forks.
- Package discovery may access database-backed settings during installation.
- Build steps may depend on an existing `vendor` directory.

### Acceptance Criteria

- [ ] Dependencies install on a clean Linux runner.
- [ ] The application does not require private files to boot.
- [ ] Metadata identifies Community Edition.
- [ ] No commercial deployment endpoints are present.

---

## ARC-002: Remove Billing, Subscriptions, Plans, and Usage Limits

**Priority:** Blocker  
**Dependencies:** ARC-001

### Description

Completely remove commercial monetization and quota concepts instead of leaving them disabled behind flags.

### Tasks

- Remove billing routes, controllers, services, requests, UI, and navigation.
- Remove Paystack integration and Composer dependencies.
- Remove plans, subscriptions, subscription history, transactions, pricing cycles, and payment enums.
- Remove plan-based capability checks.
- Remove word limits, request limits, and usage-enforcement pipeline stages.
- Remove subscription observers, notifications, jobs, and reminders.
- Remove billing and usage tests.
- Remove payment settings and settings UI.
- Remove pricing data from seeders and resources.
- Preserve operational statistics only where they are not used as quotas.

### Done Means

No runtime path, database schema, UI label, API response, or dependency refers to billing, subscriptions, plans, quotas, or payment processing.

### Tests

- Search the repository for removed domain terms.
- Run route-list tests and confirm no billing endpoints exist.
- Run schema tests and confirm removed tables are not created.
- Create and use a project without plan or subscription records.
- Execute translation requests with no quota checks.

### Edge Cases

- `Plan` currently controls language switcher features and watermark behavior.
- Subscription status currently participates in API authentication.
- Usage statistics may share models with enforcement.
- Frontend types may still require plan fields.

### Acceptance Criteria

- [ ] Translation works without plan or subscription records.
- [ ] No payment package remains.
- [ ] No commercial table is created on a clean install.
- [ ] No UI contains pricing, billing, plan, or quota controls.

---

## ARC-003: Remove OAuth, Analytics, Telegram, and Email Dependencies

**Priority:** High  
**Dependencies:** ARC-001

### Description

Remove integrations that are excluded from Community Edition and ensure application boot does not require mail configuration.

### Tasks

- Remove Google OAuth routes, controllers, fields, UI, and Socialite dependency.
- Remove Google Analytics plugin and settings.
- Remove Telegram log handlers, settings, packages, and credentials.
- Remove invitation, password-reset, and lifecycle email notifications.
- Remove SMTP test tools and mail templates no longer used.
- Configure local logging as the default.
- Remove mail requirements from installer checks.
- Keep framework mail support only if another retained feature requires it.

### Done Means

The application has no active OAuth, Google Analytics, Telegram, or email workflow.

### Tests

- Boot with no mail, Google OAuth, analytics, or Telegram variables.
- Confirm no removed routes are registered.
- Confirm failures are logged locally without remote handlers.
- Search built frontend assets for removed integration names and IDs.

### Edge Cases

- Laravel authentication scaffolding may still expose password-reset routes.
- Filament may expose password-reset functionality by default.
- User models may still reference notification classes.

### Acceptance Criteria

- [ ] No excluded integration is required to install or run.
- [ ] Password-reset email routes are unavailable.
- [ ] Local logs capture application errors.
- [ ] Related dependencies are removed from lock files.

---

## ARC-004: Merge the Translation API into the Monolith

**Priority:** Blocker  
**Dependencies:** ARC-001, ARC-002

### Description

Move the translation API implementation from `cdn-translator` into the dashboard-based monolith and reconcile duplicate domain models, settings, observers, and support classes.

### Tasks

- Import API routes, controllers, requests, middleware, rate limiters, DTOs, pipelines, jobs, and translation services.
- Compare duplicate models and retain one authoritative implementation.
- Reconcile enum, cast, observer, and relationship differences.
- Merge cache services and service providers.
- Remove subscription and plan checks from API authentication and translation pipelines.
- Preserve project API-key validation and replace the single-origin check with project accepted-origin authorization.
- Preserve NDJSON streaming behavior.
- Add API health and readiness endpoints.
- Remove the need for a separate translator deployment.

### Done Means

One Laravel process serves the dashboard, admin, translation config API, translation streaming API, and SDK assets against one database schema.

### Tests

- Contract tests for `/api/project/config`.
- Contract tests for `/api/project/translations`.
- Test valid and invalid API keys.
- Test valid, missing, malformed, and mismatched origins.
- Test projects with multiple accepted origins.
- Test inactive projects and users.
- Test streaming batches, completion events, and error events.
- Test rate limiting by project and IP.

### Edge Cases

- Existing model files differ between dashboard and translator.
- Streaming responses can be buffered by proxies.
- A project may not yet have target languages.
- Provider failures must not leak credentials.
- Encoded page URLs and titles may be malformed.

### Acceptance Criteria

- [ ] No separate translator service is needed.
- [ ] SDK-compatible NDJSON responses are preserved.
- [ ] API authorization no longer checks subscriptions.
- [ ] API and dashboard use the same models and settings.
- [ ] API requests require both a valid project API key and accepted origin.

---

## ARC-005: Integrate the Browser SDK Build

**Priority:** High  
**Dependencies:** ARC-004

### Description

Move the TypeScript/Vue browser SDK into the monolith and make its production artifacts part of the application build.

### Tasks

- Import SDK source, styles, build script, and tests.
- Choose a repository-native source location such as `resources/sdk`.
- Integrate SDK dependencies without duplicating incompatible Vue versions.
- Build readable and minified bundles.
- Emit assets to `public/wlai`.
- Configure the default API endpoint relative to the installation URL.
- Add cache-busting/versioned asset URLs.
- Document the public JavaScript API.

### Done Means

`npm run build` produces both dashboard assets and SDK assets from one repository.

### Tests

- Run all existing SDK unit tests.
- Build readable and minified SDK bundles.
- Load the SDK from the monolith in a browser fixture.
- Test translation against the merged API.
- Verify CSS isolation from host websites.

### Edge Cases

- Host pages may use strict Content Security Policy.
- The app may be installed under a non-root URL.
- Browser caching can retain an incompatible SDK version.
- Vue runtime duplication can increase bundle size.

### Acceptance Criteria

- [ ] One build command produces all frontend assets.
- [ ] SDK tests pass.
- [ ] No absolute WeblexAI production endpoint is embedded.
- [ ] A sample HTML site successfully translates through the monolith.

---

## ARC-006: Create a Clean Community Edition Database Schema

**Priority:** High  
**Dependencies:** ARC-002, ARC-003, ARC-004

### Description

Produce a clean-install schema for Community Edition. Historical private migrations do not need to be preserved because hosted-database migration is out of scope.

### Tasks

- Inventory retained tables and relationships.
- Remove migrations for excluded features.
- Consolidate or squash migrations into a readable Community Edition baseline.
- Add foreign keys, unique constraints, and indexes required by the merged app.
- Remove columns for Google OAuth, plans, billing, quotas, and invitations.
- Replace single project `domain`/`origin` uniqueness assumptions with the accepted-origins relationship.
- Ensure PostgreSQL compatibility.
- Create seeders for required reference data only.
- Do not seed real users, credentials, projects, or provider keys.

### Done Means

An empty PostgreSQL database can be migrated and seeded deterministically without historical private application state.

### Tests

- Run migrations on an empty PostgreSQL database.
- Roll back and migrate again where rollback is supported.
- Validate foreign keys and unique ownership constraints.
- Run schema smoke tests in CI.

### Edge Cases

- PostgreSQL index-name length.
- Enum values stored as strings.
- Settings migrations may run before installation is complete.
- Media-library tables may be optional depending on storage configuration.

### Acceptance Criteria

- [ ] Clean migration succeeds on supported PostgreSQL versions.
- [ ] No excluded table or column remains.
- [ ] Seeders contain no users or secrets.
- [ ] Schema constraints enforce documented invariants.

---

# Epic IAM: Accounts, Roles, and Project Provisioning

## IAM-001: Disable Public Account Creation

**Priority:** Blocker  
**Dependencies:** ARC-003

### Description

Only administrators may create accounts. Remove all public registration and invitation-based account creation paths.

### Tasks

- Remove registration routes and controllers.
- Remove registration links and frontend pages.
- Disable Filament registration.
- Return a controlled 404 or forbidden response for legacy endpoints.
- Ensure API endpoints cannot create users without admin authorization.

### Done Means

An unauthenticated visitor can only sign in or access public installation/health resources.

### Tests

- Attempt GET and POST requests to legacy registration routes.
- Attempt user creation through API mass assignment.
- Verify login remains available after installation.

### Edge Cases

- Installer must still create the first administrator.
- Existing framework routes can re-enable registration accidentally.

### Acceptance Criteria

- [ ] No public registration UI exists.
- [ ] No public endpoint creates users.
- [ ] First-admin creation works only through the installer.

---

## IAM-002: Implement Admin-Managed Users

**Priority:** High  
**Dependencies:** IAM-001

### Description

Provide Filament user management for creating, editing, activating, deactivating, and resetting user passwords.

### Tasks

- Update the Filament user resource for Community Edition.
- Require name, unique email, role, and initial password.
- Support active/inactive status.
- Add an admin password-reset action using a generated or entered temporary password.
- Force password change on next login after an admin reset.
- Prevent an administrator from disabling the last active administrator.
- Record security-relevant admin actions in a local audit log.

### Done Means

Administrators can fully manage accounts without email workflows.

### Tests

- Create users with each supported role.
- Reject duplicate email addresses.
- Reset a password and require change at next login.
- Prevent last-admin deactivation or deletion.
- Ensure non-admin users cannot access user management.

### Edge Cases

- An admin can reset their own password.
- A deactivated user may own projects.
- Concurrent administrators can update the same user.

### Acceptance Criteria

- [ ] Admin can create and manage users.
- [ ] Passwords are never displayed after creation.
- [ ] Reset users must change temporary passwords.
- [ ] Last active administrator protection is enforced.

---

## IAM-003: Implement Self-Service Password Changes

**Priority:** Medium  
**Dependencies:** IAM-002

### Description

Allow authenticated users to change their own passwords without email.

### Tasks

- Retain or implement the profile password form.
- Require current-password confirmation.
- Enforce password policy.
- Revoke other sessions or tokens after password change.
- Clear the forced-password-change flag when successful.

### Done Means

Users can securely change passwords from their profile.

### Tests

- Change password with a correct current password.
- Reject an incorrect current password.
- Reject weak and reused passwords according to policy.
- Verify other sessions are invalidated.

### Edge Cases

- A user with a temporary password must be redirected to password change.
- Session invalidation must not corrupt the current response.

### Acceptance Criteria

- [ ] Current-password verification is required.
- [ ] New password policy is enforced consistently.
- [ ] Other sessions are revoked.

---

## IAM-004: Add Emergency CLI Password Reset

**Priority:** Medium  
**Dependencies:** IAM-002

### Description

Provide an emergency recovery command for server operators when no administrator can sign in.

### Tasks

- Add a namespaced Artisan command such as `weblex:user:reset-password`.
- Require explicit user identification by email.
- Prompt securely for the new password unless supplied through a protected non-interactive mechanism.
- Optionally reactivate an administrator.
- Add audit logging without logging the password.
- Document Docker and traditional-host usage.

### Done Means

A server operator with shell access can recover administrator access without database editing.

### Tests

- Reset an existing user's password.
- Reject unknown and ambiguous users.
- Verify password input is not echoed.
- Verify command output never prints the password.

### Edge Cases

- Non-interactive containers.
- Read-only application filesystem.
- Database unavailable.

### Acceptance Criteria

- [ ] Command works in Docker and traditional installations.
- [ ] Passwords are not exposed in process output or logs.
- [ ] Recovery is documented.

---

## IAM-005: Move Project Creation to Filament Admin

**Priority:** Blocker  
**Dependencies:** IAM-002, ARC-006

### Description

Remove end-user project creation and make project provisioning an administrator responsibility.

### Tasks

- Remove project creation routes and UI from the user dashboard.
- Add or update the Filament project creation workflow.
- Set the creating administrator as project owner.
- Generate the project API key.
- Store an encrypted retrievable key alongside its authentication hash.
- Display the key on authorized project setup and admin detail screens.
- Create default project configuration and language-switcher settings.
- Add API-key rotation in admin.

### Done Means

Only an administrator can create a project, and every project is created with one owner and a usable API key.

### Tests

- Admin creates a project.
- Non-admin project creation is rejected.
- API key is available to authorized users and authenticates API requests.
- The stored key is encrypted at rest.
- Rotating the key invalidates the old key.

### Edge Cases

- The creating administrator may later be deactivated.
- A project may be created before its accepted origins are configured, but its translation API must remain unavailable.

### Acceptance Criteria

- [ ] Project creation exists only in admin.
- [ ] Creator becomes the sole owner.
- [ ] API key storage and rotation are secure.
- [ ] Project defaults support immediate configuration.
- [ ] A project without accepted origins cannot authenticate translation requests.

---

## IAM-006: Replace Invitations with Direct Membership Assignment

**Priority:** High  
**Dependencies:** IAM-002, IAM-005

### Description

Retain project collaboration while removing invitation records and email flows. Administrators assign existing users directly.

### Tasks

- Remove collaborator invitation models, tables, observers, notifications, routes, and UI.
- Simplify the project-user pivot to user, project, and role.
- Add Filament membership management.
- Allow admin to add or remove existing users.
- Support manager, translator, and viewer roles.
- Prevent a second owner assignment.
- Update user-dashboard collaborator views to show direct memberships.

### Done Means

Project membership is managed directly by administrators and does not depend on invitation state.

### Tests

- Assign each supported non-owner role.
- Change a member's role.
- Remove a member.
- Reject assigning a nonexistent user.
- Reject assigning owner through membership management.

### Edge Cases

- Duplicate membership assignment.
- Removing an inactive user.
- A user can be a member of multiple projects.

### Acceptance Criteria

- [ ] No invitation table or workflow remains.
- [ ] Existing users can be assigned directly.
- [ ] Only one owner is possible.
- [ ] Role changes take effect immediately.

---

## IAM-007: Enforce Role Permissions and Single Ownership

**Priority:** Blocker  
**Dependencies:** IAM-005, IAM-006

### Description

Define and enforce authorization consistently across Filament, Inertia controllers, API operations, and background jobs.

### Tasks

- Document the permission matrix for owner, manager, translator, and viewer.
- Update policies and middleware.
- Ensure the owner is the administrator creator.
- Prevent owner removal, transfer, or duplication in the first release.
- Restrict admin panel access to administrators.
- Ensure project membership scopes every query.
- Add authorization checks to exports, glossary changes, translations, languages, and settings.

### Done Means

Every project operation is authorized by one centralized and tested role policy.

### Tests

- Role-permission feature tests for every protected capability.
- Cross-project access tests.
- Direct URL and API-request authorization tests.
- Test inactive users and projects.
- Test queued jobs cannot act outside their project.

### Edge Cases

- Administrator privileges versus project ownership.
- Owner account deactivation.
- Stale cached permissions.
- Route-model binding can expose records before policy checks.

### Acceptance Criteria

- [ ] Permission matrix is documented and tested.
- [ ] Cross-project data access is impossible.
- [ ] Exactly one owner exists per project.
- [ ] Non-admin users cannot access Filament.

---

## IAM-008: Implement Project Accepted-Origin Management

**Priority:** Blocker  
**Dependencies:** IAM-005, ARC-004, ARC-006

### Description

Allow administrators to define one or more exact web origins that may use each project's public API key. A translation request is treated as unauthenticated unless both the API key and request origin match the same active project.

An accepted origin is stored as a normalized tuple of scheme, hostname, and optional port, for example:

- `https://example.com`
- `https://www.example.com`
- `https://shop.example.com`
- `http://localhost:5173`

Paths, query strings, fragments, credentials, and loose substring matching are not allowed. Wildcards are excluded from the first release to keep authorization behavior explicit.

### Tasks

- Add a project accepted-origins table and model.
- Store the original display value and a canonical normalized origin.
- Add a unique constraint for normalized origins within each project.
- Add Filament CRUD for accepted origins on the project resource.
- Allow multiple origins per project.
- Validate `http` and `https` schemes only.
- Permit `http` for explicitly configured local-development origins.
- Normalize hostname case, trailing dots, default ports, IPv4, IPv6, and internationalized domain names.
- Reject paths, queries, fragments, user information, malformed ports, and unsupported schemes.
- Require an exact normalized `Origin` header match in project API authentication middleware.
- Return the same unauthenticated response for invalid API keys, missing origins, and unaccepted origins.
- Do not trust `X-Page-Url`, `Referer`, `Host`, or forwarded headers as substitutes for authorization.
- Validate that `X-Page-Url`, when present, belongs to the authenticated accepted origin before using it for page analytics or persistence.
- Configure CORS so browser preflight can complete, while documenting that CORS is not authorization.
- Add the response CORS headers only as appropriate after the actual request passes accepted-origin authentication.
- Invalidate project configuration cache when accepted origins change.
- Record accepted-origin changes in the local admin audit log.
- Prevent deletion of an origin from affecting unrelated projects.

### Done Means

Administrators can manage multiple accepted origins per project, and both config and translation API endpoints reject requests that do not present an exact accepted `Origin`.

### Tests

- Add, edit, list, and remove accepted origins through Filament.
- Authenticate from each of multiple accepted origins.
- Reject a valid API key from an unaccepted origin.
- Reject missing, `null`, malformed, and unsupported-scheme origins.
- Reject prefix, suffix, and substring attacks such as `example.com.attacker.test`.
- Verify scheme-sensitive matching between HTTP and HTTPS.
- Verify port-sensitive matching for non-default ports.
- Verify default-port normalization for `:80` and `:443`.
- Verify hostname case and trailing-dot normalization.
- Verify internationalized domain-name normalization.
- Verify IPv4, bracketed IPv6, and localhost development origins.
- Reject origins containing a path, query, fragment, or credentials.
- Verify the same unauthenticated response shape for invalid key and invalid origin.
- Verify `X-Page-Url` cannot claim a different origin.
- Verify preflight behavior and confirm that only the actual authenticated request grants API access.
- Verify cache invalidation after adding or removing an origin.

### Edge Cases

- Browser `Origin` can be `null` for sandboxed documents, local files, and privacy-sensitive contexts; it must be rejected unless a future explicit policy is designed.
- Non-browser clients can spoof `Origin`; accepted origins reduce API-key abuse but do not replace server-side rate limiting and monitoring.
- Reverse proxies must not rewrite or inject the application-level `Origin` header.
- `localhost`, `127.0.0.1`, and `[::1]` are different hosts and require separate entries.
- Internationalized domains must compare in one canonical ASCII form.
- Removing the final accepted origin intentionally disables project API access.
- The same origin may be accepted by different projects because API-key matching still selects the project.

### Acceptance Criteria

- [ ] Admin can configure multiple accepted origins per project.
- [ ] Origin matching is exact after canonical normalization.
- [ ] Wildcard and substring matching are not supported.
- [ ] Both config and translation endpoints require an accepted origin.
- [ ] Missing or invalid origins are treated as unauthenticated.
- [ ] Error responses do not reveal whether the API key or origin failed.
- [ ] `X-Page-Url` cannot bypass origin authorization.
- [ ] Accepted-origin changes take effect without restarting the application.

---

# Epic PRV: Translation Provider Redesign

## PRV-001: Define Provider Contracts and Registry

**Priority:** Blocker  
**Dependencies:** ARC-004

### Description

Replace provider-name conditionals and naming ambiguity with explicit provider contracts and a registry.

### Tasks

- Define a translation-provider interface.
- Define provider capabilities such as NMT, LLM, glossary, streaming, and supported options.
- Create stable provider identifiers:
  - `google_cloud`.
  - `openai`.
  - `openrouter`.
  - `gemini`.
  - `qwen`.
- Create a provider registry/factory.
- Remove LibreTranslate from all enums and settings.
- Separate display names from identifiers.
- Define normalized provider exceptions and health status.

### Done Means

The translation pipeline resolves providers through a registry and never treats OpenRouter as OpenAI configuration.

### Tests

- Resolve every supported provider.
- Reject unknown or disabled providers.
- Verify capability declarations.
- Verify provider-specific exceptions are normalized.

### Edge Cases

- OpenRouter and Qwen use OpenAI-compatible HTTP APIs but are distinct products.
- A provider may support LLM translation but not NMT.
- Provider SDK packages can throw incompatible exception types.

### Acceptance Criteria

- [ ] Stable provider IDs are documented.
- [ ] OpenAI and OpenRouter are separate.
- [ ] LibreTranslate is absent.
- [ ] Pipeline code depends on contracts, not provider names.

---

## PRV-002: Create Encrypted Provider Settings

**Priority:** Blocker  
**Dependencies:** PRV-001, SEC-004

### Description

Store provider configuration in the database using encrypted credential fields and provider-specific validation.

### Tasks

- Define settings classes or tables for each provider.
- Encrypt API keys, service-account JSON, and sensitive endpoint credentials.
- Store non-sensitive model, timeout, batch, and endpoint settings separately.
- Add enabled/disabled state.
- Add safe credential replacement behavior.
- Prevent credential hydration back into browser forms.
- Add configuration-version fields if needed for future migrations.

### Done Means

Every provider can be configured without editing `.env`, and credentials are encrypted at rest.

### Tests

- Database encryption tests.
- Credential replacement tests.
- Serialization and form-hydration tests.
- Missing and malformed setting tests.

### Edge Cases

- `APP_KEY` rotation.
- Partial provider configuration.
- Large Google service-account JSON payloads.
- Empty credential submissions should preserve existing credentials when intended.

### Acceptance Criteria

- [ ] Provider credentials are encrypted.
- [ ] UI responses never contain stored secrets.
- [ ] Invalid partial configurations cannot be enabled.
- [ ] Providers can be disabled without deleting credentials.

---

## PRV-003: Build Provider Administration UI

**Priority:** High  
**Dependencies:** PRV-002

### Description

Create browser-based provider configuration for administrators.

### Tasks

- Add a Filament provider settings page.
- Show provider status, capabilities, and configuration requirements.
- Add credential and model fields specific to each provider.
- Add save-and-test actions.
- Display sanitized test errors.
- Show whether a provider is ready, disabled, or misconfigured.
- Restrict access to administrators.

### Done Means

An administrator can configure and test every supported provider without shell access.

### Tests

- Form validation tests per provider.
- Authorization tests.
- Browser tests for credential replacement.
- Test sanitized error rendering.

### Edge Cases

- Network timeout during test.
- Provider accepts credentials but rejects selected model.
- Existing credentials must remain unchanged when a masked field is untouched.

### Acceptance Criteria

- [ ] All provider configuration is available in admin.
- [ ] Test actions provide useful but safe feedback.
- [ ] Secrets are never redisplayed.
- [ ] Non-admin access is denied.

---

## PRV-004: Implement Google Cloud Translation Provider

**Priority:** High  
**Dependencies:** PRV-002

### Description

Retain Google Cloud Translation with browser-managed service-account configuration.

### Tasks

- Accept service-account JSON through admin.
- Validate required JSON fields.
- Store credentials encrypted.
- Support project ID, timeout, concurrency, and batch limits.
- Implement connection/permission testing.
- Preserve NMT translation mapping and error handling.

### Done Means

Google Cloud Translation works without credential files on disk or environment variables.

### Tests

- Unit tests using mocked Google clients.
- Invalid JSON and missing field tests.
- Permission and quota-error mapping tests.
- Batch mapping and language-code tests.

### Edge Cases

- Base64 versus raw JSON input from the previous implementation.
- Service account without Translation API permission.
- Google project ID mismatch.

### Acceptance Criteria

- [ ] Raw service-account JSON can be configured safely.
- [ ] Provider health test verifies usable access.
- [ ] Translation results preserve item IDs and ordering.

---

## PRV-005: Implement OpenAI Provider

**Priority:** High  
**Dependencies:** PRV-002

### Description

Implement OpenAI as a first-class provider using OpenAI-specific defaults and naming.

### Tasks

- Add OpenAI API key and optional organization/project configuration.
- Use the official OpenAI API base URL by default.
- Add model selection and configurable timeout/batching.
- Validate structured translation output.
- Add provider-specific health testing.
- Remove OpenRouter defaults from OpenAI settings.

### Done Means

OpenAI configuration cannot silently route to OpenRouter or another compatible endpoint.

### Tests

- Mocked successful and failed responses.
- Invalid key and model tests.
- Malformed structured output tests.
- Timeout and retry tests.

### Edge Cases

- Model availability differs by account.
- Rate limits and transient provider failures.
- Response may contain markdown around JSON.

### Acceptance Criteria

- [ ] OpenAI defaults point only to OpenAI.
- [ ] OpenAI settings are independent.
- [ ] Translation failures are normalized and sanitized.

---

## PRV-006: Implement OpenRouter Provider

**Priority:** High  
**Dependencies:** PRV-002

### Description

Create a distinct OpenRouter provider even though its protocol is OpenAI-compatible.

### Tasks

- Add separate OpenRouter key, endpoint, model, and optional attribution headers.
- Add OpenRouter-specific model validation and health testing.
- Keep its settings independent from OpenAI.
- Document model identifiers and cost responsibility.
- Ensure UI labels never call OpenRouter “OpenAI.”

### Done Means

OpenRouter can be enabled, configured, selected, and tested without changing OpenAI configuration.

### Tests

- Provider-registry separation test.
- OpenRouter request-header tests.
- Invalid model and key tests.
- Verify OpenAI credentials are never used.

### Edge Cases

- OpenRouter models can be removed or renamed.
- Upstream provider errors can use nested error formats.
- Free models can be unavailable.

### Acceptance Criteria

- [ ] OpenRouter has a unique provider ID.
- [ ] Credentials and models are independent from OpenAI.
- [ ] Provider errors identify OpenRouter accurately.

---

## PRV-007: Implement Gemini Provider

**Priority:** High  
**Dependencies:** PRV-002

### Description

Move Gemini configuration entirely into browser-managed provider settings.

### Tasks

- Remove Gemini key and model dependence on `config/ai.php` environment values.
- Add key, model, timeout, concurrency, and batch settings.
- Preserve structured response handling.
- Add health testing.
- Declare Gemini capabilities accurately.

### Done Means

Gemini works from encrypted database configuration with no required Gemini `.env` variables.

### Tests

- Mocked Gemini translation tests.
- Missing key and invalid model tests.
- Structured-output mismatch tests.
- Timeout tests.

### Edge Cases

- Preview model identifiers can change.
- Safety filters can block translations.
- Translation count can differ from input count.

### Acceptance Criteria

- [ ] Gemini is fully configured in the admin UI.
- [ ] No Gemini secret is required in `.env`.
- [ ] Invalid structured output is handled predictably.

---

## PRV-008: Implement Qwen Provider

**Priority:** High  
**Dependencies:** PRV-002

### Description

Move Qwen configuration into browser-managed settings and treat Qwen as a distinct provider.

### Tasks

- Add Qwen key, region/endpoint, model, timeout, and batch settings.
- Remove hardcoded Qwen endpoint defaults where region-specific.
- Implement provider health testing.
- Keep Qwen credentials independent from OpenAI and OpenRouter.
- Document supported Qwen translation models.

### Done Means

Qwen can be configured and selected independently through admin.

### Tests

- Mocked Qwen request and response tests.
- Endpoint and region validation tests.
- Invalid key, model, and output tests.

### Edge Cases

- Regional endpoints may require different URLs.
- API compatibility may differ from OpenAI in small ways.
- Model-specific translation parameters.

### Acceptance Criteria

- [ ] Qwen has independent settings and provider ID.
- [ ] Endpoint is configurable.
- [ ] Provider test verifies credentials and model access.

---

## PRV-009: Implement Project Provider Selection

**Priority:** Blocker  
**Dependencies:** PRV-004, PRV-005, PRV-006, PRV-007, PRV-008

### Description

Allow administrators to choose enabled translation providers per project without plan restrictions.

### Tasks

- Define project-level NMT and LLM provider fields.
- Filter selection to enabled and capability-compatible providers.
- Add model/options fields only where project overrides are allowed.
- Define fallback behavior explicitly; do not silently switch providers.
- Add validation when disabling a provider used by projects.
- Update translation pipeline provider resolution.

### Done Means

Each project has valid provider selections and translation requests use exactly those providers.

### Tests

- Select every supported provider.
- Reject disabled or incompatible providers.
- Test provider deletion/disable while in use.
- Verify no silent fallback occurs.

### Edge Cases

- NMT and LLM may use the same provider.
- A selected provider becomes invalid after credential rotation.
- Existing project configuration may reference removed provider IDs.

### Acceptance Criteria

- [ ] Provider selection is explicit per project.
- [ ] Only configured providers can be selected.
- [ ] Failures identify the configured provider.
- [ ] No plan or subscription influences selection.

---

# Epic CAC: Cache Architecture

## CAC-001: Define Translation Cache Contracts

**Priority:** Blocker  
**Dependencies:** ARC-004

### Description

Remove direct Redis operations from translation-domain code by introducing cache contracts based on application behavior.

### Tasks

- Define translation cache store and invalidation interfaces.
- Include operations for get, get-many, put, put-many, forget item, forget page, and forget project.
- Define stable cache keys and namespaces.
- Separate config cache, glossary cache, and translation cache concerns.
- Define TTL semantics.
- Bind contracts through a service provider.

### Done Means

No translation pipeline, controller, model observer, or job directly calls the Redis facade.

### Tests

- Contract tests using an in-memory fake.
- Cache-key collision tests.
- TTL behavior tests.
- Invalidation-scope tests.

### Edge Cases

- Text-hash algorithm changes.
- Multiple installations sharing one Redis server.
- Project deletion with large cache volume.

### Acceptance Criteria

- [ ] Domain code depends only on cache interfaces.
- [ ] Keys are installation- and project-scoped.
- [ ] Cache semantics are documented.

---

## CAC-002: Implement the Redis Cache Driver

**Priority:** Blocker  
**Dependencies:** CAC-001

### Description

Implement the initial production cache driver using Redis while hiding Redis-specific hash and scan behavior.

### Tasks

- Implement efficient bulk reads and writes.
- Implement scoped invalidation without unsafe global scans.
- Use configurable Redis connections and prefixes.
- Add serialization versioning.
- Add connection and health checks.
- Add metrics/logging for cache failures without logging content.

### Done Means

Redis remains the required first-release backend but is isolated behind cache contracts.

### Tests

- Integration tests against real Redis.
- Bulk read/write tests.
- Expiration tests.
- Concurrent update tests.
- Project and page invalidation tests.
- Redis-unavailable behavior tests.

### Edge Cases

- Redis cluster compatibility.
- Prefix behavior differs between clients.
- Large hashes can cause latency.
- Partial failures during bulk writes.

### Acceptance Criteria

- [ ] Real Redis integration tests pass.
- [ ] No Redis facade is used outside infrastructure classes.
- [ ] Cache failure behavior is defined and non-destructive.

---

## CAC-003: Refactor All Cache Consumers

**Priority:** High  
**Dependencies:** CAC-002

### Description

Move translation, project configuration, glossary, and observer invalidation logic to the new cache abstractions.

### Tasks

- Refactor translation lookup and storage pipeline stages.
- Refactor project configuration caching.
- Refactor glossary caching.
- Refactor observers and admin actions that invalidate caches.
- Remove SmartCache usage if it is no longer required.
- Remove obsolete Redis database-number assumptions.

### Done Means

All retained cache behavior is implemented through dedicated interfaces and covered by integration tests.

### Tests

- End-to-end cache-hit and cache-miss tests.
- Editing a translation invalidates the correct entry.
- Editing project languages invalidates config.
- Editing glossaries invalidates affected translations.

### Edge Cases

- Concurrent translation and admin edits.
- Deleting pages or languages.
- Cache invalidation after a failed transaction.

### Acceptance Criteria

- [ ] Cache invalidation follows database commits.
- [ ] Unrelated projects are not invalidated.
- [ ] Obsolete cache dependencies are removed.

---

# Epic INS: Browser Installer and Configuration

## INS-001: Design Installation State and Locking

**Priority:** Blocker  
**Dependencies:** ARC-006

### Description

Create a secure installation state machine that exposes `/install` only before installation is complete.

### Tasks

- Define installation steps and persisted progress.
- Add middleware redirecting uninstalled instances to `/install`.
- Add a durable installation lock.
- Prevent installer access after completion.
- Define an explicit CLI recovery/unlock procedure.
- Ensure the installer does not expose stack traces.

### Done Means

Installation can be completed once, resumed after safe failures, and cannot be reopened by unauthenticated users afterward.

### Tests

- Fresh-instance redirect tests.
- Completed-instance installer denial tests.
- Interrupted installation resume tests.
- Concurrent installer request tests.

### Edge Cases

- Multiple replicas start before installation completes.
- Filesystem is read-only.
- Database is reachable but migrations fail.
- Lock exists but administrator creation failed.

### Acceptance Criteria

- [ ] Installer is available only when appropriate.
- [ ] Installation is resumable.
- [ ] Concurrent setup cannot create duplicate administrators.
- [ ] Unlock requires server-level access.

---

## INS-002: Implement Requirements and Connectivity Checks

**Priority:** High  
**Dependencies:** INS-001

### Description

Provide understandable browser checks for developers who do not know Laravel internals.

### Tasks

- Check PHP version and required extensions.
- Check writable directories and `.env` permissions.
- Check PostgreSQL connectivity and supported version.
- Check Redis connectivity and supported version.
- Check application URL and HTTPS recommendations.
- Check queue/scheduler readiness where detectable.
- Present remediation guidance.

### Done Means

The installer identifies missing infrastructure before writing configuration or running migrations.

### Tests

- Simulate missing PHP extensions.
- Test invalid PostgreSQL and Redis credentials.
- Test unreachable hosts and timeouts.
- Test insufficient filesystem permissions.

### Edge Cases

- Database passwords containing special characters.
- IPv6 hosts and Unix sockets.
- Managed Redis requiring TLS.
- Reverse proxy headers.

### Acceptance Criteria

- [ ] Every mandatory requirement has a clear pass/fail result.
- [ ] Failures provide actionable guidance.
- [ ] Secrets are not echoed in error messages.

---

## INS-003: Implement Safe `.env` Generation

**Priority:** Blocker  
**Dependencies:** INS-002, SEC-004

### Description

Allow the installer to write infrastructure configuration to `.env` safely.

### Tasks

- Generate `APP_KEY`.
- Write app URL, environment, PostgreSQL, Redis, cache, queue, session, and storage values.
- Correctly quote and escape special characters.
- Use atomic writes and backups.
- Preserve unknown existing keys where safe.
- Validate generated configuration before replacing the file.
- Handle containers where `.env` is mounted read-only with clear instructions.

### Done Means

Installer-generated `.env` is syntactically valid, secure, and usable after process restart.

### Tests

- Property-style tests for special characters.
- Atomic-write interruption tests.
- Existing `.env` merge tests.
- Read-only filesystem tests.
- Config-cache rebuild tests.

### Edge Cases

- Newlines and quotes in credentials.
- Concurrent writes.
- Docker environments that inject variables instead of using `.env`.
- `APP_KEY` already exists.

### Acceptance Criteria

- [ ] Generated `.env` loads successfully.
- [ ] Writes are atomic.
- [ ] Existing valid `APP_KEY` is not replaced accidentally.
- [ ] Sensitive values are never rendered after submission.

---

## INS-004: Implement Migration and First-Admin Setup

**Priority:** Blocker  
**Dependencies:** INS-003, IAM-002

### Description

Run database setup and create the first administrator through the browser.

### Tasks

- Run migrations and required reference seeders.
- Create the first administrator with validated credentials.
- Assign the admin role.
- Require a strong password.
- Mark installation complete only after transactionally successful setup.
- Sign in or redirect to login after completion.

### Done Means

A new installation ends with one working administrator and no seeded credentials.

### Tests

- Complete fresh installation.
- Reject duplicate email.
- Reject weak passwords.
- Simulate migration and user-creation failures.
- Verify no installation lock is set after partial failure.

### Edge Cases

- Migration succeeds but administrator creation fails.
- Browser refresh during setup.
- Two concurrent completion requests.

### Acceptance Criteria

- [ ] Exactly one initial administrator is created.
- [ ] No default password exists.
- [ ] Partial failures are recoverable.
- [ ] Installer locks only after successful completion.

---

## INS-005: Add Initial Application and Storage Configuration

**Priority:** High  
**Dependencies:** INS-004

### Description

Collect required non-provider application settings and configure storage during installation.

### Tasks

- Configure application name, URL, locale, and timezone.
- Configure local storage by default.
- Offer S3-compatible storage fields.
- Offer Cloudinary fields as an optional driver.
- Validate selected storage.
- Defer provider configuration when desired, but clearly mark translation as unavailable.

### Done Means

The installed application has valid general and storage configuration without requiring manual config edits.

### Tests

- Local storage write/read test.
- Mock S3-compatible configuration test.
- Mock Cloudinary configuration test.
- Validate optional and required fields per driver.

### Edge Cases

- S3 path-style endpoints.
- Cloudinary unavailable during installation.
- Local storage symlink permissions.

### Acceptance Criteria

- [ ] Local storage works by default.
- [ ] Optional drivers can be configured in browser.
- [ ] Invalid storage configuration cannot be activated.

---

# Epic DEP: Deployment and Operations

## DEP-001: Build Production FrankenPHP Image

**Priority:** Blocker  
**Dependencies:** ARC-005

### Description

Create a reproducible multi-stage production image for the monolith.

### Tasks

- Pin supported base-image versions.
- Install required PHP extensions.
- Build Composer dependencies without development packages.
- Build dashboard and SDK assets.
- Run as a non-root user where feasible.
- Add health and readiness checks.
- Support app, worker, scheduler, and updater roles.
- Avoid automatically running unsafe migrations in every replica.

### Done Means

One immutable image can run all required application roles.

### Tests

- Build image in CI.
- Scan image for critical vulnerabilities.
- Start each role.
- Validate health endpoints.
- Confirm no source credentials are present in layers.

### Edge Cases

- Multi-architecture builds.
- Storage permissions.
- FrankenPHP worker mode and long-lived settings.
- Migration concurrency.

### Acceptance Criteria

- [ ] Image builds reproducibly.
- [ ] All roles start correctly.
- [ ] Critical image vulnerabilities are resolved or documented.
- [ ] Container contains no development secrets.

---

## DEP-002: Create Production Docker Compose Stack

**Priority:** Blocker  
**Dependencies:** DEP-001, CAC-002

### Description

Provide the primary `git clone && docker compose up` installation path.

### Tasks

- Add app, worker, scheduler, PostgreSQL, and Redis services.
- Add persistent volumes.
- Add health checks and startup dependencies.
- Add environment templates.
- Add reverse-proxy guidance.
- Ensure only required ports are exposed.
- Add backup-friendly volume naming.
- Add install and upgrade commands.

### Done Means

A clean Linux host with Docker can start the entire stack and complete browser installation.

### Tests

- End-to-end clean Compose installation.
- Restart and persistence tests.
- Dependency recovery tests.
- Upgrade smoke test.
- Network exposure audit.

### Edge Cases

- Port conflicts.
- ARM64 hosts.
- Existing PostgreSQL/Redis services.
- Docker Compose version differences.

### Acceptance Criteria

- [ ] `docker compose up -d` starts a healthy stack.
- [ ] Data survives restart and image replacement.
- [ ] Browser installer completes successfully.
- [ ] Database and Redis are not publicly exposed by default.

---

## DEP-003: Document Traditional Linux Hosting

**Priority:** High  
**Dependencies:** DEP-001, INS-004

### Description

Document a supported non-Docker installation using FrankenPHP, PostgreSQL, Redis, and process supervision.

### Tasks

- Document system prerequisites.
- Document source installation and build steps.
- Provide FrankenPHP/Caddy configuration.
- Provide systemd units for app, Horizon, and scheduler where appropriate.
- Document filesystem ownership and permissions.
- Document reverse proxy, TLS, backups, log rotation, and upgrades.
- Include Ubuntu LTS as the reference platform.

### Done Means

A competent Linux administrator can deploy without Docker using only public documentation.

### Tests

- Perform installation on a clean supported Ubuntu VM.
- Verify restart after reboot.
- Verify worker and scheduler processing.
- Verify TLS and streaming response behavior.

### Edge Cases

- Shared hosting without process supervision is not supported.
- SELinux/AppArmor environments.
- Proxy buffering can break NDJSON streaming.

### Acceptance Criteria

- [ ] Reference installation works on a clean Ubuntu VM.
- [ ] Required long-running processes are supervised.
- [ ] Streaming API works through the documented web stack.

---

## DEP-004: Add Backup and Restore Procedures

**Priority:** High  
**Dependencies:** DEP-002, DEP-003

### Description

Document and automate backup of all state required to recover an installation.

### Tasks

- Define backup scope: PostgreSQL, storage, `.env`, and `APP_KEY`.
- Add Docker backup examples/scripts.
- Add traditional-host backup examples/scripts.
- Document encrypted provider credential dependency on `APP_KEY`.
- Add restore verification procedure.
- Define upgrade-time backup requirements.

### Done Means

An installation can be restored to a new host without losing encrypted provider settings or project data.

### Tests

- Back up a populated test installation.
- Restore to a clean host.
- Verify login, projects, media, and provider settings.

### Edge Cases

- Restoring database without matching `APP_KEY`.
- Inconsistent database and filesystem snapshots.
- Large translation tables.

### Acceptance Criteria

- [ ] Backup and restore are documented for both deployment paths.
- [ ] A tested restore preserves encrypted settings.
- [ ] Upgrade workflow requires or verifies a recent backup.

---

# Epic UPD: Release and One-Click Updates

## UPD-001: Define Versioning and Signed Release Metadata

**Priority:** Blocker  
**Dependencies:** SEC-003, DEP-001

### Description

Create a trustworthy release feed used for update notifications and automated updates.

### Tasks

- Adopt semantic versioning.
- Publish changelogs and upgrade notes.
- Define supported release channels, initially stable only.
- Publish signed release metadata.
- Include minimum PHP, database, Redis, and schema requirements.
- Verify signatures in the application.
- Cache update checks without telemetry or installation identifiers.

### Done Means

The application can determine whether an authentic compatible update exists without sending identifying data.

### Tests

- Valid and invalid signature tests.
- Downgrade and replay protection tests.
- Offline and malformed feed tests.
- Version comparison tests.

### Edge Cases

- Compromised release hosting.
- Clock skew.
- Withdrawn releases.
- Pre-release versions.

### Acceptance Criteria

- [ ] Release metadata is signed.
- [ ] Update checks send no installation telemetry.
- [ ] Invalid metadata is ignored and logged.

---

## UPD-002: Implement Admin Update Notifications

**Priority:** High  
**Dependencies:** UPD-001

### Description

Notify administrators when a compatible stable update is available.

### Tasks

- Add an admin update-status page or widget.
- Show current and latest versions.
- Show release notes, security classification, and prerequisites.
- Allow update checks to be manually triggered.
- Add configurable automatic check frequency.
- Do not notify non-admin users.

### Done Means

Administrators receive useful update information without telemetry.

### Tests

- No-update, update-available, security-update, and offline tests.
- Authorization tests.
- Cached feed expiration tests.

### Edge Cases

- Installation is ahead of stable release.
- Update is incompatible with current runtime.
- Feed temporarily unavailable.

### Acceptance Criteria

- [ ] Only compatible authentic releases are displayed.
- [ ] No installation identifier is transmitted.
- [ ] Update checks cannot block normal requests.

---

## UPD-003: Design a Secure Update Driver Architecture

**Priority:** Blocker  
**Dependencies:** UPD-001, DEP-002, DEP-003

### Description

Define separate update drivers for Docker and traditional installations. The web container must not receive unrestricted access to the host Docker socket.

### Tasks

- Threat-model update execution.
- Define a common update state machine.
- Define traditional-host release switching and rollback.
- Define a narrowly scoped Docker update agent or another safe orchestration mechanism.
- Define maintenance mode, migration, health-check, and rollback behavior.
- Define update locks and concurrent-update prevention.
- Obtain security review before implementation.

### Done Means

There is an approved design that supports one-click initiation without granting the web application unrestricted host control.

### Tests

- Architecture-level threat cases.
- Interrupted update scenarios.
- Malicious release metadata scenarios.
- Concurrent update request scenarios.

### Edge Cases

- Docker socket access can compromise the host.
- Database migrations may not be reversible.
- Worker processes can continue running old code.
- Multi-node installations require coordination.

### Acceptance Criteria

- [ ] Web process has no unrestricted Docker socket.
- [ ] Update driver is deployment-aware.
- [ ] Backup, health check, and rollback behavior are defined.
- [ ] Security review approves the design.

---

## UPD-004: Implement Traditional-Hosting One-Click Updates

**Priority:** High  
**Dependencies:** UPD-003, DEP-004

### Description

Implement atomic, release-based updates for supported traditional installations.

### Tasks

- Download and verify signed release artifacts.
- Create versioned release directories.
- Install production dependencies and build or include assets.
- Enter maintenance mode.
- Back up required state.
- Run migrations once.
- Switch the active symlink atomically.
- Restart app, worker, and scheduler processes.
- Run health checks and roll back code on failure.

### Done Means

An administrator can initiate an update in the browser and the traditional installation safely switches releases.

### Tests

- Successful update.
- Signature failure.
- Download interruption.
- Dependency failure.
- Migration failure.
- Health-check failure and rollback.
- Concurrent update rejection.

### Edge Cases

- Irreversible database migrations.
- Insufficient disk space.
- Files modified locally.
- Worker jobs running during switch.

### Acceptance Criteria

- [ ] Release signatures are verified.
- [ ] Code switch is atomic.
- [ ] Failed updates leave the previous release operational when possible.
- [ ] Detailed sanitized update logs are available to admins.

---

## UPD-005: Implement Docker One-Click Updates

**Priority:** High  
**Dependencies:** UPD-003, DEP-002, DEP-004

### Description

Implement Docker updates through the approved scoped update mechanism.

### Tasks

- Build a minimal update agent or selected orchestration integration.
- Authenticate requests from the application.
- Restrict operations to the WeblexAI Compose project.
- Pull signed/versioned images rather than mutable `latest` only.
- Back up state before update.
- Coordinate app, worker, scheduler, and migration execution.
- Run health checks.
- Roll back image versions on failure where schema compatibility allows.

### Done Means

An administrator can initiate a Docker update without exposing general host-control capabilities to the application.

### Tests

- Successful image update.
- Unauthorized update-agent request.
- Attempted operation outside the WeblexAI project.
- Pull failure.
- Migration and health-check failures.
- Rollback test.

### Edge Cases

- Registry unavailable.
- Host restarts during update.
- Image rollback with migrated schema.
- Custom Compose project names.

### Acceptance Criteria

- [ ] Update agent cannot control unrelated containers.
- [ ] Images use immutable version tags or digests.
- [ ] Update status is visible in admin.
- [ ] Failure behavior is documented and tested.

---

# Epic DOC: Documentation, Support, and Contributions

## DOC-001: Write the Main README and Quick Start

**Priority:** High  
**Dependencies:** DEP-002

### Description

Create a concise public landing document for developers evaluating or installing Community Edition.

### Tasks

- Explain what WeblexAI Community Edition does.
- Explain Community versus managed/commercial offerings without feature ambiguity.
- Add architecture overview.
- Add Docker quick start.
- Link traditional installation.
- List supported providers and infrastructure.
- Add screenshots and SDK usage example.
- Link license, trademark, support, security, and contribution policies.

### Done Means

A new developer can understand the product and reach the installer from the README.

### Tests

- Validate every command on a clean environment.
- Check all links.
- Review screenshots for secrets and private data.

### Edge Cases

- Documentation must not promise unsupported platforms.
- Branding must distinguish official builds from forks.

### Acceptance Criteria

- [ ] Quick start is tested.
- [ ] Requirements and exclusions are explicit.
- [ ] No private URLs or credentials appear.

---

## DOC-002: Create Operations and Troubleshooting Documentation

**Priority:** Medium  
**Dependencies:** DEP-002, DEP-003, DEP-004

### Description

Document routine operation for self-hosters.

### Tasks

- Document logs, health checks, queues, scheduler, and cache.
- Document provider troubleshooting.
- Document SDK integration and project accepted-origin configuration.
- Document backups, restore, upgrades, and rollback.
- Document password recovery.
- Document common proxy buffering and CORS issues.
- Document scaling boundaries for the first release.

### Done Means

Common operational problems can be diagnosed without private support.

### Tests

- Follow troubleshooting steps against intentionally broken test deployments.
- Validate commands for both deployment paths.

### Edge Cases

- Commands differ between Docker and traditional installations.
- Proxy vendors use different buffering controls.

### Acceptance Criteria

- [ ] Common failure modes have actionable runbooks.
- [ ] Docker and traditional commands are clearly separated.
- [ ] Recovery steps avoid destructive defaults.

---

## DOC-003: Publish Community Support and Security Policies

**Priority:** High  
**Dependencies:** SEC-003

### Description

Set expectations for free community support and provide a private vulnerability-reporting path.

### Tasks

- Add `SUPPORT.md`.
- Use GitHub Issues for reproducible bugs and feature requests.
- Use GitHub Discussions for questions and community help.
- State that free support has no guaranteed response time.
- Add `SECURITY.md` with supported versions and private reporting instructions.
- Define vulnerability disclosure and patch handling.
- Link managed/commercial support separately.

### Done Means

Users know where to ask questions, report bugs, and privately disclose vulnerabilities.

### Tests

- Validate contact methods.
- Run a tabletop security-report workflow.
- Check issue templates link to the correct channels.

### Edge Cases

- Security reports must not be filed publicly.
- Commercial support wording must not imply reduced open-source security.

### Acceptance Criteria

- [ ] Support boundaries are explicit.
- [ ] A private security channel works.
- [ ] Supported versions are documented.

---

## DOC-004: Add Contribution Guidelines and CLA Workflow

**Priority:** High  
**Dependencies:** SEC-003

### Description

Allow outside contributions while preserving WeblexAI's ability to use contributions in Community and commercial editions.

### Tasks

- Add `CONTRIBUTING.md`.
- Define coding, testing, commit, and review expectations.
- Prepare individual and corporate CLA text with legal counsel.
- Add CLA automation to pull requests.
- Define contributor certificate and patent terms.
- Add pull-request and issue templates.
- Document that contributors retain copyright while granting required rights.

### Done Means

Pull requests cannot be merged until required contribution terms and quality checks are satisfied.

### Tests

- Open test pull requests from signed and unsigned contributors.
- Verify corporate contribution flow.
- Verify CLA status survives amended pull requests.

### Edge Cases

- Contributions made before CLA automation.
- Contributors acting for employers.
- Bot-generated dependency updates.

### Acceptance Criteria

- [ ] CLA text has legal approval.
- [ ] CLA check is required for merge.
- [ ] Contribution workflow is documented and tested.

---

# Epic QUA: Quality, CI, and Release Readiness

## QUA-001: Establish Automated Quality Gates

**Priority:** Blocker  
**Dependencies:** ARC-001

### Description

Create deterministic CI that runs without private infrastructure.

### Tasks

- Add PHP formatting and static analysis.
- Add PHP unit and feature tests.
- Add frontend lint, type-check, formatting, and build checks.
- Add SDK unit tests.
- Start PostgreSQL and Redis services in CI.
- Add secret, dependency, and license scans.
- Build the production image.
- Cache dependencies safely.

### Done Means

Every pull request receives repeatable quality results from a clean environment.

### Tests

- Intentionally fail each quality gate in a test branch.
- Run CI from a fork without private secrets.
- Verify no step requires commercial infrastructure.

### Edge Cases

- Provider integration tests must use mocks unless explicitly marked.
- Flaky streaming and timing tests.
- Composer custom repositories can become unavailable.

### Acceptance Criteria

- [ ] CI passes from a fork with no secrets.
- [ ] PostgreSQL and Redis tests run.
- [ ] Production image builds.
- [ ] Required checks block merging.

---

## QUA-002: Build Clean-Install End-to-End Tests

**Priority:** Blocker  
**Dependencies:** INS-005, DEP-002

### Description

Test the complete first-time user path from an empty environment.

### Tasks

- Start a fresh Compose stack.
- Complete browser installer.
- Create the first admin.
- Configure a mocked provider.
- Create a user and project.
- Assign project membership.
- Add languages.
- Integrate the SDK into a fixture site.
- Request and render translations.

### Done Means

The primary Community Edition workflow is automatically validated from an empty machine state.

### Tests

- The end-to-end workflow itself.
- Repeat with installation interruption and restart.
- Verify no outbound telemetry.
- Verify no email or excluded service is required.

### Edge Cases

- Browser installer and app on different hostnames during CI.
- Streaming support in test proxies.
- Generated API key is available on authorized setup screens.

### Acceptance Criteria

- [ ] Clean installation succeeds automatically.
- [ ] First translation succeeds.
- [ ] Excluded integrations are not contacted.
- [ ] Test starts from empty volumes.

---

## QUA-003: Add API and SDK Contract Tests

**Priority:** High  
**Dependencies:** ARC-004, ARC-005

### Description

Protect the contract between the embedded SDK and the translation API.

### Tasks

- Define config-response schema.
- Define NDJSON event schemas.
- Define authentication and error responses.
- Add server-side contract tests.
- Add SDK parser tests using recorded fixtures.
- Version the contract where future breaking changes are possible.

### Done Means

API changes that would break the SDK fail CI before release.

### Tests

- Config, batch, complete, and error event tests.
- Partial and malformed stream tests.
- Unsupported contract-version tests.
- Large and duplicate text-node tests.

### Edge Cases

- Stream lines split across network chunks.
- Completion after partial success.
- Browser abort during translation.

### Acceptance Criteria

- [ ] Schemas are documented.
- [ ] Server and SDK fixtures agree.
- [ ] Breaking contract changes require explicit version handling.

---

## QUA-004: Perform Security Hardening Review

**Priority:** Blocker  
**Dependencies:** IAM-007, IAM-008, PRV-009, INS-004, UPD-003

### Description

Review the release candidate across authentication, authorization, secrets, installer, API, uploads, streaming, and updater surfaces.

### Tasks

- Review project API-key handling.
- Review accepted-origin normalization, authorization, and dynamic CORS.
- Review Filament and Inertia authorization.
- Review installer locking and `.env` writes.
- Review provider secret handling.
- Review file uploads and storage drivers.
- Review rate limits and abuse controls.
- Review update signatures and update-agent permissions.
- Run dependency and container scans.
- Resolve high and critical findings.

### Done Means

The release candidate has no unresolved critical or high-severity security finding.

### Tests

- IDOR and cross-project authorization tests.
- CSRF and session-fixation tests.
- API-key brute-force and rate-limit tests.
- Malicious provider response tests.
- Malicious archive/update tests.
- Upload content-type and path traversal tests.

### Edge Cases

- API keys are intentionally used in public website JavaScript and must be bound to exact project accepted origins.
- Origin headers can be forged by non-browser clients, so rate limiting and abuse controls remain mandatory.
- Browser preflight requests do not contain the bearer-token value; authorization is enforced on the actual request, not inferred from preflight.
- Reverse proxies can spoof headers if trusted-proxy configuration is unsafe.
- One-click update is a privileged remote-code installation path.

### Acceptance Criteria

- [ ] Security checklist is complete.
- [ ] High and critical findings are resolved.
- [ ] Residual risks are documented.
- [ ] Security reporting path is active.

---

## QUA-005: Prepare and Validate the First Public Release

**Priority:** Blocker  
**Dependencies:** All release-blocking tickets

### Description

Create the first public release only after installation, operation, security, and legal validation.

### Tasks

- Select version `1.0.0` or a documented pre-release version.
- Freeze and review dependencies.
- Generate release notes and checksums.
- Publish signed source and container artifacts.
- Verify Apache, trademark, third-party notices, and CLA setup.
- Run Docker and traditional installation acceptance tests.
- Run backup/restore and update tests.
- Perform final secret scan.
- Publish support and security channels.

### Done Means

The public release is reproducible, installable, documented, legally reviewed, and supportable.

### Tests

- Full release-candidate test matrix.
- Artifact signature and checksum verification.
- Installation from published artifacts, not local source.
- Upgrade from the previous release candidate.

### Edge Cases

- Container and source artifact version mismatch.
- Release metadata published before artifacts are available.
- Documentation references unreleased behavior.

### Acceptance Criteria

- [ ] Docker installation passes from published artifacts.
- [ ] Traditional installation passes from published artifacts.
- [ ] No unresolved blocker exists.
- [ ] Final secret scan is clean.
- [ ] Release artifacts and metadata are signed.

---

## 6. Recommended Ticket Order

The following order minimizes rework:

1. SEC-001 through SEC-004.
2. ARC-001 through ARC-006.
3. IAM-001 through IAM-008.
4. PRV-001 through PRV-009.
5. CAC-001 through CAC-003.
6. INS-001 through INS-005.
7. DEP-001 through DEP-004.
8. UPD-001 through UPD-005.
9. DOC-001 through DOC-004.
10. QUA-001 should begin during ARC work; complete QUA-002 through QUA-005 before release.

## 7. Release Definition of Done

WeblexAI Community Edition is ready for its first public release when:

- A clean repository and complete credential rotation are verified.
- Apache-2.0, trademark, notices, support, security, and contribution documents are published.
- A clean Docker installation requires only documented inputs.
- A traditional Linux installation has been independently reproduced.
- The browser installer creates the first administrator and locks itself.
- Administrators can create users and projects.
- Administrators can manage multiple exact accepted origins for every project.
- Translation config and translation requests require a valid project API key and accepted origin.
- Every project has exactly one owner.
- Existing users can be assigned manager, translator, or viewer roles without invitations.
- Billing, subscriptions, plans, quotas, email, OAuth, analytics, Telegram, and LibreTranslate are absent.
- Google Cloud Translation, OpenAI, OpenRouter, Gemini, and Qwen are independently configurable through admin.
- Provider credentials are encrypted and never returned to the browser.
- The translation API and browser SDK operate from the same application.
- Redis access is isolated behind cache contracts.
- PostgreSQL and Redis are the documented and tested production stores.
- Backups and restores preserve encrypted settings.
- Update notifications work without telemetry.
- One-click update paths pass security and rollback tests.
- CI passes from a public fork without private secrets.
- Clean-install, API contract, SDK, authorization, and security tests pass.
- No critical or high-severity security issue remains unresolved.

## 8. Planning Notes

- Tickets marked **Blocker** should not be deferred past the first release.
- One-click updates are intentionally split into design and implementation tickets because they introduce host-level privilege and supply-chain risk.
- The first public release should favor a smaller reliable feature set over compatibility layers for removed private functionality.
- Because private database migration is out of scope, schema cleanup should optimize for maintainability rather than preserve every historical migration.
- Provider integration tests should use mocks in normal CI. Optional live-provider smoke tests may run manually with dedicated test credentials.
- Future cache drivers should be added only after the Redis contract and conformance tests are stable.
