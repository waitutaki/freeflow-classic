# Global Rules — MUST / MUST NOT (Applies to ALL Phases)

## Platform, deps, and assets
**MUST**
- Use PHP 8.2+ only.
- Fully bootstrap 5 compliant
- Keep everything self-contained in the repo.
- Use PHP + XML only for core structure and manifests.

**MUST NOT**
- Use Composer.
- Use external libraries/assets at runtime.
- Use JSON anywhere (files, APIs, encoding/decoding), except for 
reCAPTCHA/hCaptcha internals.

## Architecture and app boundaries
**MUST**
- Follow MVC.
- Keep Site, Admin, and Installer as fully separate applications.

**MUST NOT**
- Mix runtime concerns across boundaries.

## Installer isolation
**MUST**
- Allow installer-only DB connection.
- Replace #_ table prefix placeholders during install.
- Write only DB configuration output.

**MUST NOT**
- Load or modify core, admin, or site runtime.
- Create databases.
- Write any files beyond DB config.

## Database rules
**MUST**
- Have exactly one runtime DB connection routine.
- Use #_ placeholders in all runtime SQL.

**MUST NOT**
- Use direct PDO outside the core DB routine (installer excepted).

## Core protection and updates
**MUST**
- Treat core files and tables as immutable.
- Register themes/extensions only via core APIs.
- Update core only via Admin update flow.

**MUST NOT**
- Allow extensions/themes to modify core files or tables.

## Table ownership
**MUST**
- Enforce table namespaces:
  - Core tables (core-owned)
  - Extensions: #_ext_{ext_key}_*
  - Themes: #_theme_{theme_key}_*
- Allow read-only access to core tables.
- Allow write access only to owned tables.

**MUST NOT**
- Allow cross-namespace writes.

## Filesystem and writable storage
**MUST**
- Follow Phase 1 layout rules strictly.
- Enforce validated keys and IDs.
- Restrict writes to explicitly allowed areas.
- Route all user media writes through core services.

**MUST NOT**
- Permit writes outside allowed storage.

## CSS / assets / UI
**MUST**
- Always load core baseline CSS.
- Keep assets local.
- Use Bootstrap 5 only.
- Use bundled Bootstrap JS only.
- Apply global CSS overrides after baseline and theme CSS.
- bootsrap 5 files to be kept in core/assets/lib/

**MUST NOT**
- Use inline styles or style blocks.
- Use non-Bootstrap or legacy Bootstrap classes.

## Extensions and themes
**MUST**
- Declare everything in manifest.xml.
- Use SQL-only install/uninstall.
- Reject unsafe installs automatically.

**MUST NOT**
- Allow executable install scripts.

## Environment constraints
**MUST**
- Keep codebase static and production-safe.

**MUST NOT**
- Add dev-only files, tests, or tooling.

## Entrypoints
**MUST**
- Keep entrypoints minimal: context + bootstrap only.

**MUST NOT**
- Add logic to entrypoints in later phases.

## Multilingual rules
**MUST**
- Treat the system as multilingual from day one.
- Use language keys for all user-facing text.
- Maintain INI language packs.
- Update all en-GB.ini files continuously as features evolve.
- Fail loudly on missing language keys.

**MUST NOT**
- Ship features with hardcoded or untranslated strings.

## Pre-supplied assets and libraries
**MUST**
- Use pre-supplied core assets and libraries when they already exist.
- If a required asset or library is not pre-supplied, it **MUST** be obtained at 
build time and installed locally as its own subdirectory within the core asset 
library.
- Treat all installed libraries as read-only at runtime.

**MUST NOT**
- Replace, override, or fork pre-supplied core assets when they already satisfy 
the requirement.
- Load assets or libraries from external sources at runtime.
- Scatter third-party assets outside the core asset library structure.

## Global UI Specification — Icons, Actions, and Lists

### 1. General Rule

All system-generated lists and action controls MUST follow a consistent icon 
and 
action pattern across the entire system.

- **Admin**
  - All actions rendered in the admin toolbar MUST be presented as **buttons**.
  - All system-generated buttons MUST include **both an icon and a visible text 
label**.
  - Action entries rendered outside the toolbar (row actions, inline actions, 
menus, etc.) MUST include an icon.

- **Site (Frontend / User Area)**
  - System-generated lists and actions MUST use icons consistently across all 
items.
  - Icon usage MUST be uniform and predictable.

- **Icon Source**
  - All icons MUST be sourced **exclusively from the Font Awesome 6 icon 
library**.
  - Mixing icon libraries or custom icon sets is forbidden.

---

### 2. Icon Consistency Rule

- The **same icon MUST always represent the same action or state system-wide**.
- Per-screen, per-component, or per-developer icon variations are forbidden.
- If an action or state exists in multiple contexts (toolbar, list, menu), it 
MUST use the same icon everywhere.

---

### 3. List Layout Requirements

All system-generated lists (Admin and Site) MUST:

- Present primary content first, followed by actions
- Group actions consistently
- Use **even spacing** between items
- Include a **clear visual separator** between list items (border, rule, or 
equivalent)
- Avoid dense or compressed layouts that reduce scanability

---

### 4. Accessibility Requirements (Mandatory)

All icons and icon-based controls MUST meet accessibility requirements:

- Decorative icons MUST be hidden from assistive technologies 
(`aria-hidden="true"`).
- Meaningful icons MUST expose accessible names (e.g. `aria-label` or 
visually-hidden text).
- All interactive elements MUST:
  - be keyboard accessible
  - expose appropriate roles
  - provide visible focus states

---

### 5. Canonical Action and State → Icon Mapping (Font Awesome 6)

#### 5.1 Actions

| Action           | Font Awesome 6 Icon |
|------------------|---------------------|
| View / Open      | `fa-eye` |
| Create / Add     | `fa-plus` |
| Edit             | `fa-pen` |
| Save             | `fa-floppy-disk` |
| Delete           | `fa-trash` |
| Duplicate / Copy | `fa-clone` |
| Settings         | `fa-gear` |
| Permissions      | `fa-key` |
| Users            | `fa-user` |
| Logout           | `fa-right-from-bracket` |

---

### 5.2 State Indicators (Status Icons)

In **system-generated lists**, state indicators MUST communicate both:
1. the current state of the item (enabled/on or disabled/off), and  
2. whether that state is editable in the current context.

State indicators represent **status only**, not actions. They MUST NOT be 
clickable and MUST NOT change state directly.

---

#### State → Icon Mapping (Lists)

| State | Editable | Icon | Visual Treatment |
|------|----------|------|------------------|
| Enabled / On | Yes | `fa-circle-check` | Green |
| Disabled / Off | Yes | `fa-circle-xmark` | Red |
| Enabled / On | No (Locked) | `fa-circle-check` | Muted / greyed (reduced 
opacity) |
| Disabled / Off | No (Locked) | `fa-circle-xmark` | Muted / greyed (reduced 
opacity) |

---

#### Rules

- The **icon shape MUST always reflect the actual state**:
  - check = enabled/on
  - cross = disabled/off
- **Editability MUST be conveyed by visual treatment**, not by changing the 
icon.
- Colour MUST NOT be the sole indicator of state; icon shape remains primary.
- Locked state indicators MUST NOT be focusable or clickable.
- Toggle switches MUST NOT be used in list contexts.
- Toggle switches MAY be used elsewhere in the system (e.g. forms, settings, 
detail views).

---

#### Accessibility (Mandatory)

- State indicators MUST expose accessible text describing both state and 
editability, for example:
  - `aria-label="Enabled"`
  - `aria-label="Disabled (locked)"`
- Decorative wrappers MUST be hidden from assistive technologies.
- If explanatory help is provided (tooltip or hint), it SHOULD explain *why* the 
item is locked.

---

#### Principle (Normative)

**Actions change state; state indicators never change state.**


### 6. Forbidden Patterns (Scoped)

The following patterns are forbidden **in system-generated lists only**:

- Toggle switches used to represent enable/disable state
- Using `fa-toggle-on` / `fa-toggle-off` icons
- Using colour alone to convey meaning
- Different icons representing the same action or state in different areas

Outside of list contexts (e.g. forms, settings screens), toggle switches MAY be 
used where appropriate.

---

#### State → Icon Mapping (Lists)

| State | Editable | Icon | Visual Treatment |
|------|----------|------|------------------|
| Enabled / On | Yes | `fa-circle-check` | Green |
| Disabled / Off | Yes | `fa-circle-xmark` | Red |
| Enabled / On | No (Locked) | `fa-circle-check` | Muted / greyed (reduced 
opacity) |
| Disabled / Off | No (Locked) | `fa-circle-xmark` | Muted / greyed (reduced 
opacity) |

---

#### Rules

- The **icon shape MUST always reflect the actual state**:
  - check = enabled/on
  - cross = disabled/off
- **Editability MUST be conveyed by visual treatment**, not by changing the 
icon.
- Colour MUST NOT be the sole indicator of state; icon shape remains primary.
- Locked state indicators MUST NOT be focusable or clickable.
- Toggle switches MUST NOT be used in list contexts.
- Toggle switches MAY be used elsewhere in the system (e.g. forms, settings, 
detail views).

---

#### Accessibility (Mandatory)

- State indicators MUST expose accessible text describing both state and 
editability, for example:
  - `aria-label="Enabled"`
  - `aria-label="Disabled (locked)"`
- Decorative wrappers MUST be hidden from assistive technologies.
- If explanatory help is provided (tooltip or hint), it SHOULD explain *why* 
the 
item is locked.

---

#### Principle (Normative)

**Actions change state; state indicators never change state.**


---

### 6. Forbidden Patterns (Scoped)

The following patterns are forbidden **in system-generated lists only**:

- Toggle switches used to represent enable/disable state
- Using `fa-toggle-on` / `fa-toggle-off` icons 
- Using colour alone to convey meaning
- Different icons representing the same action or state in different areas

Outside of list contexts (e.g. forms, settings screens), toggle switches MAY be 
used where appropriate.


---

### 7. Rationale

This specification enforces visual, behavioural, and accessibility consistency 
across the system. Replacing toggle switches with explicit state icons removes 
ambiguity, improves scanability in lists, and avoids reliance on motion or 
colour alone. Canonical icon mapping prevents UI drift, supports user muscle 
memory, and ensures the interface remains predictable, accessible, and 
maintainable as the system grows.

### 8. Core Trust & Integrity (Global)

### Trusted signing public key

**MUST**
- Ship with a single **trusted signing public key** stored in a core read-only 
location:
  - `/core/keys/freeflow_public.pem`
- Use this key for **all cryptographic signature verification** related to:
  - update repositories and feeds
  - core update packages
  - extension and theme install/update packages (where signatures are present)
- Treat the trusted signing public key as **immutable at runtime**.
- Permit changes to the trusted signing public key **only via a verified core 
update**
  executed through the Admin update flow.

**MUST NOT**
- Load a trusted signing key from the database, environment variables, or any
  user-editable configuration.
- Allow signature verification to be disabled, bypassed, or overridden, even by
  Super.

---

### Trusted key fingerprint (sanity and tamper warning)

**MUST**
- Store the fingerprint of `/core/keys/freeflow_public.pem` in the global
  settings table (`#__settings`) for diagnostics and audit purposes only:
  - `group`: `security`
  - `key`: `trusted_signing_pub_fingerprint`
  - `value`: hex-encoded fingerprint (e.g. SHA-256 of normalized public key 
bytes)
- On entry to **Installer** and **Updates** admin routes, compute the 
fingerprint
  of the trusted signing public key file and compare it to the value stored in
  `#__settings`.
- If a mismatch is detected:
  - Immediately block all install and update operations.
  - Log the condition to `admin.log` and `error.log` (fingerprints only).
  - Display a persistent **Super-only warning banner** until the mismatch is
    resolved.

**SHOULD**
- Notify Super by email if mail is configured, rate-limited (e.g. once per 24
  hours), including fingerprint values only.

**MUST NOT**
- Automatically place the entire system into maintenance mode solely due to a
  fingerprint mismatch.

---

### Missing or unreadable trusted key

**MUST**
- If `/core/keys/trusted_signing_pub.pem` is missing or unreadable:
  - Refuse all install and update actions.
  - Log the condition to `admin.log` and `error.log`.
  - Display a persistent Super-only warning banner.

**MUST NOT**
- Proceed with any operation that requires cryptographic verification when the
  trusted signing public key cannot be loaded.
  
### Core table: #__admin_menu_entries

- Single registry for core + extensions.

- Suggested columns (keep it minimal but future-proof):

  - id (PK)
  - owner_type ENUM(core,ext)
  - owner_key (string)
  - core items: core
  - extension items: {ext_key} (your extension unique key)
  - item_key (string, unique per owner or globally unique)
  - parent_id (nullable FK -> #__admin_menu.id) ✅ for submenus
  - label_key (string) ✅ mandatory
  - icon (string, nullable)
  - route (string) ✅ e.g. /admin/users or /admin/safehavens/anchorages
  - sort_order (int)
  - perm_key (string, nullable)
  - is_enabled (bool)
  - created_at, updated_at

## Admin Navigation Source of Truth (NEW — Global, Mandatory)

- The Admin sidebar menu output (Zulu.sidebar_menu) MUST be generated at 
runtime from #_admin_menu table and MUST NOT 
be hardcoded in templates, controllers, or views.

- Core admin sections MUST also be represented as registry entries (core rows) 
and participate in the same menu composition pipeline.

- The Zulu template MUST render sidebar_menu content only by calling the core 
Menu service output for the current user/context; it MUST NOT define menu items 
inline.

## Build time sql

- All core schema and all core seed data (except the Super User) MUST be 
implemented in `/core/admin/sql/install.sql` using `INSERT IGNORE`.
- The agent MUST also implement `/core/admin/sql/uninstall.sql` to remove core 
tables (extensions manage their own removal).
-All core schema MUST be Updated during build with relevant fields and data.

## Build time languages

- From the implementation of Phase 4 (NOT BEFORE) all lanuguage files MUST be 
updated as the build progresses. 
- minimum languages are  english, german, french, spanish, italian.

---



# Phase 1 — Filesystem, Skeleton and Guarding

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.



## Phase Objective
Create the complete filesystem structure for the system and place all 
non-entrypoint directories into a guarded state.

This phase is **purely structural**.
No runtime wiring, no bootstrap logic, no routing, no configuration, and no 
assumptions about execution.

After this phase:
- The directory layout is final
- Core is visibly the entire base runtime
- All non-entrypoint directories are guarded
- Baseline assets and system media are in their permanent locations

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Create the full directory structure
2. Create guarded `index.php` files in all guarded directories
3. Create unguarded entrypoint `index.php` files
4. Move existing assets to their final locations

Nothing else is permitted.

---

## Guard Definition
Guards are **PHP execution guards**, not filesystem tricks.

### Guard file
- Filename: `index.php`
- Content (exact):

```php
<?php
defined('FF_CMS') or die('Restricted');
```

### Guard rules
- `FF_CMS` is **not defined** in this phase
- Guards exist only to prevent direct access
- Guards may be explicitly removed in later phases
- No other logic is permitted in guard files

---

## Entry Points (UNGUARDED)

The following directories and files are **entry points** and MUST NOT contain 
guards:

- `/index.php`
- `/admin/index.php`
- `/install/index.php`

Rules:
- These files must exist as empty placeholders during this Phase or contain 
minimal comments only
- No other files are permitted in these directories

---

## Directory Structure Created in This Phase

### Core
```
/core/
├─ controllers/
│  ├─ site/
│  └─ admin/
├─ models/
├─ views/
│  ├─ site/
│  └─ admin/
├─ assets/
│  └─ lib/
│     └─ fontawesome6/
│     └─ editors/freewrite
│     └─ freepick/
├─ languages/
├─ languages/en-GB/
├─ keys/
└─ admin/
   ├─ sql/
   └─ config/   
```

### Extensions & Themes
```
/extensions/
/themes/
/themes/alpha/
/themes/zulu
```

### Storage
```
/storage/
├─ backups/
├─ logs/
├─ media/
│  └─ system/
│  └─ users/
│  └─ extensions/
│  └─ themes/
└─ tmp/
```

---

## Guarded Directories

An `index.php` guard file MUST be created in **every directory listed below**.

### Core
- `/core/`
- `/core/controllers/`
- `/core/controllers/site/`
- `/core/controllers/admin/`
- `/core/models/`
- `/core/views/`
- `/core/views/site/`
- `/core/views/admin/`
- `/core/templates/`
- `/core/templates/Alpha/`
- `/core/templates/Zulu/`
- `/core/assets/`
- `/core/assets/lib/`
- `/core/assets/lib/fontawesome6/`
- `/core/languages/`
- `/core/admin/`
- `/core/admin/sql/`
- `/core/admin/config/`

### Extensions & Themes
- `/extensions/`
- `/themes/`
- `/themes/alpha/`
- `/themes/zulu/`

### Storage
- `/storage/`
- `/storage/backups/`
- `/storage/logs/`
- `/storage/media/`
- `/storage/media/system/`
- `/storage/media/users/`
- `/storage/media/extensions/`
- `/storage/media/themes/`
- `/storage/tmp/`

Every directory listed above MUST contain an `index.php` guard file with the 
exact guard content.

---

## Asset & Media Moves (EXPLICIT AND ONE-TIME)

### FontAwesome 6
- Source: existing FontAwesome 6 directory already present in the workspace
- Action: **MOVE if required** (not copy)
- Destination:

```
/core/assets/lib/fontawesome6/
```

Rules:
- Directory structure is preserved
- No references are added in this phase
- No duplicate copies may remain elsewhere

---

### System Logo
- File: `fflogo.png`
- Action: **MOVE** (not copy)
- Destination:

```
/storage/media/system/fflogo.png
```

Rules:
- This file becomes core-owned system media
- No other copies may exist after the move

---

## Forbidden Actions
The following are strictly forbidden in this phase:

- Defining `FF_CMS`
- Adding bootstrap logic
- Wiring routes
- Loading or referencing assets
- Creating configuration files
- Writing SQL logic
- Creating databases
- Adding tests, servers, or debug files
- Copying assets instead of moving them
- Introducing new dependencies
- Creating `vendor/` directories

---

## Verification Checklist (Static Only)

- All directories listed in this document exist
- All guarded directories contain an `index.php` guard
- Entry point directories contain only their entrypoint file
- FontAwesome 6 exists only in `/core/assets/lib/fontawesome6/`
- `fflogo.png` exists only in `/storage/media/system/`
- No runtime logic has been added
- No forbidden directories or files exist

---

## Phase Completion Criteria

Phase 1 is complete when:
- The filesystem matches this document exactly
- All guards are present and correct
- All asset moves are complete
- No forbidden actions have occurred

No subsequent phase may begin until Phase 1 is complete and verified.

# Phase 2 — Core Bootstrap

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Phase Objective
Implement the **core runtime bootstrap and application engine** so the system 
can execute safely, deterministically, and in a guarded manner.

This phase delivers a **fully working core runtime**:
- Guards become functional
- The system boots in both site and admin contexts
- Missing configuration automatically hands off to the installer
- Entry points are permanently frozen
- Core runtime services exist as real implementations

This phase does **not** render UI, route URLs, or load templates.

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Implement the core bootstrap
2. Define the `FF_CMS` runtime constant
3. Implement a single autoloader
4. Implement `Core\Application`
5. Implement core configuration loading (DB config only)
6. Implement automatic installer hand-off
7. Implement the single database connection layer
8. Update entry points to delegate execution to core
9. Ensure all guards function correctly

No other behaviour is permitted.

---

## Entry Point Rules (PERMANENT)

The following files are **entry points** and are permanently frozen:

- `/index.php`
- `/admin/index.php`

They MUST:
1. Define execution context
2. Include `core/bootstrap.php`
3. Instantiate and run `Core\Application`

They MUST NOT:
- Contain any other logic
- Perform routing
- Perform configuration
- Perform authentication
- Perform output logic

**No later phase may add logic to entry points. Ever.**

---

## Execution Context

Execution context is **explicit** and immutable.

- Site context constant: `FF_CONTEXT_SITE`
- Admin context constant: `FF_CONTEXT_ADMIN`

Exactly one execution context constant MUST be defined by the entry point before 
loading the bootstrap.

---

## Core Bootstrap

### File
```
/core/bootstrap.php
```

### Responsibilities
The bootstrap MUST:

- Define `FF_CMS` exactly once
- Validate that an execution context constant exists
- Register the core autoloader
- Attempt to load database configuration
- Detect installation state
- Hand off to the installer when required
- Instantiate and execute `Core\Application` when installed
- Fail cleanly on invalid or unsafe state

The bootstrap MUST NOT:
- Route URLs
- Render output
- Load templates
- Start sessions
- Load extensions or themes

---

## Automatic Installer Hand-Off (MANDATORY)

### Installation State Detection
The system is considered **NOT installed** if:

- `/core/admin/config/config.php` does not exist

### Behaviour When Not Installed
When the system is not installed:

- Bootstrap MUST immediately stop normal runtime execution
- Bootstrap MUST hand off execution to the installer
- No core runtime logic may continue
- No guards are bypassed
- No partial runtime is allowed

Installer hand-off MUST:
- Transfer execution to `/install/index.php`
- Respect `/install/.lock` if present
- Perform no fallback or recovery logic

### Behaviour When Installed
When configuration exists:
- Bootstrap proceeds with normal runtime initialisation
- Installer is never referenced again

---

## Guards (Now Functional)

All guarded `index.php` files created in Phase 1 MUST now function correctly.

- Any direct access to guarded directories MUST terminate execution
- Guards rely solely on the presence of `FF_CMS`
- No guarded file may define `FF_CMS`

---

## Core Application

### File
```
/core/Application.php
```

### Responsibilities
`Core\Application` MUST:

- Accept the execution context
- Validate execution context
- Represent the running application
- Provide a single `run()` method
- Terminate cleanly when no routing is defined

This is a **real implementation**, not a stub.

Expected behaviour:
- Site context produces a deterministic plain response
- Admin context produces a deterministic plain response
- Output may be minimal text such as:
  - “System initialised — no routes defined”

---

## Autoloader

### File
```
/core/Autoloader.php
```

### Rules
- Single autoloader for the entire system
- No Composer
- No external loaders
- Must reliably load all core classes
- Behaviour must be deterministic and explicit

---

## Configuration Loader

### File
```
/core/Config.php
```

### Scope
- Load **database configuration only**
- Source:
```
/core/admin/config/config.php
```
- Must not error if the file does not exist
- Must expose configuration values to the core runtime

No other configuration formats or sources are introduced in this phase.

---

## Database Connection Layer

### File
```
/core/Connection.php
```

### Rules
- Exactly **one** database connection routine
- Uses PDO internally
- Supports `#_` table prefix replacement
- Prefix is loaded from configuration
- Prefix replacement occurs at runtime
- No schema creation
- No table modification
- No installer logic

The connection layer MUST be fully implemented and usable within Phase-2 scope.

Installer database logic remains isolated and separate.

---

## Session Groundwork

Session handling MAY be introduced in this phase, but only to define:

- Distinct session names for site and admin
- No authentication logic
- No ACL logic

Sessions MUST NOT start automatically unless required by execution.

---

## Forbidden Actions

The following are strictly forbidden in this phase:

- UI rendering
- Routing logic
- Controller execution
- Template loading (Alpha/Zulu untouched)
- Bootstrap or FontAwesome usage
- Authentication or ACL logic
- Extension or theme loading
- Installer logic beyond hand-off
- Tests, mocks, simulations, or stubs
- Debug or helper scripts
- JSON usage
- Additional database connections

---

## Fully Working Implementation Rule

Everything introduced in this phase MUST be:
- Fully implemented
- Deterministic
- Complete within Phase-2 scope

No stubs, placeholders, TODOs, or simulated behaviour are permitted.

If a feature cannot be completed in this phase, it MUST NOT be introduced.

---

## Verification Checklist

- Guards correctly block direct access
- `FF_CMS` defined exactly once
- Bootstrap hands off to installer when config is missing
- Installer is unreachable once installed
- Entry points delegate execution correctly
- `Core\Application` runs in both contexts
- Database connection supports prefix replacement
- No forbidden logic exists

---

## Phase Completion Criteria

Phase 2 is complete when:
- Core runtime boots deterministically
- Installer hand-off works correctly
- Guards are effective
- Entry points remain unchanged
- All rules in this document are satisfied

No further phases may proceed until Phase 2 is complete.

# Phase 3 — Routing

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Phase Objective
Implement the first **fully working** request→route→controller→view→template 
rendering pipeline for both:
- Frontend (Alpha)
- Admin (Zulu)

This phase fully defines both templates (layout contracts, region keys, 
responsive behaviour, and asset locations) and provides working routing and 
rendering to prove the runtime end-to-end.

This phase does **not** introduce:
- authentication
- ACL
- users/content modules
- extensions/themes system
- database usage (beyond what Phase 2 already established)

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Implement routing for site and admin (separate route tables, single 
Application)
2. Implement controller dispatch for site and admin controllers
3. Implement view rendering (PHP views)
4. Implement template rendering for Alpha and Zulu
5. Implement template region rendering (named region placeholders)
6. Implement core asset serving sufficient to load local Bootstrap and template 
assets
7. Provide a working admin dashboard page with 6 dashboard element zones 
(dashboard page only)

No other behaviour is permitted.

---

## Permanent Entry Point Rules (Re-affirmed)
Entry points remain frozen and unchanged except where already required by prior 
phases:

- `/index.php`
- `/admin/index.php`

They MUST:
1. Define execution context
2. Include `core/bootstrap.php`
3. Instantiate and run `Core\Application`

They MUST NOT contain any additional logic now or in any later phase.

---

## Routing (Single System, Two Route Tables)

### Rules
- Routing is owned by `Core\Application`
- There is one routing mechanism and one dispatcher
- There are two route tables:
  - Site routes (for `/`)
  - Admin routes (for `/admin` entrypoint)

No dynamic route registration, no extensions, no theme routing in this phase.

### Minimum Required Routes
#### Site
- `/` → Site home page

#### Admin
- `/` (within admin entrypoint context) → Admin dashboard page

---

## Controllers (Real, Minimal)

### Controller Rules
- Controllers must be real implementations, not stubs
- Controllers must not use the database in this phase
- Controllers must not use auth/ACL in this phase
- Controllers must render a view and return a response via the Application

### Minimum Required Controllers
- `core/controllers/site/HomeController.php`
- `core/controllers/admin/DashboardController.php`

---

## Views (Real, Minimal)

### View Rules
- Views are PHP files under core views paths
- Views contain markup only, no routing logic, no DB calls
- Views may receive data arrays from controllers

### Minimum Required Views
- `core/views/site/home.php`
- `core/views/admin/dashboard.php`

---

## Template System (Fully Defined in This Phase)

### Template Ownership and Location
Templates are core-owned baseline templates and live only in:

- `themes/Alpha/` (site baseline)
- `themes/Zulu/` (admin baseline)

Templates are part of core and follow core guard rules.

### Template Asset Location Rules
Template-specific assets MUST be stored only under:

- `theme/Alpha/assets/`
- `theme/Zulu/assets/`

No inline CSS.
No inline JS.
No `<style>` blocks.

Bootstrap assets are stored in core (not in root, not in admin root) and are 
loaded locally.

- both Site and Admin MUST use icons from the inbuilt Fontawesome6 library 
where necessary.

---

## Alpha Template (Frontend) — Full Layout Contract

### Alpha Purpose
A modern, clean, fully responsive frontend layout suitable for directory/blog 
style content, built as a region-based layout contract for later Elements 
placement.

### Alpha Containers (Vertical Bands)
Alpha is composed of three major bands inside a single responsive outer 
container:

1. Container 1 (Header band)
2. Container 2 (Main content band)
3. Container 3 (Footer band)

### Alpha Background Rules
- Container 1 background MUST default to: `#99ffff`
- Container 2 background MUST default to: `#ffffff`
- Container 3 background MUST default to: `#99ffff`
- All sidebar/content region panels within Container 2 MUST default to a very 
light near-white background such as: `#faffed`

Alpha colour values are stored in Alpha template CSS (not inline).
Alpha must support alpha colours such as `#00000000` (transparent) for future 
overrides.

### Alpha Region Keys (Frozen API)
#### Header band regions
- `topbar`
- `header_left`
- `header_right`
- `menu`
- `below_menu`

#### Main band regions
- `sidebar_left`
- `above_content`
- `content` (required)
- `below_content`
- `sidebar_right`

#### Footer band regions
- `above_footer_1`
- `above_footer_2`
- `above_footer_3`
- `above_footer_4`
- `footer_1`
- `footer_2`
- `footer_3`
- `footer_4`
- `below_footer`
- `copyright`

### Alpha Rendering Rules
- Any region with no output MUST NOT be rendered (no empty wrappers)
- The layout MUST reflow so that unused sidebars do not consume space:
  - if left sidebar is empty, main content expands
  - if right sidebar is empty, main content expands
  - if both are empty, content is full width
- Above/below content regions render only if they have output
- Footer 4-region rows render only populated regions; empty regions do not 
reserve columns

### Alpha Assets
Alpha template MUST include:
- Bootstrap 5 CSS and Bootstrap bundled JS (local core assets)
- Alpha template CSS from `themes/Alpha/assets/`
- Alpha template JS from `themes/Alpha/assets/` (may be empty in this phase)

---

## Zulu Template (Admin) — Full Layout Contract (Rigid, Non-themeable)

### Zulu Purpose
A rigid, fixed admin layout that is responsive but not themeable later. It 
provides:
- topbar logo area
- collapsible left sidebar menu
- workspace toolbar/status bar aligned to sidebar edge
- dashboard workspace with 6 dashboard zones on the dashboard page only

### Zulu Background / Style Rules (Defaults)
Defaults are defined in `themes/Zulu/assets/css/template.css`.

- Chrome backgrounds (topbar, sidebar, toolbar) MUST default to: `#ce0000`
- Chrome text/icons MUST default to: `#ffffff`
- Hover/active backgrounds are NOT allowed and MUST be transparent:
  - background must remain `#00000000` on hover/active states
- Workspace background SHOULD default to: `#f5f5f5` (or lighter)

Note: The admin logo is used as transparent PNG. If later contrast changes are 
desired, only CSS changes are permitted (no runtime logic changes).

### Zulu Regions (Frozen API)
#### Topbar
- `topbar_logo`

#### Sidebar
- `sidebar_toggle`
- `sidebar_menu`

#### Workspace toolbar/status (inside workspace, not in topbar)
- `toolbar_left`
- `toolbar_right`
- `toolbar_notices`

#### Workspace content
- `content` (required)

#### Dashboard-only zones (dashboard page only)
- `dashboard_1`
- `dashboard_2`
- `dashboard_3`
- `dashboard_4`
- `dashboard_5`
- `dashboard_6`

### Zulu Layout Rules
#### Topbar (logo only)
- Topbar contains logo only
- Logo is left aligned
- Left padding around 40px
- No padding elsewhere
- Logo is vertically centered
- Logo uses the cropped `fflogo.png` (transparent) and is sized by height 
responsively
  - A reasonable default is:
    - desktop: ~250px height
    - tablet: ~150px height
    - mobile: ~100px height
- Topbar height must be sufficient to contain the logo (responsive)

#### Sidebar
- Sidebar is collapsible
- Expanded state shows icons + text
- Collapsed state shows icons only
- Toggle switch:
  - must look like an on/off switch (not hamburger)
  - must be right aligned
  - must have no text label
- Sidebar background matches topbar/toolbar
- No hover/active backgrounds (transparent only)
- Zulu sidebar menu must contain at least one item contributed from the 
registry and must not contain any hardcoded menu arrays/markup.”


#### Toolbar/status bar (workspace-aligned)
- Toolbar exists at the top of the workspace area (to the right of sidebar)
- Toolbar left edge aligns with sidebar edge (responds to collapse/expand)
- Toolbar split:
  - left: tools region (buttons later)
  - right: non-session link to frontend (right aligned)
- Toolbar may also show notices:
  - `toolbar_notices` renders only when present
  - Must not break left/right layout

#### Workspace
- Dashboard page uses 6 dashboard zones arranged responsively:
  - large screens: 2 columns x 3 rows (preferred)
  - small screens: 1 column stack
- Non-dashboard pages render normal content in `content` and do not use 
dashboard zones

---

## Core Asset Serving (Minimal, Working)

### Rules
- No files may be added to `/` or `/admin/` beyond their entrypoint `index.php`
- Bootstrap and template assets are stored under core and must be accessible 
via 
a core-controlled mechanism
- Only the following asset roots are required in this phase:
  - core Bootstrap assets location (in core)
  - `themes/Alpha/assets/`
  - `themes/Zulu/assets/`

No external assets, no CDNs, no remote loading.

# Phase 4 — Logging, Language and System Events

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

This Phase implements core logging and system event recording, plus an Admin 
log viewer integrated into the existing Settings screen. Also implements the 
core language system. 

This phase provides:
- Deterministic, file-based logging
- Strict separation between event logs and error logs
- A central logger API
- Admin UI for viewing, downloading, and clearing logs
- Mandatory logging of all system errors

This phase does not implement:
- Log rotation of any kind
- Stack traces in logs
- JSON output
- External logging services
- Any changes to mail sending, update logic, or authentication logic beyond 
logging

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or assets
- No JSON anywhere
- AJAX allowed but responses must be HTML or plain text only
- Bootstrap 5, fully responsive
- No inline CSS
- Core DB connection only
- Admin-context session required
- Super (role 998) is the only role allowed to access logs

## Log storage

All logs are stored in `/storage/logs/`.
This directory is non-web-accessible and must never be readable via HTTP.

Exactly the following log files exist:

- `error.log` — all system errors and exceptions
- `auth.log` — authentication and account-related events
- `mail.log` — mail send events
- `updates.log` — updates and maintenance events
- `admin.log` — significant admin actions (excluding authentication)
 
Empty logs MUST be placed at build time.
 

No other log files may be created dynamically.

## Event logs vs error log

Event logs (`auth.log`, `mail.log`, `updates.log`, `admin.log`) record what 
happened:
- attempts
- outcomes (success or fail)
- high-level, non-sensitive context

Event logs must not contain:
- stack traces
- exception details
- credentials
- tokens
- session identifiers

If an event fails, the failure outcome is recorded here, but technical details 
are not.

All errors and exceptions of any kind are written to `error.log`.
No errors are written to event logs.
All system errors MUST be logged to `error.log`.

## Log entry rules

- One log line equals one event
- No multi-line entries
- No stack traces
- No embedded newlines

### Timestamp format

`DD-MMM-YYYY HH:MM:SS`

English month abbreviations only, locale-independent, 24-hour clock, 
zero-padded.

### Log line format

`DD-MMM-YYYY HH:MM:SS | LEVEL | CHANNEL | context | message`

- LEVEL: INFO, WARNING, ERROR
- CHANNEL: error, auth, mail, updates, admin
- context: space-separated key=value pairs, single-line only
- message: human-readable, single-line text

Pipe characters and newlines must be escaped or removed from context and 
message.

## Central Logger API

A single core logger service must exist with:

- `info(string $channel, string $message, array $context = [])`
- `warning(string $channel, string $message, array $context = [])`
- `error(string $channel, string $message, array $context = [])`

Rules:
- Channel must map to one of the fixed log files
- Unknown channels must be rejected deterministically
- Logger must never output JSON
- Logger must always emit exactly one log line per call

## Mandatory logging requirements

### Authentication events (`auth.log`)
- Successful login (site and admin)
- Failed login attempts
- Logout
- Password reset request
- Password reset completion

Admin login is recorded here.

### Mail events (`mail.log`)
- Every mail send attempt
- Outcome recorded as success or fail
- No error details or credentials

Failures:
- Event outcome in `mail.log`
- Technical details in `error.log`

### Updates and maintenance (`updates.log`)
- Core update start and result
- Extension and theme update start and result
- Maintenance runs (who, when, what)

Failures:
- Event outcome in `updates.log`
- Technical details in `error.log`

### Admin actions (`admin.log`)
- Significant admin actions excluding authentication

### Errors (`error.log`)
- All uncaught exceptions
- All system-level failures
- All technical error details

## Safety and redaction

Logs MUST NOT contain:
- Database credentials
- SMTP credentials
- Tokens of any kind
- Session identifiers
- Cookie values

Sensitive values must be replaced with `[REDACTED]` at log-write time.

## Admin UI — Logs tab

Logs are managed under `/admin/settings` as a Logs tab.

Access is Super-only (role 998).

### Logs list

The list displays:
- error.log
- auth.log
- mail.log
- updates.log
- admin.log

Each row has:
- Eye icon — view log
- Download icon — download entire log file
- Trash icon — empty (truncate) log file

Files are never deleted.

## Log viewer

- Opens in a popup/modal
- Content is fully HTML-escaped
- Text is selectable and copyable

Viewer shows the last N lines, where N is selected at the top of the Logs tab:

- Last 10
- Last 25
- Last 50
- Last 100
- All

The selection is session-only and applies to subsequent views.

## Download behavior

Download always delivers the entire log file.

## Clear behavior

Clear truncates the log file contents.
Confirmation required.
No undo.

## Output escaping

All displayed log content must be HTML-escaped.
Logs must never be rendered as HTML.
XSS must not be possible even with hostile log content.


## Multilingual Language System Specification

Audience: Core developers, extension authors
Scope: Defines how FreeflowCMS loads, resolves, and applies human language 
translations across core and extensions.

---

1. Purpose

FreeflowCMS must support true multilingual operation using standard .ini 
language files.

The language system must load translations from core and extensions, support 
per-language fallbacks, provide a consistent API for resolving language strings, 
fail safely when translations are missing, and integrate cleanly with the 
built-in error logging system.

---

2. Supported Language File Format

Format: .ini  
Encoding: UTF-8 (no BOM)  
Key format: FFCMS_CONTEXT.KEY="Translated string"

Example keys:
FFCMS_SAVE="Save"
FFCMS_CANCEL="Cancel"

Keys MUST be uppercase.

---

3. Language Directory Structure

3.1 Core Languages

Core language files MUST live in:
core/languages/{lang_tag}/

Example:
core/languages/en-GB/
core/languages/fr-FR/

Files may include:
core/languages/en-GB/language_en-GB.ini
core/languages/fr-FR/language_fr-FR.ini

---

3.2 Extension Languages

Extensions MUST store language files inside:
extensions/{ext_key}/Languages/{lang_tag}/

Example:
extensions/safehavens/Languages/en-GB/safehavens.ini
extensions/safehavens/Languages/es-ES/safehavens.ini

Languages directory name is case-sensitive.
ext_key MUST match the registered extension key.

---

3.3 the system must build and populate language files for  English (en-GB), 
German (de-DE), Italian (it-IT) French (fr-FR) and spanish (es-ES) at build 
time. fallback MUST 
be english, All keys MUST be translated at build time, if possible.

---

4. Language Tags

Language tags MUST follow ll-CC format, e.g. en-GB, fr-FR, es-ES.
They are case-sensitive on disk and case-insensitive logically.

---

5. Language Loading Order

1. Core language files
2. Extension language files
3. Overrides (future expansion)

Extension values override Core values.
Last loaded value wins.

---

6. Active Language Resolution

Order:
1. User preference
2. Session language
3. Site default
4. Fallback (en-GB)

Missing directories MUST fall back silently.
Where possible, common keys should be used and not generate new ones if a key 
Already exists.
Common words include words like: New, Create, Save, Download, Published, 
Publish, Unpublished, Unpublish, Delete, Created, Missing, Not Found. etc etc 
etc.

---

7. Fallback Behaviour

Resolution order:
1. Active language
2. Fallback language
3. Return key name

Missing keys MUST NOT cause errors.

---

8. Language API Contract

Conceptual usage: Lang::get('FFCMS_SAVE')

API MUST be globally available, safe at any execution stage, cache-aware, and 
must not re-read files repeatedly.

---

9. Extension Responsibilities

Extensions MUST provide at least en-GB, namespace all keys, never modify core 
language files, and never assume a language exists.

Invalid: SAVE="Save"
Valid: SAFEHAVENS.SAVE="Save"

---

10. Admin vs Site Context

No separation of site and admin keys or files.

---

11. Caching Rules

Language files MUST be cached per request.
Cache MUST invalidate when files change, extensions change, or language changes.

---

12. Error Logging Integration

The language system integrates with the built-in error.log logger.

Logged conditions MAY include missing directories, unreadable files, malformed 
ini files, or duplicate keys in debug mode.

Missing keys MUST NOT be logged.

Production logs only critical failures.
Debug logs MAY include detailed warnings.

Errors MUST NEVER break rendering or execution.

---

13. Non-Goals

This spec does not define content translation, RTL/LTR handling, locale 
formatting, or translation UIs.

---

14. Exit Criteria

FreeflowCMS is multilingual-compliant when core and extensions load correctly, 
missing translations fail safely, and no hardcoded user-facing strings exist.


## Acceptance criteria

Phase 4 is complete when:
1. Logs are written to `/storage/logs/`
2. Timestamp format is exactly `DD-MMM-YYYY HH:MM:SS`
3. One log line equals one event
4. Event logs and error log are strictly separated
5. All system errors are logged to `error.log`
6. Admin can view, download, and clear logs via Settings → Logs
7. Viewer supports selectable/copyable text and line limits
8. Logs are Super-only
9. No JSON is used anywhere
10. Language system is FULLY implemented with all necessary key="value" 
translations


# Phase 5 — Sessions & Authentication

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Phase Objective
Implement the **runtime identity layer** using the database schema created in 
Phase 4.

This phase introduces:
- DB-backed sessions
- Authentication (login/logout)
- Hard separation between site and admin contexts

This phase does **not** introduce user management, registration, roles UI, 
permissions UI, or settings UI.

The phase is complete when authentication and session handling function 
correctly in isolation.

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Implement DB-backed session handling using `#_sessions`
2. Enforce strict separation between site and admin sessions
3. Implement authentication services (login/logout)
4. Implement site and admin login pages
5. Enforce admin access gating (admin requires admin session)

No other features are permitted.

---

## Session Handling (DB-Backed)

### Session Model
- Single table: `#_sessions`
- Context column: `site` or `admin`
- One PHP session handler implementation
- Uses the single core DB connection (`Connection.php`)

### Cookie Rules (Hard)
- Site:
  - Cookie name: `ff_site`
  - Cookie path: `/`
  - Context: `site`
- Admin:
  - Cookie name: `ff_admin`
  - Cookie path: `/admin`
  - Context: `admin`

### Enforcement Rules
- A session cookie MUST only ever load a session row matching its context
- Site sessions MUST NOT authenticate admin routes
- Admin sessions MUST NOT authenticate site identity implicitly
- Context mismatch MUST result in session rejection

### Session Data Rules
- Session payload is stored as PHP serialized data only
- No JSON is permitted
- Expired sessions MUST be rejected and removed

---

## Authentication Service

### Responsibilities
- Verify credentials using `password_verify`
- Bind authenticated `user_id` to session
- Create DB session row with correct context
- Destroy session on logout

### Login Identifier
- Login MUST accept either:
  - username OR
  - email
- Password hash comparison uses PHP native password API

### Logout Behaviour
- Logout MUST:
  - destroy DB session row
  - expire the session cookie
  - not affect other context sessions (site logout does not kill admin session 
and vice versa)

---

## Access Control (Minimal, Phase-Limited)

### Admin Gating
- All admin routes MUST require:
  - a valid admin-context session
- If no valid admin session:
  - redirect to admin login page
- No role or permission checks are enforced yet (handled in later phase)

### Site Access
- Site remains public
- Authenticated site session is available but not required for access

---

## Login Pages

### Site Login Page
- Location: site routes
- Template: Alpha
- Function:
  - display login form
  - submit credentials
  - authenticate into `site` session
- On success:
  - redirect to site homepage (or last requested page)

### Admin Login Page
- Location: admin routes
- Template: Zulu
- Function:
  - display login form
  - submit credentials
  - authenticate into `admin` session
- On success:
  - redirect to admin dashboard

### UI Rules
- Forms use POST
- No AJAX
- No JSON
- Errors returned as rendered HTML
- Flash messages may be stored in session data

---

## Bootstrap / Application Integration

### Bootstrap Behaviour
- During bootstrap:
  - if admin context AND no config present → redirect to installer
  - if admin context AND no admin session → redirect to admin login
- Site bootstrap does not require authentication

### Application Responsibility
- Determine context (site/admin)
- Load correct session handler configuration
- Expose authenticated user (if any) to controllers

---

## Forbidden Actions
This phase MUST NOT introduce:
- user registration
- roles or permissions enforcement
- admin managers
- profile UI
- settings UI
- mail handling
- tokens usage
- extensions or themes
- JSON usage
- external libraries

---

## Verification Checklist
- Sessions are stored in DB (`#_sessions`)
- Site and admin sessions are isolated by cookie + context
- Logging in to admin does not authenticate site session
- Logging out of one context does not affect the other
- Admin routes are inaccessible without admin session
- Login forms render correctly using Alpha and Zulu templates
- No UI beyond login/logout is added

---

## Phase Completion Criteria
Phase 5 is complete when:
- DB session handler functions correctly
- Site login/logout works
- Admin login/logout works
- Context separation is enforced
- All rules in this document are satisfied
- No forbidden actions occurred

# PHASE 6 — Identity & Access (DB Schema Only, No UI)



This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Phase Objective
Implement the **runtime identity layer** 

This phase introduces:
- DB-backed sessions
- Authentication (login/logout)
- Hard separation between site and admin contexts

This phase does **not** introduce user management, registration, roles UI, 
permissions UI, or settings UI.

The phase is complete when authentication and session handling function 
correctly in isolation.

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Implement DB-backed session handling using `#_sessions`
2. Enforce strict separation between site and admin sessions
3. Implement authentication services (login/logout)
4. Implement site and admin login pages
5. Enforce admin access gating (admin requires admin session)

No other features are permitted.

---

## Session Handling (DB-Backed)

### Session Model
- Single table: `#_sessions`
- Context column: `site` or `admin`
- One PHP session handler implementation
- Uses the single core DB connection (`Connection.php`)

### Cookie Rules (Hard)
- Site:
  - Cookie name: `ff_site`
  - Cookie path: `/`
  - Context: `site`
- Admin:
  - Cookie name: `ff_admin`
  - Cookie path: `/admin`
  - Context: `admin`

### Enforcement Rules
- A session cookie MUST only ever load a session row matching its context
- Site sessions MUST NOT authenticate admin routes
- Admin sessions MUST NOT authenticate site identity implicitly
- Context mismatch MUST result in session rejection

### Session Data Rules
- Session payload is stored as PHP serialized data only
- No JSON is permitted
- Expired sessions MUST be rejected and removed

---

## Authentication Service

### Responsibilities
- Verify credentials using `password_verify`
- Bind authenticated `user_id` to session
- Create DB session row with correct context
- Destroy session on logout

### Login Identifier
- Login MUST accept either:
  - username OR
  - email
- Password hash comparison uses PHP native password API

### Logout Behaviour
- Logout MUST:
  - destroy DB session row
  - expire the session cookie
  - not affect other context sessions (site logout does not kill admin session 
and vice versa)

---

## Access Control (Minimal, Phase-Limited)

### Admin Gating
- All admin routes MUST require:
  - a valid admin-context session
- If no valid admin session:
  - redirect to admin login page
- No role or permission checks are enforced yet (handled in later phase)

### Site Access
- Site remains public
- Authenticated site session is available but not required for access

---

## Login Pages

### Site Login Page
- Location: site routes
- Template: Alpha
- Function:
  - display login form
  - submit credentials
  - authenticate into `site` session
- On success:
  - redirect to site homepage (or last requested page)

### Admin Login Page
- Location: admin routes
- Template: Zulu
- Function:
  - display login form
  - submit credentials
  - authenticate into `admin` session
- On success:
  - redirect to admin dashboard

### UI Rules
- Forms use POST
- No AJAX
- No JSON
- Errors returned as rendered HTML
- Flash messages may be stored in session data

---

## Bootstrap / Application Integration

### Bootstrap Behaviour
- During bootstrap:
  - if admin context AND no config present → redirect to installer
  - if admin context AND no admin session → redirect to admin login
- Site bootstrap does not require authentication

### Application Responsibility
- Determine context (site/admin)
- Load correct session handler configuration
- Expose authenticated user (if any) to controllers

---

## Forbidden Actions
This phase MUST NOT introduce:
- user registration
- roles or permissions enforcement
- admin managers
- profile UI
- settings UI
- mail handling
- tokens usage
- extensions or themes
- JSON usage
- external libraries

---

## Verification Checklist
- Sessions are stored in DB (`#_sessions`)
- Site and admin sessions are isolated by cookie + context
- Logging in to admin does not authenticate site session
- Logging out of one context does not affect the other
- Admin routes are inaccessible without admin session
- Login forms render correctly using Alpha and Zulu templates
- No UI beyond login/logout is added

---

## Phase Completion Criteria
Phase 5 is complete when:
- DB session handler functions correctly
- Site login/logout works
- Admin login/logout works
- Context separation is enforced
- All rules in this document are satisfied
- No forbidden actions occurred

# Phase 6b — Registration + Tokens + Email Verification (Site)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Phase Objective
Implement **site registration** with **token-based email verification** using 
the Phase 4 schema and the Phase 5 session/auth runtime.

This phase introduces:
- Frontend Register page (Alpha)
- Registration workflow (create pending user)
- Token creation/validation/consumption using `#_tokens`
- Email verification endpoint (click-to-verify link)
- Minimal outbound email sending using DB mail settings and seeded templates
- Username optional + deterministic name-based allocation + AJAX availability 
checking
- Welcome email on successful verification (no passwords sent)

This phase does **not** introduce admin managers, roles/permissions UI, profile 
UI, settings UI, or mail template editing UI.

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Implement site registration page and handler
2. Create users in a pending (unverified) state
3. Implement token creation/validation/consumption using `#_tokens`
4. Implement email verification endpoint
5. Implement minimal email sending (local, no externals, no JSON)
6. Implement AJAX username availability check endpoint
7. Send a welcome email on successful verification (no passwords)
8. Create all site and admin forms required by the system to operate

No other behaviour is permitted.

---

## AJAX Rule (Global, Applies to This Phase)
- AJAX is permitted.
- AJAX responses MUST be **plain text or HTML only**.
- JSON is forbidden.

---

## Registration Page (Required)

### Route
- `GET /register` renders the form (Alpha)
- `POST /register` processes the form

### Form Rules
- Separate page (not modal)
- POST submit (standard form post)
- Helper notes MUST appear beneath each field (Bootstrap help text)
- Name entry fields MUST appear first on the form
- No JSON usage

### Required Fields (Minimum, In This Order)
1. `name_first` (required)
2. `name_last` (required)
3. `email` (required)
4. `username` (optional)
5. `password` (required)
6. `password_confirm` (required)

### Helper Notes (MANDATORY)
- First name: used to help generate your username if left blank  
- Last name: used to help generate your username if left blank  
- Email: must be verified; a verification email will be sent  
- Username:
  - optional
  - generated automatically if left blank
  - can be changed during registration
  - minimum 8 characters
  - cannot contain admin-like terms
- Password:
  - minimum 8 characters
  - at least 1 uppercase, 1 number, 1 symbol
  - no passwords are ever emailed

---

## Username Rules (Required)

### Username Optional
- User-supplied usernames MUST be retained and used.
- If left blank, the system MUST allocate one deterministically.

### Constraints
- Minimum length: 8
- Allowed characters: `a-z`, `0-9`, `_`, `-`
- Stored lowercase
- Must start with a letter

### Reserved Terms
Normalized username (lowercase, remove `_` and `-`) MUST NOT contain:
`admin`, `administrator`, `webmaster`, `superadmin`

Applies to both user-supplied and system-generated usernames.

---

## Username Allocation Policy (When Blank)

### Deterministic Generation
- Base derived from `name_first` + `name_last`
- Normalized to allowed characters
- Prefixed with `u` if it does not start with a letter

### Deterministic Suffix
- Append a deterministic suffix derived from:
  - `name_first`
  - `name_last`
  - email local-part
- Example:
  - `substr(sha1(first + last + email_local), 0, 4)`
- Result example:
  - `tonysmith-a9f3`

### Length & Uniqueness
- Ensure minimum length 8
- If collision:
  - append `-2`, `-3`, etc deterministically

### Reserved Fallback
- If rejected due to reserved terms:
  - fallback base `user`
  - reapply suffix and uniqueness rules

---

## Username Availability Check (AJAX)

### Endpoint
`GET /check-username?u=...`

### Response (Plain Text Only)
- `AVAILABLE`
- `TAKEN`
- `INVALID`

Live checking applies only when username field is non-empty.

---

## Password Rules

### Complexity
- ≥ 8 characters
- ≥ 1 uppercase
- ≥ 1 number
- ≥ 1 symbol

### No-Words Rule
Password MUST NOT contain:
- username
- email local-part
- reserved terms

Stored only as `password_hash`.

---

## User Creation
- Status: pending/unverified (`2`)
- `email_verified_at` = NULL
- No auto-login
- Profile row ensured

---

## Token System

### Registration Token
- `token_type = register_verify`
- SHA-256 hashed
- Expires in 24h
- Single-use via `used_at`

---

## Email Verification

### Endpoint
`GET /verify-email?token=...`

### Behaviour
- Validate token
- Activate user
- Consume token
- Render success page with login link

No auto-login.

---

## Email Sending

- Settings read from `#_settings` scope `mail`
- Fail closed if misconfigured
- Log to `storage/logs/`
- No defaults guessed

### Templates
- `register_verify`
- `welcome`

---

## Welcome Email
Sent after verification:
- includes username
- includes login link
- never includes passwords or tokens

---

## Login Restriction
Pending users cannot login until verified.

---

## Forbidden
- Admin UI
- JSON
- External services
- Password reset
- Captcha

---

## Phase Completion
Phase 6b complete when all rules above are implemented exactly.


# Phase 6C - FreeflowCMS Email System Upgrade Specification

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.

> **Status:** Authoritative core upgrade specification  
> **Applies to:** FreeflowCMS core mail system  
> **Audience:** AI coding agents and human developers  
> **Scope:** Mail wrapping, multipart delivery, Freewrite integration, admin UI

---

## 1. Purpose

This specification defines the **upgrade of the FreeflowCMS mail system** from a 
simple template-based sender into a **centralised, wrapper-based, multipart 
email system** that:

- Preserves existing mail behaviour
- Adds system-wide email wrapping
- Enforces HTML + text delivery
- Uses the Freewrite editor for authoring mail templates and wrappers
- Is safe, deterministic, and difficult to misuse

The intent is to make this a **low-risk, high-impact upgrade** with minimal 
changes to existing calling code.

---

## 2. Core Design Principle (Hard Rule)

  **MailService is the only component responsible for email layout and 
wrapping.**

- Core features and extensions provide **email content only** (subject + body).
- MailService **always applies** the system wrapper before sending.
- No feature, controller, or extension may send wrapped or pre-layout email 
directly.

---

## 3. Scope of Change

### 3.1 What Changes
- Addition of a **mail wrapper system**
- Upgrade to **multipart/alternative** email sending (HTML + text)
- Upgrade of mail template and wrapper editors to **Freewrite XML block 
documents**
- Minor changes to MailService pipeline to apply wrapping

### 3.2 What Does NOT Change
- How core features trigger emails
- How extensions trigger emails
- Existing mail template semantics (subject + body)
- Language handling in admin UI

---

## 4. Responsibilities

### 4.1 Core Features / Extensions
- Build email **content**:
  - Subject (plain string)
  - Body (via template or raw HTML/text)
- Call MailService
- **MUST NOT** apply wrappers or branding

### 4.2 MailService (Mailer)
MailService **MUST**:
- Resolve mail content
- Render HTML and text versions
- Apply the default mail wrapper
- Send email as multipart/alternative
- Handle transport and logging

---

## 5. Database Changes

### 5.1 Mail Templates (`#__mail_templates`) — Upgrade

Mail templates become **Freewrite-authored documents**.

Required fields:
- `id`
- `owner_type` (`core` | `extension`)
- `owner_key`
- `template_key`
- `subject` (plain string)
- `body_doc_xml` (LONGTEXT) — Freewrite XML block document
- `is_enabled`
- `created_at`, `updated_at`

Optional (recommended for performance):
- `html_cache` (MEDIUMTEXT)
- `text_cache` (MEDIUMTEXT)
- `cache_updated_at`

---

### 5.2 Mail Wrappers (`#__mail_wrappers`) — New Table

Mail wrappers define the **global email layout**.

Required fields:
- `id`
- `wrapper_key` (unique, e.g. `default`)
- `name`
- `wrapper_doc_xml` (LONGTEXT) — Freewrite XML block document
- `is_default` (TINYINT)
- `is_enabled` (TINYINT)
- `created_at`, `updated_at`

Optional cache fields:
- `html_cache`
- `text_cache`
- `cache_updated_at`

---

## 6. Freewrite Integration

### 6.1 Templates
- Mail template bodies **MUST** be authored using the Freewrite editor
- Stored as XML block documents
- Subject remains a plain string

### 6.2 Wrappers
- Mail wrappers **MUST** be authored using the Freewrite editor
- Wrapper documents **MUST include exactly one mail content placeholder**

---

## 7. Mail Content Placeholder

### 7.1 Definition
- The placeholder is represented by the shortcode:

```
[mailcontent]
```

- Implemented via a **dedicated Freewrite block**

### 7.2 Editor Behaviour
- Placeholder block is:
  - **Only insertable** in the Mail Wrapper editor
  - Inserted via a **toolbar button** (“Insert Mail Content”)
- Button behaviour:
  - Inserts placeholder if missing
  - Disabled or focuses existing placeholder if already present

### 7.3 Validation Rules
- Wrapper save **MUST fail** if:
  - Placeholder is missing
  - Placeholder appears more than once

---

## 8. Admin UI Changes

### 8.1 Navigation

**Admin → Settings → Mail** becomes a tabset:

1. Mail Settings (existing)
2. Mail Templates (existing, upgraded editor)
3. Mail Wrappers (new)

---

### 8.2 Mail Templates Editor

- Subject field (plain input)
- Freewrite editor for body
- Preview:
  - Rendered HTML
  - Rendered text
  - Wrapped preview using default wrapper
- Validation:
  - Enabled templates must render to both HTML and text

---

### 8.3 Mail Wrappers Editor

- Freewrite editor
- Toolbar button to insert placeholder
- Validation:
  - Exactly one placeholder required
- Preview:
  - Wrapper with sample injected content

---

## 9. MailService Pipeline (Authoritative)

### 9.1 Content Resolution
- From template (`sendTemplate`) **or**
- From raw content (`sendRaw`)

### 9.2 Rendering
1. Render template/body XML → `contentHtml`, `contentText`
2. Load default enabled wrapper
3. Render wrapper XML → `wrapperHtml`, `wrapperText`
4. Inject content:
   - Replace `[mailcontent]` in wrapper HTML with `contentHtml`
   - Replace `[mailcontent]` in wrapper text with `contentText`

---

### 9.3 Delivery

- MailService **MUST send multipart/alternative**:
  - `text/plain`
  - `text/html`

- If `mail_force_plain_text` is enabled:
  - Only `text/plain` is sent

---

## 10. Failure Behaviour

### 10.1 Wrapper Errors
- If no default wrapper exists, MailService **MUST fail loudly** and log
- Wrapper placeholder errors must not silently pass

### 10.2 Content Errors
- Missing body → fail
- Render errors → fail

---

## 11. Extension API Usage

### 11.1 Raw Send API

Extensions may send mail via:

```
MailService::sendRaw(to, subject, textBody?, htmlBody?)
```

Rules:
- Extensions provide **unwrapped content only**
- Wrapping is automatic
- Extensions cannot bypass wrapper

---

## 12. Security & Policy

- No JSON storage
- Extensions may read core tables but **must never write them**
- Wrapper enforcement is mandatory
- HTML sanitisation may be applied at MailService discretion

---

## 13. Migration Strategy

- Existing templates are migrated by:
  - Wrapping old HTML/text into a single Freewrite document
- A default wrapper is created during upgrade
- Existing mail flows continue to function

---

## 14. Build Notes for AI Coding Agents

- Follow Global Rules
- Do not invent new language keys unnecessarily
- Do not bypass wrapper logic
- Do not introduce JSON storage
- Multipart email support is mandatory
- Placeholder validation is mandatory

---

**End of Specification**






# Phase 7 - Freegate — FreeflowCMS Core Security Module Specification

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.

> **Status:** Authoritative build specification  
> **Type:** Core module (built into FreeflowCMS)  
> **Audience:** AI coding agents and human developers  
> **Scope:** Runtime security, WAF, adaptive protection, alerts, Devstore 
integration

---

## 1. Purpose & Positioning

**Freegate** is the core security module for FreeflowCMS. It provides a 
**WAF‑first, adaptive, explainable security layer** tightly integrated with core 
services (AUTH, ACL, routing, mail dispatcher, language system).

Freegate is *inspired by* products such as Wordfence, but is **not a clone**. It 
is explicitly designed for:

- Core‑first integration (not an add‑on bolt‑on)
- Explainable decisions and auditability
- Zero cloud dependency by default
- Safe defaults for distributed installations
- Strict adherence to FreeflowCMS Global Rules

---

## 2. Hard Requirements (Non‑Negotiable)

### 2.1 Global Rules Compliance
- Freegate **MUST** comply with all FreeflowCMS **Global Rules** wherever 
applicable.
- If a Global Rule does not apply, the implementation **MUST document why** 
(briefly, explicitly, no assumptions).

### 2.2 Language Key Reuse (Mandatory)
- Freegate **MUST reuse existing system language keys** wherever a suitable key 
already exists.
- New language keys **MUST NOT** be created unless no suitable existing key 
exists.
- Any newly created keys **MUST** be justified implicitly by necessity.

### 2.3 Language Key Naming Convention
All Freegate‑specific language keys **MUST** follow this exact format:

```
FFCMS.FREEGATE_<CONTEXT>_<KEY>="Label"
```

Where:
- `<CONTEXT>` = DASHBOARD, FIREWALL, RULES, SCAN, ALERTS, TOOLS, INTEGRATIONS
- `<KEY>` is concise and descriptive

**Label rules:**
- Labels **MUST be short and succinct**
- Avoid sentences in labels
- Long explanations belong in help text or emails

---

## 3. Architectural Placement

- Freegate is **built into core**, not an extension
- Uses core services:
  - AUTH / ACL
  - Routing
  - Request lifecycle hooks
  - CSRF
  - Mail dispatcher
  - Language service

Internally, Freegate **MUST be modular**, so it *can* be extracted later if 
required.

---

## 4. Operational Modes

### 4.1 Default Mode
- Default mode on first run: **EXTERNAL**
- Stored in settings table
- Behaviour is conservative and safe for distributed installs

### 4.2 Devstore Detection
Freegate performs non‑intrusive detection to determine whether Devstore is 
present:
- Extension/module registration
- Service container binding
- Route existence

Detection **MUST NOT** automatically change behaviour.

### 4.3 Devstore Mode Switch (Super‑only)

If Devstore is detected:
- A **Super‑only** switch becomes visible
- Modes:
  - **External** (default)
  - **Home**

If Devstore is **not detected**:
- No Devstore switch is shown
- No Devstore‑specific UI appears

All mode changes **MUST be logged**.

---

## 5. Devstore Integration Behaviour

### 5.1 External Mode
- No inbound exemptions created
- Freegate behaves as standard WAF + security module
- Outbound calls to the Devstore repo **MUST NOT be interfered with**

### 5.2 Home Mode

Inbound API exemptions enabled using **route‑based matching (R1)**:

Default patterns:
- `/api/devstore*`
- `/admin/devstore*`

Default action:
- **ALLOW**

Behaviour for matched routes:
- Skip deep payload heuristics
- Still apply:
  - Method sanity
  - Request size limits
  - Logging

All settings configurable under:
**Admin → Freegate → Protection → Integrations → Devstore**
the admin section MUST be installed in admin/settings as a Tab, with subtabs

---

## 6. WAF (Web Application Firewall)

### 6.1 Design Philosophy
- WAF‑first
- Balanced with scanner
- Explainable decisions
- Adaptive posture

### 6.2 Request Evaluation Flow
1. Allow/deny short‑circuit checks
2. Route‑based exemptions
3. Rate limits
4. Heuristic inspection
5. Challenge / block decision

### 6.3 Actions
- ALLOW
- THROTTLE
- CHALLENGE (optional)
- BLOCK (temporary only)

### 6.4 Challenge Types
- JavaScript challenge
- Proof‑of‑work
- Rate/delay challenge

Captcha **MUST NOT** be implemented.

---

## 7. Adaptive Protection

### 7.1 Adaptive Mode
- Default stance: **Adaptive**
- Starts calm, tightens based on signals

### 7.2 Triggers (All Supported, Configurable)
- WAF block rate
- Login failure rate
- 404 probe spikes
- Admin‑confirmed attacks
- Scanner critical findings

### 7.3 Safety Rails
- Never permanently block
- Never block Super users
- Caps on automated escalation

---

## 8. Learning System

### 8.1 Learning Modes
- **L1:** Bounded automatic tuning (weights / thresholds only)
- **L2:** Suggested rules requiring admin approval

### 8.2 Storage Rules
- **NO JSON STORAGE**
- All learning data stored in relational tables
- Full audit and rollback required

### 8.3 Constraints
- No new rule types created automatically
- No silent enforcement
- No learning on Super traffic

---

## 9. Scanner

Scanner exists primarily for:
- Integrity awareness
- Risk auditing
- Correlation with WAF events

Capabilities:
- Quick scan
- Full scan
- Integrity comparison
- Risk audit

Scanner output **MUST feed the Change Story Timeline**.

---

## 10. Explainable Security

Every security decision **MUST record**:
- Rule ID / reason code
- Confidence score
- Action taken
- Correlation ID

Admin UI must show:
- What happened
- Why it happened
- What could be done next

---

## 11. Change Story Timeline

A unified timeline correlating:
- WAF events
- Login activity
- Learning adjustments
- Scanner findings
- Devstore actions

This is a core differentiator.

---

## 12. Admin UI

### 12.1 Tabs (Combined)

1. Overview
2. Protection
3. Rules & Learning
4. Scan
5. Traffic & Logs
6. Tools

All UI **MUST** use Bootstrap 5.

---

## 13. Email Notifications

### 13.1 Mail System Usage
- Freegate **MUST use core mail dispatcher**
- Email content rendered via `#__mail_templates`
- Dispatcher handles wrapping/layout

### 13.2 Global Toggles (No Clutter)

Global settings:
- Send security emails to users: ON/OFF
- Send security emails to admins/super: ON/OFF

Applies to all Freegate events.

### 13.3 Templates

Namespace:
- `FREEGATE.*`

Examples:
- `FREEGATE.LOCKOUT.USER`
- `FREEGATE.LOCKOUT.ADMIN`
- `FREEGATE.ALERT.SUSPICIOUS_LOGIN`

### 13.4 Help Link
- Global help link URL
- Global include/exclude toggle
- Used in all user‑facing emails

---

## 14. Logging & Audit

- All actions logged
- Learning changes logged
- Mode switches logged
- Email sends logged

Retention configurable.

---

## 15. Permissions

Example permission keys:
- `freegate.view`
- `freegate.manage`
- `freegate.approve_rules`
- `freegate.recovery`

Super‑only:
- Recovery mode
- Mode switching
- WAF disable

---

## 16. Safety & Recovery

- Recovery mode (time‑limited)
- Maintenance/deploy mode
- Super users never permanently blocked

---

## 17. Build Notes for AI Coding Agents

- Follow Global Rules
- Reuse language keys
- No JSON storage
- Explainability is mandatory
- Defaults must be safe
- No cloud dependencies

---

**End of Specification**



# Phase 8 — Security: CAPTCHA + Anti-Spam Controls (Forward-Facing Forms)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Define CAPTCHA providers, built-in anti-spam controls, and enforcement rules for
**all forward-facing forms presented to visitors who are not logged in**.

This phase provides:
- A configurable CAPTCHA subsystem with provider selection
- Built-in anti-spam bot controls (honeypot + time-to-submit)
- Per form-type enablement (defaults apply)
- Session-based failure counting (“fail N times”) controls
- Admin Settings → Security tab to configure all of the above

This phase does not provide:
- Any external libraries or CDNs
- Any JSON persistence (in-memory JSON parsing is allowed only for CAPTCHA
verification responses)
- Any changes to logged-in form protection beyond existing CSRF expectations

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries, no external assets/CDNs
- No JSON anywhere EXCEPT: in-memory parsing of CAPTCHA verification responses
only; never persisted
- AJAX allowed but responses must be HTML or plain text only
- Bootstrap 5, responsive
- No inline CSS
- Admin-context session required for admin settings pages
- CSRF protection applies (existing system CSRF)

---

## Definitions

### Forward-facing form (in scope)
A form is in scope for Phase 8 enforcement if:
- It is presented to a visitor who is **not logged in** at the time the form is
shown.

If the user is logged in, Phase 8 controls do not apply; existing CSRF
protections are considered sufficient by design.

### Form types (core list for Phase 8)
Phase 8 applies configuration per core form type:

- `login`
- `registration`
- `password_reset_request`
- `password_reset_confirm`
- `email_verification_resend`

Only these form types are configured in this phase. Additional form types may be
introduced in later phases (without changing Phase 8 semantics).

---

## Defaults (install-safe)

Because provider keys are not available at install time:

- Default CAPTCHA provider: **none**
- Default per-form CAPTCHA enabled: **OFF**
- Default built-in anti-spam bot: **ON** (honeypot ON, time-to-submit ON, using
safe defaults)
- Default session failure counting: **OFF** unless explicitly enabled by admin

No forward-facing form may become unusable on a fresh install due to missing
CAPTCHA keys.

---

## CAPTCHA providers

### Provider selection
Admin can select exactly one global CAPTCHA provider:

- `none` (default)
- `hcaptcha`
- `recaptcha` (optional; not default)

Only one provider can be active at a time.

### Provider configuration (Settings → Security)
When provider is `hcaptcha`:
- `hcaptcha_site_key`
- `hcaptcha_secret_key`

When provider is `recaptcha`:
- `recaptcha_site_key`
- `recaptcha_secret_key`

Keys are stored in the settings table as normal string values. They are not
logged and must be treated as secrets.

### CAPTCHA verification responses (JSON exception)
- Provider verification endpoints return JSON.
- The system may parse the JSON response **in memory only** for verification.
- No JSON may be persisted to DB, session, logs, or files.

Implementation constraint:
- Use PHP built-in HTTP tooling (e.g. cURL) only.
- Do not introduce external packages.

---

## Built-in anti-spam bot (toggleable)

The built-in anti-spam bot exists and is configurable under Settings → Security.

### Mechanism 1: Honeypot
- Each configured forward-facing form includes a honeypot field.
- If the honeypot field is non-empty on submission, the submission fails.

Honeypot field:
- Must be added to the HTML form output for in-scope forms.
- Must be hidden using existing CSS classes/stylesheets (no inline CSS).
- Must not rely on JavaScript.

### Mechanism 2: Time-to-submit
- When an in-scope form is rendered, store a timestamp in session for that form
type.
- On submission, compute elapsed time.
- If elapsed seconds is less than a configured minimum threshold, the submission
fails.

Default threshold:
- A conservative minimum (e.g. 3 seconds) to avoid false positives,
configurable in Settings → Security.

Time-to-submit is per session and per form type.

---

## Session-based failure counting (optional)

Phase 8 provides an optional “fail N times” control, implemented session-based.

### Semantics
- Track failures per session, per form type.
- A “failure” is any submission rejected by:
  - CAPTCHA failure (when enabled and active)
  - honeypot failure (when enabled)
  - time-to-submit failure (when enabled)
- When failure count reaches N, the form type becomes blocked for the remainder
of the session (or until a configured cooldown expires if cooldown is enabled).

Configuration under Settings → Security:
- Enable/disable failure counting
- N (choices are fixed options, not free text):
  - 3, 5, 10
- Optional cooldown (if enabled):
  - 5 minutes, 15 minutes, 60 minutes' 240 minutes

If failure counting is disabled, the system does not block based on repeated
failures.

---

## Per-form configuration rules

For each core form type listed above, admin can configure:

- CAPTCHA enabled (default OFF)
- Honeypot enabled (default ON)
- Time-to-submit enabled (default ON)

### Provider = none interaction rule
If CAPTCHA is enabled for a form type but provider is `none`:
- Runtime behavior: treat CAPTCHA as OFF (do not block).
- Admin UI behavior: show a visible warning in Settings → Security that CAPTCHA
cannot be enforced without a provider.

This avoids install-time lockouts and avoids runtime breakage.

---

## Enforcement order (deterministic)

When a not-logged-in visitor submits an in-scope form, checks occur in this
order:

1. CSRF validation (existing system behavior)
2. Honeypot (if enabled)
3. Time-to-submit (if enabled)
4. CAPTCHA verification (if enabled AND provider is not `none`)
5. Failure count update (if enabled)
6. If blocked by failure counting, deny submission

Any failed step causes the submission to be rejected.

---

## Admin Settings UI

### Location
- `/admin/settings` → **Security tab**
Tab switching must follow the same mechanism used by other tabbed admin pages.

### Access control
- Admin session required
- ACL permission required (permission key introduced in this phase):
  - `manage_settings_security`

Role 998 (Super) always allows.

### Security tab layout (Bootstrap 5)
The Security tab contains:

1) **CAPTCHA Provider**
- Provider select: none / hcaptcha / recaptcha
- Key fields shown only for selected provider
- Keys are password-type inputs (never displayed back in clear text)
- Blank key on save means “do not overwrite existing key”

2) **Built-in Anti-Spam Bot**
- Master toggle: enable built-in anti-spam (default ON)
- Honeypot toggle (default ON)
- Time-to-submit toggle (default ON)
- Minimum seconds selector (fixed options):
  - 1, 2, 3, 5, 10

3) **Failure Counting (Session-based)**
- Enable failure counting toggle (default OFF)
- Attempts threshold selector:
  - 3, 5, 10
- Cooldown toggle (optional)
- Cooldown selector (if enabled):
  - 5 minutes, 15 minutes, 60 minutes

4) **Per-Form Controls (table)**
A list/table of core form types with icon/toggle controls for:
- CAPTCHA (per form)
- Honeypot (per form)
- Time-to-submit (per form)

Defaults shown clearly.

No JSON responses; any dynamic show/hide uses local JS only.

---

## Logging requirements (ties to Phase 10)

- Failures caused by anti-spam checks are not errors.
- They may be logged as events in `auth.log` (for login-related) or `admin.log`
if initiated by admin actions.
- Technical errors (e.g. network failure contacting CAPTCHA verify endpoint) are
logged to `error.log` only.

No secrets (keys/tokens) may appear in logs.

---

## Acceptance criteria

Phase 8 is complete when:

1. Settings → Security tab exists and saves configuration.
2. Default provider is `none` and does not block any form on fresh install.
3. hCaptcha is available and can be enabled; reCAPTCHA is available but not 
default.
4. Built-in anti-spam bot exists and can be toggled; honeypot and time-to-submit 
work.
5. CAPTCHA enablement is configurable per form type (default OFF).
6. Session-based failure counting (“fail N times”) is configurable and works 
when enabled.
7. JSON parsing is used only in memory for CAPTCHA verification responses and is 
never persisted.
8. No external assets, libraries, or JSON outputs exist.
9. All enforcement applies only to forms presented to users who are not logged 
in.


# Phase 9 — Admin Settings UI (Global + Mail Templates)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## 9.1 Phase purpose and scope

Phase 9 extends the **existing Admin Settings screen** (`/admin/settings`) by:

1. Adding **Mail settings** to the existing **Global** tab
2. Adding a new **Mail Templates** tab providing full CRUD management of mail 
templates

This phase **does not** introduce:
- New sidebar menu items
- New settings pages outside `/admin/settings`
- Mail sending logic, previews, or test emails
- Any new seeding of verification or welcome templates (already defined in Phase 
6)

This phase is strictly **admin UI + persistence + protection semantics**.

---

## 9.2 Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries, assets, CDNs
- No JSON anywhere (AJAX responses must be plain text or HTML)
- Bootstrap 5 only, fully responsive
- No inline CSS
- Core DB connection only via `Connection.php`
- Zulu admin UI conventions apply
- Phases must be complete and self-contained

---

## 9.3 Admin routing and tab semantics

### 9.3.1 Base route

- **`/admin/settings`**

This route already exists and renders a **tabbed interface** (pattern 
established by `/admin/roles`).

### 9.3.2 Tabs

Tabs for `/admin/settings` after Phase 9:

- **Global** (existing)
- **Mail Templates** (new)

Tab switching must follow the **same mechanism used by `/admin/roles`**
(e.g. query param, internal router state, or controller variable).
Phase 9 must not introduce a new tab-switching method.

---

## 9.4 Permissions (core ACL)

### 9.4.1 Permission keys

Phase 9 uses these core permissions:

- `manage_settings_global`
- `manage_settings_mail_templates`

Rules:
- Unknown permissions deny by default
- Role `998` (Super) always allows
- Permissions are DB-only (no registry files, no JSON)

### 9.4.2 Permission application

- Access to `/admin/settings` (Global tab save):
  - Requires `manage_settings_global`
- Access to **Mail Templates** tab and all its CRUD routes:
  - Requires `manage_settings_mail_templates`
- Ability to mark a template as **core**:
  - Requires role `998` (Super) explicitly

---

## 9.5 Data storage

### 9.5.1 Settings table

Existing or newly created core table:

**`#_settings`**

| Column | Type | Notes |
|------|------|------|
| setting_key | VARCHAR(120) PK | Unique |
| setting_group | VARCHAR(40) | `global` |
| setting_value | MEDIUMTEXT | Stored as string |
| value_type | VARCHAR(20) | `string`, `int`, `bool`, `text` |
| is_protected | TINYINT(1) | Default 0 |
| updated_at | DATETIME | Required |
| updated_by | INT | Nullable |

Rules:
- No JSON, no serialized PHP
- `bool` stored strictly as `'0'` or `'1'`
- Type coercion handled in model

### 9.5.2 Mail templates table (extension of existing Phase 6 table)

**`#_mail_templates`**

Add column:

| Column | Type | Notes |
|------|------|------|
| is_core | TINYINT(1) NOT NULL DEFAULT 0 | Deletion protection flag |

Full table includes (from earlier phases):

- id
- template_key (unique)
- name
- language_tag
- subject
- body_html
- body_text
- is_enabled
- is_core
- updated_at
- updated_by

Rules:
- `is_core = 1` → template is **not deletable**
- `is_core` can only transition `0 → 1`
- No automatic wiring of core templates to system flows

---

## 9.6 Global tab — Mail settings section

### 9.6.1 Settings keys (group = `global`)

Mail-related keys stored alongside other Global settings:

| Key | Type | Notes |
|----|------|------|
| mail_transport | string | `php_mail` or `smtp` |
| mail_from_email | string | Valid email |
| mail_from_name | string | Required |
| mail_replyto_email | string | Optional, valid email |
| smtp_host | string | Required if smtp |
| smtp_port | int | Required if smtp |
| smtp_security | string | `none`, `tls`, `ssl` |
| smtp_username | string | Optional |
| smtp_password | string | Write-only |
| mail_force_plain_text | bool | Required |
| mail_log_enabled | bool | Required |

### 9.6.2 UI behavior

- Mail settings appear as a **section** within the **Global tab**
- Transport selector controls visibility:
  - `php_mail` → hide SMTP fields
  - `smtp` → show SMTP fields
- Visibility is client-side only; server enforces validation

### 9.6.3 SMTP password rules

- Password field is always empty on GET
- On POST:
  - Blank value → do not overwrite stored password
  - Non-blank → overwrite stored password
- Stored as plain text (no crypto phase defined)

### 9.6.4 Save semantics

- Uses existing **Global tab Save button**
- One POST, all-or-nothing save
- Validation failure blocks entire save
- Success redirects back to `/admin/settings` with Global tab active

---

## 9.7 Mail Templates tab

### 9.7.1 Purpose

Provide **full CRUD management** of mail templates under Settings, following 
sitewide list rules.

### 9.7.2 List view (default tab content)

Route:
- `/admin/settings` with **Mail Templates tab active**

List characteristics:
- Overview list only
- Bootstrap table
- Icon-only row actions
- Toolbar (left only): **New**, **Close**

Columns:
- Name
- Template key
- Language
- Core indicator
- Enabled
- Updated

Row actions:
- Edit (pencil)
- Delete (trash) — **only if `is_core = 0`**

### 9.7.3 CRUD routes

Separate pages, consistent with admin conventions:

- Create: `/admin/settings/mailtemplates/new`
- Edit: `/admin/settings/mailtemplates/edit/{id}`
- Delete confirm: `/admin/settings/mailtemplates/delete/{id}`

Close action always returns to:
- `/admin/settings` with Mail Templates tab active

---

## 9.8 Mail template create/edit semantics

### 9.8.1 Fields

- Name (required)
- Template key
  - Required on create
  - Lowercase `a–z`, `0–9`, `_`
  - Max 80
  - Read-only on edit
- Language tag (default `en-GB`)
- Enabled (switch)
- Subject (required)
- Body HTML (required)
- Body Text (required)

### 9.8.2 Core designation

- Field: “Mark as core (protected)”
- Visible **only** to role `998`
- Only shown when `is_core = 0`
- Once saved as core:
  - Cannot be unset
  - Delete action is permanently disabled

### 9.8.3 Delete rules

- If `is_core = 1`:
  - Delete icon not shown
  - Delete route hard-blocks with error view
- Non-core templates:
  - Standard delete confirmation page
  - No inline deletes

---

## 9.9 Placeholder policy

Templates may contain simple placeholder tokens:

- `{{site_name}}`
- `{{site_url}}`
- `{{user_email}}`
- `{{verify_url}}`
- `{{login_url}}`

Rules:
- Stored verbatim
- No execution
- No preview or substitution in this phase
- Unknown tokens are left untouched by future renderers

---

## 9.10 Missing core template warning

Because required templates are seeded in **Phase 6**, Phase 9 must:

- Detect absence of expected core template keys
- Display a **non-blocking warning banner** in the Mail Templates tab
- No auto-recreation, no silent fixes

---

## 9.11 MVC components

### Controllers

- `AdminSettingsController`
  - Handles `/admin/settings`
  - Tab rendering
  - Global tab POST validation/save
- `AdminSettingsMailTemplatesController`
  - Handles all mail template CRUD routes

### Models

- `SettingsModel`
- `MailTemplatesModel`
  - Must enforce `is_core` delete protection
  - Must enforce Super-only core marking

### Views

- Existing Settings view updated to:
  - Include Mail settings section in Global tab
  - Add Mail Templates tab
- New views for:
  - Mail templates list
  - Create/edit
  - Delete confirmation

All views:
- Bootstrap 5 compliant
- No inline CSS
- No external assets

---

## 9.12 Acceptance criteria

Phase 9 is complete when:

1. `/admin/settings` shows Global and Mail Templates tabs
2. Global tab allows editing mail transport and SMTP settings with correct 
visibility and validation
3. Mail Templates tab lists templates with proper CRUD flows
4. Core templates are editable but not deletable
5. Super users can mark templates as core (one-way)
6. Missing required templates produce a visible admin warning
7. All permissions are enforced correctly
8. No JSON is produced anywhere
9. UI follows existing Zulu and sitewide list conventions



# Phase 10 — Admin Roles & Permissions Manager (CRUD + Inheritance, DB-Only 
Core ACL)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Phase Objective
Implement the **Admin Roles & Permissions Manager** in the Zulu admin interface, 
providing:
- Full CRUD for **custom roles**
- Immutable **system roles** with fixed IDs
- Immediate parent-based role inheritance
- DB-only core permissions model
- Permission matrix management
- Runtime ACL service with Super (998) allow-all

This phase finalises the **core access-control spine** of the system.

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Add a Roles menu entry to the admin sidebar
2. Implement the Roles & Permissions manager page with two tabs:
   - Roles
   - Permissions
3. Implement full CRUD for custom roles
4. Enforce immutability rules for system roles
5. Implement immediate parent role inheritance
6. Implement the permissions matrix editor
7. Enforce Super-only locked permissions (UI + save logic)
8. Implement runtime ACL evaluation using DB tables only
9. Enforce Super (998) allow-all at runtime

No other behaviour is permitted.

---

## Core Policy (Critical)
- Core permissions exist **only** in core DB tables:
  - `#_permissions`
  - `#_role_permissions`
- No permission registry files (PHP/XML) are permitted.
- Extensions/themes MUST NOT modify:
  - core ACL tables
  - core ACL runtime logic
- Extensions requiring permissions must manage and enforce them independently.

---

## Access Rules (Mandatory)
- All pages in this phase require a valid **admin session**
- Only Super (role ID 998) may:
  - create roles
  - edit roles
  - delete roles
  - modify the permission matrix
  - edit core permission metadata

---

## Required Schema Change
To support inheritance, the roles table must include an immediate parent 
pointer.

### `#_roles` additional field
- `parent_role_id` INT UNSIGNED NULL
- INDEX(`parent_role_id`)

Rules:
- No foreign keys (by global rule)
- System roles MUST have `parent_role_id = NULL`

If Phase 6 SQL is already frozen, this change is applied as a Phase 10 SQL 
delta using the `#_` prefix placeholder.

---

## Admin Navigation
- Sidebar entry: **Roles**
- Clicking Roles opens a single page with two tabs:
  - **Roles**
  - **Permissions**

---

## Roles Tab — Full CRUD with System Role Immutability

### Route
- `/admin/roles` (tabbed page, default tab = Roles)

### Layout Rules
- List layout only
- Clear visual separation between rows
- Row actions are **icons only**
- Toolbar buttons (left):
  - **New**
  - **Save**
  - **Close**

---

### Role Types

#### System Roles (Immutable)
System roles have fixed IDs and are immutable:

- IDs: `100`, `200`, `300`, `400`, `998`
- `is_system = 1`

Rules:
- Cannot be deleted
- Cannot have parent roles
- ID cannot change
- `is_system` cannot change
- Title edit:
  - Permitted for Super only (policy locked for this phase)

Role 998 is **Super User**.

---

#### Custom Roles (Full CRUD)
Custom roles have:
- `is_system = 0`
- Auto-assigned IDs
- Optional immediate parent role

Allowed operations:
- Create
- Edit
- Delete (subject to safety rules)

---

## Custom Role ID Allocation (Locked)

- Role IDs are **never user-entered**
- System role IDs are reserved and MUST NOT be assigned:
  - `100`, `200`, `300`, `400`, `998`

### Allocation Algorithm
When creating a custom role with `parent_role_id = P`:

1. Determine the lowest unused integer ID such that:
   - `id > P`
   - `id` is not already in use
   - `id` is not a reserved system ID
2. Assign that ID to the new role

This guarantees:
- Child roles always have higher IDs than their parent
- Role ordering naturally reflects hierarchy
- Reserved system anchors are preserved

---

## Create Role

### Route
- `/admin/roles/new`

### Fields
- Title (required)
- Parent role (optional dropdown)

### Validation Rules
- Title required
- Title uniqueness recommended (policy locked for this phase)
- Parent role:
  - must exist
  - must not be the role itself
  - must not create a circular hierarchy

Cycle detection MUST be enforced at save time.

---

## Edit Role

### Route
- `/admin/roles/edit?id={role_id}`

Rules:
- Custom roles:
  - Title editable
  - Parent role editable (cycle detection enforced)
- System roles:
  - Only title editable (Super only)
  - All other fields locked

---

## Delete Role

### Route
- `/admin/roles/delete?id={role_id}`

Rules:
- Separate confirmation step required
- Deletion MUST be refused if:
  - role is a system role
  - role is assigned to any user in `#_user_roles`
  - role is the parent of any other role (`parent_role_id`)
- Clear error message must be shown if deletion is refused

---

## Permissions Tab — Role-Permission Matrix with Inheritance

### Route
- `/admin/roles` (Permissions tab active)

### Layout Rules
- Table-based matrix (Bootstrap 5)
- Rows = permissions
- Columns = roles (system + custom)
- Clear spacing and separators
- No JSON

---

### Matrix Semantics

- The matrix defines **explicit permissions** assigned to a role
- Explicit permissions are stored in `#_role_permissions`

### Inheritance Rules
Effective permissions for a role are:
- its explicit permissions
- PLUS all effective permissions of its parent role
- recursively up the parent chain

Inherited permissions:
- MUST be visible in the UI
- MUST NOT be directly editable in the child role
- SHOULD be visually marked as “inherited”

Edits affect only the role’s explicit permissions.

### Matrix Cell UI (No Checkboxes)
- Each matrix cell MUST be an action icon, not a checkbox:
  - ON/allowed = green tick inside a circle
  - OFF/denied = red cross inside a circle
- Clicking the icon toggles the explicit permission for that role and updates 
the DB immediately.

### Immediate Update Behaviour
- The permissions matrix does not use a Save button.
- Each toggle performs an AJAX request and persists the change immediately.
- AJAX responses MUST be plain text or HTML only (no JSON).
- Toggle must update the icon state in-place on OK
- If the request returns DENY/ERROR, revert the icon and show a flash/notice

### Toggle Endpoint (Deterministic)
- A single admin-only endpoint MUST exist for toggles, e.g.:
  - `POST /admin/roles/perm-toggle`
- Required POST fields:
  - `role_id`
  - `perm_key`
  - `state` (either `1` or `0`)
- Response MUST be:
  - `OK` on success
  - `DENY` if not permitted
  - `ERROR` on failure

### Permission Rules During Toggle
- Only Super (role 998) may toggle permissions.
- Locked Super-only permissions MUST NOT be toggled for any role except 998.
- Inherited permissions (granted by parent) MUST be displayed as ON but 
disabled 
and not directly editable in child roles.
- Child roles cannot override inherited permissions to OFF in this phase.


---

## Locked Super-Only Permissions

The following permissions are **Super-only** and MUST NOT be granted to non-998 
roles:

- `manage_permissions`
- `manage_updates_core`
- `manage_updates_extensions`
- `manage_updates_themes`
- `manage_docs_build`
- `export_database`
- `import_database`
- `manage_extensions`
- `install_extensions`
- `run_maintenance`
- `use_block_html`

Enforcement:
- UI: checkboxes disabled for all roles except 998
- Save logic: invalid grants MUST be ignored or rejected deterministically

---

## Core Permission Definitions

- Permissions are stored only in `#_permissions`
- In this phase:
  - Creation and deletion of permissions is forbidden
  - Editing title/description is Super-only

---

## Runtime ACL Service (Required)

### Behaviour
- DB-only evaluation using:
  - `#_user_roles`
  - `#_roles` (parent traversal)
  - `#_role_permissions`
  - `#_permissions`

### Super Allow-All (Locked)
- If user has role ID `998`:
  - `can(perm_key)` MUST return `true` for all core permissions
  - DB matrix state is ignored for Super

### Evaluation Rules
1. Collect all roles assigned to user
2. For each role:
   - collect explicit permissions
   - walk parent chain and collect inherited permissions
3. Union all permissions
4. Allow only if:
   - permission exists in `#_permissions`
   - AND is present in union

### Safety Rules
- Unknown permission keys MUST be denied
- Parent cycle detection MUST exist:
  - enforced on role edit
  - guarded at runtime (fail-safe deny + log if detected)

---

## UI Conventions (Reaffirmed)
- Lists only for overview pages
- Separate pages for create/edit/delete confirmation
- Row actions are icons only
- Toolbar buttons:
  - **New**
  - **Save**
  - **Close**

---

## Forbidden Actions
This phase MUST NOT introduce:
- Extension/theme ACL integration
- Extension/theme installers or registries
- Profile/community UI
- JSON usage
- External libraries or assets

---

## Verification Checklist
- Roles page exists with Roles and Permissions tabs
- System roles are immutable and cannot be deleted
- Custom roles support full CRUD
- Role IDs are auto-assigned above parent ID
- Parent inheritance works and cycles are prevented
- Permissions matrix saves explicit permissions
- Inherited permissions are visible but not editable
- Super-only permissions are enforced (UI + save)
- Runtime ACL evaluation works with inheritance and 998 allow-all
- No forbidden features added

---

## Phase Completion Criteria
Phase 10 is complete when:
- Roles CRUD works as specified
- Role inheritance works as specified
- Permission matrix works with inheritance
- Runtime ACL enforcement is correct and safe
- All rules in this document are satisfied
- No forbidden actions occurred


# Phase 11 — Admin User Manager (CRUD)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Phase Objective
Implement the **Admin User Manager** in the Zulu admin interface, providing full 
CRUD capability over users using list-based layouts and separate edit pages.

This phase introduces:
- Admin user list
- Admin user create/edit/delete pages
- Role assignment (basic, no permissions UI yet)
- Enforcement of identity rules defined in earlier phases

This phase does **not** introduce:
- Roles manager UI
- Permissions matrix UI
- Profile UI (community-style)
- Settings UI expansion
- Extensions or themes

---

## Scope of This Phase
This phase performs **only** the following actions:

1. Implement admin-side user list page
2. Implement admin-side user create page
3. Implement admin-side user edit page
4. Implement admin-side user delete action
5. Allow admin to assign roles to users
6. Enforce username/email rules during admin edits

No other behaviour is permitted.

---

## Access Rules (Mandatory)
- All routes in this phase are **admin-only**
- A valid admin-context session is required

---

## Admin User List Page

### Route
- `/admin/users`

### Layout Rules
- List layout only (no cards)
- One user per row
- Clear visual separation between rows
- Suitable spacing between list items

### Columns (Minimum)
- Avatar (if present; small thumbnail)
- Username
- Email
- Status (active / pending / disabled)
- Created date
- Actions

### Actions (Icons Only)
- Edit (icon)
- Delete (icon)

No text labels for row actions.

### Toolbar Buttons (Left Toolbar Only)
- **New** (button)
- No Save button on list page

---

## Admin User Create Page

### Route
- `/admin/users/new`

### Layout Rules
- Separate page (not modal)
- Form-based
- Save / Close buttons appear in admin toolbar (left)
- No inline save buttons inside the form body

### Fields (Minimum)
- Username (optional; system allocation allowed)
- Email (required)
- Password (required)
- Password Confirm (required)
- Status
- Roles (multi-select or checkbox list)
- Avatar upload (optional)
- Cover upload (optional)

### Validation Rules
- Username rules identical to Phase 7
- Password rules identical to Phase 7
- Email must be unique
- Roles must reference existing role IDs only

---

## Admin User Edit Page

### Route
- `/admin/users/edit?id={user_id}`

### Layout Rules
- Separate page (not modal)
- Same structure as Create page
- Save / Close buttons in admin toolbar

### Edit Rules
- Username:
  - Cannot be changed unless:
    - current admin is Super (998), OR
    - user has `change_username` permission (to be enforced in later phase)
  - For this phase:
    - Only Super (998) may change usernames
- Email:
  - Editable
  - Email verification reflow is **not triggered** in this phase (handled later)
- Password:
  - Optional
  - If provided, must meet password rules
- Roles:
  - Editable
  - Multiple roles allowed

---

## Delete User

### Route
- `/admin/users/delete?id={user_id}`

### Behaviour
- Must require explicit confirmation (separate confirmation page or confirmation 
step)
- Must not be triggerable via GET alone without confirmation
- On delete:
  - User row is removed
  - Related rows in:
    - `#_user_roles`
    - `#_sessions`
    - `#_tokens`
    may be removed (or left for cleanup later; policy must be deterministic)

---

## Media Handling (Admin User Manager)

### Rules
- Avatar and cover uploads follow the same rules as registration:
  - Upload to `storage/tmp`
  - Validate
  - Move to `storage/media/{user_id}/`
- Paths stored as relative paths in DB
- No external image processing libraries

---

## UI Conventions (Reaffirmed)
- Lists only for overview pages
- Separate pages for create/edit
- Row actions are icons only
- Toolbar buttons:
  - **New**
  - **Save**
  - **Close**
- No inline form buttons inside content area

---

## Forbidden Actions
This phase MUST NOT introduce:
- Roles manager UI
- Permissions matrix UI
- Profile UI (tabs, community builder features)
- Settings UI changes
- JSON usage
- External libraries or assets

---

## Verification Checklist
- `/admin/users` lists users with clear row separation
- Row actions use icons only
- Create/edit pages are separate pages
- Save/New/Close buttons appear only in admin toolbar
- Username, email, and password rules enforced
- Roles can be assigned to users
- Only Super can change usernames in this phase
- No forbidden features added

---

## Phase Completion Criteria
Phase 11 is complete when:
- Admin User Manager CRUD works as specified
- UI conventions are followed exactly
- Identity rules from earlier phases are respected
- All rules in this document are satisfied
- No forbidden actions occurred


# Phase 12A — Content Media Inserter (Editor Popup + Upload)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Phase 12A defines the **content media inserter** used inside the content editor 
for inserting images into pages or posts.

This phase covers:
- A popup-based media picker/inserter
- Thumbnail-based browsing
- Uploading images directly from the inserter
- Strict per-user visibility rules
- Pre-insert placement, alignment, and sizing options (no resizing of files)
- Selecting an image for use as a **page/post featured image reference** 
(selection only)

This phase does **not** cover:
- General admin media management
- User profile media management
- Non-image files (handled by the Download Manager in a later phase)
- Image resizing systems (handled later via admin configuration)
- Frontend galleries or media display widgets
- Defining how featured images render on the frontend (later phase)

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- AJAX allowed, responses must be HTML or plain text only
- Bootstrap 5 compliant
- Fully responsive
- No inline CSS
- Core DB connection only
- Storage rules strictly enforced

---

## Definitions

### In-scope user
A user is in scope for Phase 12A when:
- They are logged in
- They are editing content via the content editor

Phase 12A does not apply to anonymous visitors.

---

## Visibility and access rules

### Media visibility
- A normal user may browse and select media **only** from:
  - `/storage/media/users/{user_id}/`
- Super user (role 998) may browse and select media from **any** folder under:
  - `/storage/media/`

### Upload destination
- All uploads performed via the content inserter are ultimately placed in:
  - `/storage/media/users/{user_id}/`
- Super users do not select alternate destinations in this phase.

---

## Supported file types

Phase 12A supports **images only**:

- JPG / JPEG
- PNG
- WEBP

No other file types are permitted.
Documents (PDF, DOC, etc.) are handled by the core Download Manager in a later 
phase.

---

## Upload flow (mandatory staged process)

All uploads performed via the content media popup MUST follow this exact flow:

1. **Initial upload**
   - File is uploaded to:
     - `/storage/tmp/`
   - No file may be written directly to `/storage/media/`.

2. **Sanitisation and validation**
   The uploaded file MUST pass all of the following checks:
   - File size ≤ **10 MB**
   - Image can be decoded as a real image (not just extension/MIME)
   - File type is one of the supported formats
   - Image dimensions do not exceed **4000 px** in width or height

   Failure at this stage:
   - Immediately deletes the temporary file
   - Returns a validation error to the popup UI (HTML only)

3. **Filename generation**
   - A **safe, generated filename** is created
   - Original filenames are not preserved
   - Filename collisions must be impossible or resolved deterministically

4. **Thumbnail generation**
   - A thumbnail is generated from the validated image
   - Thumbnail rules are defined below

5. **Final placement**
   - Original image is moved to:
     - `/storage/media/users/{user_id}/`
   - Thumbnail is moved to:
     - `/storage/media/users/{user_id}/thumbs/`

Only after all steps succeed is the upload considered complete.

---

## Thumbnail rules

- Exactly **one thumbnail** is generated per image
- Thumbnail dimensions:
  - Fit within **320 × 320**
  - Aspect ratio preserved
  - **No cropping**
- Thumbnail format:
  - Always **WEBP**
- Thumbnail storage location:
  - `/storage/media/users/{user_id}/thumbs/`
- Thumbnail filename:
  - Based on the generated base filename
  - Deterministic and unique
- Thumbnails are for **browsing only**
  - They are never inserted into content

---

## EXIF handling

- EXIF data is **preserved**
- No metadata stripping occurs in this phase

---

## Media inserter popup UI

### Presentation
- Inserter opens as a **popup/modal window**
- Uses Bootstrap 5 components
- Displays thumbnails for all visible images
- No inline CSS

### Browsing
- Users see only media they are permitted to see (per visibility rules)
- Thumbnails are used for browsing
- Selection targets the **full-size image**, not the thumbnail

### Upload
- Popup includes an upload control
- Uploads follow the staged flow defined above
- Upload errors are displayed inline in the popup (HTML only)

---

## Image insertion behavior

When an image is selected for insertion:

- The **full-size image path** is inserted into the content
- Thumbnail paths are never inserted
- The user is presented with options **before insertion**:
  - Alignment
  - Placement
  - Display sizing (presentation only)

### Placement and alignment
- Implemented using **Bootstrap classes and/or core CSS classes**
- No inline styles are permitted
- Options are restricted to a fixed, predefined set
- Inserted markup must remain theme-safe

### Sizing
- “Sizing” affects **display only**
- No image resizing or variant selection occurs in this phase
- All sizing is expressed via classes or attributes allowed by system rules

---

## Featured image selection (page/post)

The popup must also support selecting an image as the **featured image 
reference** for the current page/post.

### Rules
- Featured image selection uses the **full-size image path** (never the 
thumbnail)
- Selecting a featured image does **not** insert anything into the content body
- The popup must provide a distinct action for this purpose:
  - “Use as featured image” (or equivalent), separate from “Insert into content”
- On selection, the popup returns the chosen full-size image path to the editor 
form so it can be stored later as the page/post featured image reference

This phase defines the selection and value passing only. The storage schema and 
frontend usage of the featured image are defined in later phases.

---

## Content format

- The inserter inserts the reference to the selected image using the system’s 
defined content markup strategy
- This phase does not define new content markup formats
- Inserted content must be deterministic and stable across themes

---

## Error handling and logging

- Validation failures are user-facing but not system errors
- Upload processing failures caused by system exceptions are logged to:
  - `error.log`
- No secrets, paths, or internal details are exposed to the user

---

## Security constraints

- All uploads are subject to server-side validation
- Client-side validation is advisory only
- Temporary files in `/storage/tmp/` must not be executable
- No user input may influence filesystem paths directly

---

## Acceptance criteria

Phase 12A is complete when:

1. The content editor provides a popup media inserter
2. Users can browse only their own media; Super can browse all
3. Users can upload images via the popup
4. Uploads follow the staged tmp → sanitise → thumbnail → move process
5. Only JPG, PNG, and WEBP files are accepted
6. File size and dimension limits are enforced
7. Thumbnails are generated as specified and stored correctly
8. Inserted content references the full-size image, not the thumbnail
9. Alignment and placement options work without inline CSS
10. Featured image selection is available and passes the full-size image path 
without inserting into body content
11. No JSON, external assets, or unsafe writes occur

# Phase 12B — Admin Media Manager

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Phase 12B defines the **Admin Media Manager**, a system-level media management 
interface used by administrators to manage files stored under `/storage/media/`.

This phase provides:
- Browsing media across all permitted storage buckets
- Uploading, deleting, and basic organisation of media files
- Strict enforcement of core / extension / theme media boundaries
- A read-only view into user media (except where explicitly allowed)

This phase does **not** provide:
- Content editor insertion UI (handled in Phase 12A)
- User self-service media management (handled in Phase 12C)
- Media galleries or frontend display logic
- Image resizing systems beyond thumbnails already defined
- Any external asset loading or libraries

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- AJAX allowed, responses must be HTML or plain text only
- Bootstrap 5 compliant
- Fully responsive
- No inline CSS
- Core DB connection only
- Core filesystem boundaries must be enforced at runtime

---

## Media storage structure (authoritative)

The Admin Media Manager operates strictly within:



Buckets under `/storage/media/`:

- `/storage/media/system/`
- `/storage/media/users/{user_id}/`
- `/storage/media/extensions/{ext_key}/`
- `/storage/media/themes/{theme_key}/`

No other top-level buckets are permitted.

---

## Access control

### Default access policy
- Admin Media Manager is **Super-only** by default.
- The permissions system may later grant access to non-super roles, but Phase 
12B must be fully functional with Super-only access.

### Permission keys (defined for ACL and future role grants)
Phase 12B introduces the following permission keys:

- `manage_media_admin` (access the admin media manager UI)
- `media_browse_all` (browse all buckets)
- `media_upload` (upload new files)
- `media_delete` (delete files)
- `media_edit_image` (edit operations such as rotate/flip and generating 
variants)
- `media_crop_image` (cropping operations)
- `media_regenerate_thumbnails` (regenerate thumbnails to enforced size)
- `media_manage_system_bucket` (write operations in `/storage/media/system/`)
- `media_manage_user_buckets` (write operations in 
`/storage/media/users/{user_id}/`)
- `media_manage_ext_theme_buckets` (write operations in 
`/storage/media/extensions/{ext_key}/` and `/storage/media/themes/{theme_key}/`)

Defaults in this phase:
- Super has all capabilities (role 998 allow-all).
- No other role is assumed to have any of these unless explicitly granted later.

---

## Core constraints

### No move / no rename (hard rule)
The Admin Media Manager MUST NOT provide:
- rename operations
- move operations
- cross-folder relocation tools
- cross-bucket relocation tools

This rule is absolute to prevent broken content references.

### Non-destructive edits (hard rule)
All image edits are **non-destructive**:

- The original file remains unchanged.
- An edit operation produces a **new image file** with a generated safe 
filename.
- The admin media manager never updates existing content references.
- Edited images exist as additional media assets only.

---

## Supported file types

Admin Media Manager supports image management consistent with Phase 12A, minimum 
set:

- JPG / JPEG
- PNG
- WEBP

Additional file types are not introduced in this phase.

---

## Thumbnail enforcement (system size)

### Enforced thumbnail size
Admin Media Manager must respect a **single enforced system thumbnail size** for 
browsing thumbnails.

- The system has one canonical thumbnail size for thumbnails used in media UIs.
- When an edited image is created, its thumbnail must be generated automatically 
at this enforced size.

This phase does not define the admin UI for changing the enforced thumbnail 
size; it enforces the existing/current configured size.

### Thumbnail shape rules
- Thumbnails are generated to fit within the enforced size
- Aspect ratio preserved
- No cropping by default unless explicitly chosen by the admin as part of the 
edit workflow

Storage convention for thumbnails must be consistent across media systems. If 
user media uses:

- `/storage/media/users/{user_id}/thumbs/`

then Admin Media Manager must respect and use the same convention for user 
buckets. Equivalent thumbs folder rules apply for other buckets.

---

## Admin Media Manager UI

### Placement
Admin UI provides an Admin Media Manager entry point accessible to Super users.

Exact navigation placement must follow existing Zulu conventions.

### Overview display
- Browseable view of media items with thumbnails where available
- Responsive Bootstrap layout
- Overview is a list/grid; actions are icon-driven
- No inline CSS

### Bucket navigation
- Super can browse all buckets:
  - system
  - all user folders
  - ext/theme buckets
- All path handling must be internal and allow-list-based; no user-supplied 
filesystem paths are trusted.

---

## Admin operations

### Upload
Super can upload images into any selected bucket.

Upload rules:
- Server-side validation mandatory
- Safe generated filename mandatory
- Upload must not allow directory traversal
- Upload must not overwrite existing files

(Where uploads stage temporarily is an implementation detail; staging is allowed 
but must not violate storage policy.)

### Delete
Super can delete:
- original images
- edited variants
- thumbnails

Deletion rules:
- Confirmation required
- Deleting an image must also delete its associated thumbnail(s)
- No soft-delete in this phase

### Edit / create variant (non-destructive)
Admin may perform suitable edit operations, including at minimum:
- rotate
- crop
- other basic image transformations considered suitable

Rules:
- Produces a new image file with generated safe name
- Original remains untouched
- Automatically generates thumbnail(s) for the new file at enforced system size
- New variant is stored in the same bucket as the original

### Regenerate thumbnails
Admin may regenerate thumbnails:
- for a selected image
- or bulk regenerate within a bucket (optional)

Rules:
- Regeneration outputs thumbnails at the enforced system size
- No JSON output
- Operations must be permission-gated

---

## Logging (ties to Phase 10)

- Admin operations are logged as events (e.g., in `admin.log`) without secrets.
- Technical failures are logged to `error.log` only.
- No credentials, tokens, or sensitive config values are ever logged.

---

## Security constraints

- All filesystem access is canonicalised and constrained to `/storage/media/`
- All bucket and path access is allow-list validated
- No absolute filesystem paths are exposed in UI
- No user-supplied path fragments are trusted
- No external assets are loaded

---

## Acceptance criteria

Phase 12B is complete when:

1. Super admin can browse all buckets under `/storage/media/`
2. Uploads enforce safe filename generation and server-side validation
3. Delete operations work with confirmation and remove associated thumbnails
4. Image edit operations are non-destructive and produce new files
5. Edited images trigger automatic thumbnail generation to enforced system size
6. Thumbnail regeneration works as a managed operation
7. No move/rename functionality exists anywhere in Admin Media Manager
8. No content references are rewritten by Admin Media Manager
9. All access is Super-only by default and permission keys exist for future 
grants
10. No JSON output or external asset loading occurs


# Phase 12C — User Profile Media (Summary + Limited Upload)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Phase 12C defines **media handling within the user profile interface**.

This phase provides:
- A **summary-only media view** in the user profile
- Limited, purpose-specific uploads (avatar and cover image only)
- A gateway link to the Admin Media Manager for full management
- Permission-scoped handling of “own media” capabilities

This phase does **not** provide:
- Full media browsing or management in the user profile
- Deleting, renaming, moving, or editing arbitrary media from the profile
- Content editor insertion UI (Phase 12A)
- Admin-wide media management (Phase 12B)

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- Bootstrap 5 compliant
- Fully responsive
- No inline CSS
- Site and admin sessions are separated
- Core storage rules enforced

---

## User profile media tab (summary view)

### Purpose

The media tab in the user profile provides **visibility only**, not management.

It exists to:
- Give the user awareness of their stored media
- Provide a safe, clear path to full media management in the admin interface

### Display rules

The media tab shows:

1. **Total image count**
   - Count includes **original images only**
   - Thumbnails are excluded from the count

2. **Random thumbnails**
   - Exactly **4 thumbnails** selected at random from the user’s images
   - If fewer than 4 images exist, show all available
   - If no images exist, show an empty-state message
   - Thumbnails are read from:
     - `/storage/media/users/{user_id}/thumbs/`

3. **Manage media link**
   - A link labelled clearly (e.g. “Manage media”)
   - Opens in a **new browser window**
   - Points to the Admin Media Manager
   - Displays a warning message explaining:
     - The admin area uses a separate session
     - The user will need to log in again

No full image list, pagination, or management controls are shown in the profile.

---

## Uploads from user profile

### Allowed uploads (strictly limited)

From the user profile interface, a user may upload images **only** for:

- **Avatar**
- **Cover image**

No other image uploads are permitted from the profile UI.

### Upload rules

Avatar and cover uploads must follow the same core validation rules as Phase 
12A:

- File types: JPG / JPEG, PNG, WEBP
- Max file size: **10 MB**
- Max dimensions: **4000 px** width or height
- Upload staging via `/storage/tmp/`
- Sanitisation before final placement
- Safe generated filename
- Thumbnail generation where applicable

Avatar/cover images are stored within the user’s media bucket and referenced 
appropriately by the user profile system.

---

## Permissions model

### Base permission

Phase 12C introduces the concept of **“own media” permissions**.

Base permission:
- `manage_own_images`

This permission controls whether a user may access media-related actions for 
**their own media only** in admin contexts.

### `_own` permission variants

All admin media permissions defined in Phase 12B may have `_own` variants, for 
example:

- `media_upload_own`
- `media_delete_own`
- `media_edit_image_own`
- `media_crop_image_own`
- `media_regenerate_thumbnails_own`

Rules:
- `_own` permissions apply **only** to `/storage/media/users/{user_id}/`
- They never grant access to other users’ media
- They never grant access to `system`, `{ext_key}`, or `{theme_key}` buckets

Default policy:
- Regular users have no admin media permissions unless explicitly granted
- Super (role 998) is not constrained by `_own` rules

---

## Relationship to Admin Media Manager

- The user profile media tab does **not** replace the Admin Media Manager
- All real media management (browsing, editing, deleting, regenerating 
thumbnails) occurs in admin
- The profile tab acts as:
  - a summary
  - a controlled upload point for avatar/cover
  - a gateway to admin media tools

---

## Security constraints

- No filesystem paths are exposed in the profile UI
- Users cannot infer the structure of `/storage/media/`
- No user input controls filesystem navigation
- Profile UI cannot bypass admin permissions

---

## Acceptance criteria

Phase 12C is complete when:

1. User profile includes a media/images tab
2. The tab shows originals-only image count
3. The tab displays 4 random thumbnails (or fewer if unavailable)
4. No full media list is shown in the profile
5. A “Manage media” link opens the admin interface in a new window with login 
warning
6. Users can upload only avatar and cover images from their profile
7. All avatar/cover uploads follow core validation and sanitisation rules
8. `_own` media permissions are defined and enforced
9. No JSON, external assets, or unsafe access paths are used


# Phase 13 — Freewrite (Core Block-Based WYSIWYG Editor)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Phase 13 defines **Freewrite**, the core content editor for FreeflowCMS.

Freewrite is a **single, block-based editor** with a **TinyMCE-like look and 
feel**, providing:
- Structured block placement and editing
- Rich WYSIWYG editing inside text blocks
- Grid-based layout without tables
- Deterministic, XML-based content storage
- Tight integration with core media and document insertion
- Permission-gated advanced blocks (HTML, embeds)

This phase defines the editor **engine, storage contract, UI behaviour, and 
core 
block set**.

This phase does **not** define:
- Page/post workflows (handled in Content Management)
- Publishing logic or states
- Versioning or revisions
- Menu systems
- Frontend theming rules beyond valid output
- File storage management for documents (handled later)

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- PHP and XML only
- Fully Bootstrap 5 compliant
- Fully responsive
- No inline CSS
- Core system untouchable at runtime
- All behaviour implemented (no stubs)

---

## Asset location (authoritative)

Freewrite is a **core-bundled editor**.

Assets live at:

- `/core/assets/lib/editors/freewrite/`

Rules:
- Assets are served locally only
- No runtime asset relocation
- No external dependencies
- Visual styling MUST resemble TinyMCE (toolbar layout, icons, spacing, 
interaction patterns)

---

## Editor model (authoritative)

### Single editor surface

Freewrite is **one editor**, not multiple modes.

- The document is always treated as a **block document**
- Blocks are displayed as a vertical stack
- Cursor interaction is always scoped to a block
- Rich text editing occurs only inside text-capable blocks

There is **no separate Gutenberg editor**.

---

## Canonical content storage

### Canonical format

All content edited by Freewrite is stored in the database as **Freewrite XML**.

- XML is the **single source of truth**
- No JSON storage
- No parallel HTML source is required in this phase

### High-level structure

Example (illustrative):

    <freewrite version="1">
      <block type="heading" level="2">Title</block>
      <block type="richtext">
        <![CDATA[
          <p>Formatted text with <strong>markup</strong>.</p>
        ]]>
      </block>
      <block type="image" src="/storage/media/12/example.webp" />
    </freewrite>

Rules:
- XML must be well-formed at all times
- Block order is authoritative
- Attributes are explicit, not inferred
- Sanitisation occurs on save

---

## Block rules

### Block fundamentals

- Each block has:
  - a `type`
  - optional attributes
  - optional inner content
- Blocks are **top-level** unless placed inside layout containers (grid/columns)
- Blocks may not arbitrarily nest unless explicitly defined as containers

---

## Rich Text (WYSIWYG) behaviour

### Rich Text block

- `richtext` is the primary text-editing block
- Contains sanitized HTML wrapped in CDATA
- Supports inline formatting only (no inline styles)

Allowed inline elements (minimum allow-list):
- `p`, `br`
- `strong`, `em`, `u`, `s`
- `a` (safe attributes only)
- `code`, `kbd`
- `sub`, `sup`
- `span` (class-only, no styles)

### WYSIWYG UI requirements (TinyMCE-like)

- Toolbar appearance and interaction MUST resemble TinyMCE
- Toolbar includes an icon for **every available block type**
- Inline formatting tools affect only selection inside a richtext block
- Block insertion tools insert blocks at block boundaries

---

## Wrap-selection into blocks (mandatory)

### Purpose

Freewrite MUST allow users to:
- Paste a large body of formatted text
- Select a portion of that text
- Convert the selection into a block (e.g. heading, quote, list)

### Rules

- Wrapping is allowed **only inside a single richtext block**
- Only **textual blocks** may wrap selections

Textual wrap-capable blocks include:
- Heading
- Paragraph
- Quote / Pullquote
- List (ordered/unordered)
- Code
- Callout / Alert

### Behaviour

When wrapping:
1. The richtext block is split into:
   - content before selection
   - the new block
   - content after selection
2. The new block replaces the selected content
3. Formatting is normalised as required by block type

### Heading default

- Heading wrap defaults to **H2**
- Level may be changed after insertion

---

## Layout without tables

### Grid / Columns block (mandatory)

- Layout is handled via a **Grid / Columns block**
- No HTML tables for layout

Rules:
- Grid defines rows and columns
- Columns use Bootstrap grid classes
- Columns contain blocks
- Grid nesting is allowed, but implementation may enforce a sane maximum depth

---

## Core block catalog (initial build)

Freewrite ships with a **comprehensive core block set**.

### Text blocks
- Paragraph
- Heading (H1–H6)
- Lead text
- Small text
- Quote
- Pullquote
- Code
- Preformatted
- Divider
- Spacer

### Lists
- Bulleted list
- Numbered list
- Checklist
- Definition list

### Layout
- Grid / Columns
- Section
- Group
- Container
- Card
- Tabs
- Accordion
- Callout / Alert

### Media
- Image (via media popup)
- Gallery
- Figure with caption
- Icon

### Data
- Table (first-class block)
- Key/value list

### Links & files
- Button
- File / Document link (from `/storage/media/users/{user_id}/files`)
- Link card

### Embeds & advanced (permission-gated)
- Embed block (`use_block_embed`)
- Raw HTML block (`use_block_html`)
- Shortcode block

---

## Media integration

### Image insertion
- Image block and toolbar icon open the **media popup**
- Uses Phase 12A rules
- Inserts full-size image reference
- Thumbnail never inserted

### Featured image
- Supported via media popup
- Selection stored separately by content system (later phase)

---

## Document insertion

- Document block and toolbar icon provided
- Inserts a simple link in rendered HTML:
  - `<a href="...">filename</a>`
- Source restricted to:
  - `/storage/media/users/{user_id}/files/`
- No upload or management defined in this phase

---

## Permissions integration

The following permissions apply:

- `use_block_html` — required to use Raw HTML block
- `use_block_embed` — required to use Embed block

Users without permission:
- Cannot insert restricted blocks
- Cannot edit existing restricted blocks
- Restricted blocks are shown read-only

---

## Sanitisation and safety

- All content is sanitised on save
- HTML inside richtext blocks is filtered against allow-list
- Embed and HTML blocks must be validated for safety prior to allowing render
- No inline styles allowed anywhere
- No script execution allowed

---

## Rendering contract (editor output)

- Editor output must be valid HTML when rendered
- Blocks emit Bootstrap classes and/or core CSS classes
- No inline CSS
- Output must be deterministic from XML input

---

## Logging

- Editor errors are logged to `error.log`
- No content is logged
- No user input is logged verbatim

---

## Acceptance criteria

Phase 13 is complete when:

1. Freewrite assets exist under `/core/assets/lib/editors/freewrite/`
2. Editor UI visually resembles TinyMCE
3. Content is stored exclusively as Freewrite XML
4. Block-based editing is enforced at all times
5. Richtext editing works only inside richtext blocks
6. Wrap-selection into textual blocks works as defined
7. Grid/columns layout works without tables
8. All core blocks are available and insertable
9. Media and document insertion integrate correctly
10. Permission-gated blocks are enforced
11. No JSON or external assets are used


# Phase 14 — Content Management (Pages, Posts, Categories)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Implement admin UI and runtime storage for:
- Pages management
- Posts management
- Categories management (nested)

This phase includes:
- Create/Edit
- Publish/Unpublish
- Slugs and routing integration
- Featured image selection integration (selection only; uses existing media 
popup rules)
- ACL-gated access

This phase does NOT include:
- Versioning / restore versions
- Trash / soft delete / restore / permanent delete
- Menus manager
- Elements system
- Docs builder

---

## Global rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer / vendor
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- Bootstrap 5 compliant, fully responsive
- No inline CSS
- All behaviour implemented (no stubs)
- Freewrite is the editor (Phase 16) and stores content as XML

---

## Content storage model (authoritative)

### Freewrite XML (mandatory)
Pages and Posts content MUST be stored as **Freewrite XML** (canonical).

- Column name: `content_xml`
- XML must be well-formed
- Sanitise on save:
  - validate XML structure
  - sanitise HTML inside `richtext` CDATA blocks
  - enforce permission-gated blocks:
    - `use_block_html`
    - `use_block_embed`

No `content_html` canonical storage is used in this phase.

---

## Routing (authoritative)

### Static routes MUST win
The router MUST match **static routes before dynamic routes**.
This is mandatory to prevent slug-based routing from consuming reserved paths.

### Public content routes
- Pages are always:
  - `/page/{slug}`
- Posts are always:
  - `/blog/{slug}`

Rules:
- Only content with status `published` may render publicly.
- Unpublished/draft/archived content must return 404 on public routes (not a 
login prompt).

---

## Slug rules (mandatory)

### Format
Slugs must be:
- lowercase
- `a–z 0–9 -` only
- no spaces
- no leading/trailing hyphen
- max length enforced deterministically (e.g. 160)

### Uniqueness
- Page slugs are unique within Pages.
- Post slugs are unique within Posts.
- Category slugs are unique within Categories.

(They do not need to be globally unique across all three, because their route 
prefixes differ.)

### Reserved prefixes
Slugs must not collide with reserved route prefixes or top-level system paths. 
At minimum, deny:
- `admin`
- `install`
- `page`
- `blog`
- `storage`
- `core`
- `extensions`
- `themes`
- `api` (if used later)

If a slug violates rules, the save must fail with a clear validation message.

---

## Status model

Pages and Posts support these statuses:
- `draft`
- `published`
- `unpublished`
- `archived`

Rules:
- Only `published` renders publicly.
- Changing to `published` sets `published_at` if not already set.
- Changing away from `published` does not erase `published_at` (historical 
record).

---

## Database tables

### `#__pages`
Minimum fields:
- `id` INT PK
- `title` (required)
- `slug` (required, unique)
- `content_xml` (required)
- `excerpt` (nullable)
- `status` (required)
- `featured_image_path` (nullable)  *(stores the chosen full-size media path; 
selection via media UI)*
- `created_by` (user id)
- `created_at`
- `modified_by` (user id)
- `modified_at`
- `published_at` (nullable)

Notes:
- No `parent_id` on pages in this phase.

### `#__posts`
Minimum fields:
- `id` INT PK
- `title` (required)
- `slug` (required, unique)
- `content_xml` (required)
- `excerpt` (nullable)
- `status` (required)
- `featured_image_path` (nullable)
- `category_id` (nullable FK to categories)
- `author_id` (user id)
- `created_at`
- `modified_by` (user id)
- `modified_at`
- `published_at` (nullable)

### `#__categories`
Minimum fields:
- `id` INT PK
- `title` (required)
- `slug` (required, unique)
- `parent_id` (nullable; enables nesting)
- `description` (nullable)
- `created_by`
- `created_at`
- `modified_by`
- `modified_at`

Rules:
- Category nesting must prevent cycles (no category can become its own 
ancestor).

---

## Admin routes (minimum)

Pages:
- `/admin/content/pages` (list)
- `/admin/content/pages/new`
- `/admin/content/pages/{id}/edit`

Posts:
- `/admin/content/posts` (list)
- `/admin/content/posts/new`
- `/admin/content/posts/{id}/edit`

Categories:
- `/admin/content/categories` (list)
- `/admin/content/categories/new`
- `/admin/content/categories/{id}/edit`

---

## Admin UI conventions (Zulu)

- Lists are overview only
- Separate pages for create/edit
- Row actions are icons only
- Toolbar buttons : New / Save / Close
- No inline form buttons
- Fully responsive
- Fixed layout, collapsible sidebar toggle (not hamburger)

---

## Editor integration

Pages and Posts editor forms include:
- Title (required)
- Slug (auto from title, editable, validated)
- Content (Freewrite editor)
- Excerpt (optional)
- Status selector (permission-gated for publish actions)
- Featured image selector (uses existing media modal; stores full-size image 
path)
- Posts only: Category selector (nullable)

---

## Permissions (core ACL)

Define content permissions (DB-only):

- `manage_content`:
  - view lists
  - create new items
  - edit existing items
- `publish_content`:
  - set status to `published` / `unpublished` / `archived`
  - publish/unpublish actions must be server-side enforced

Defaults:
- Deny by default
- Role 998 always allows

(If you later want finer-grain “edit_own vs edit_all”, that is not introduced 
in 
this phase unless explicitly added.)

---

## Logging (ties to Phase 10)

Log content events (event logs) at minimum:
- create page/post/category
- edit page/post/category
- publish/unpublish/archive transitions
- changes to slug
- featured image selection change

Errors go to `error.log`.

No content body is written to logs.

---

## Acceptance criteria

Phase 14 is complete when:

1. Pages, Posts, Categories tables exist and are used
2. Pages and Posts store content as Freewrite XML (`content_xml`)
3. Slugs validate and enforce reserved prefixes
4. Router matches static routes before dynamic routes
5. Public routing works:
   - `/page/{slug}` for pages
   - `/blog/{slug}` for posts
6. Only published content renders publicly
7. Admin UI exists for pages/posts/categories using Zulu conventions
8. ACL gates access and publish actions server-side
9. All required events are logged appropriately


# Phase 15 — Elements Manager + Builder (Widgets / Modules)


This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.

---

## Scope

Implement the **Elements Manager + Builder**, the system responsible for 
creating, configuring, enabling, ordering, and assigning reusable **elements** 
(widgets/modules) to **theme-defined template positions**, including 
**assignment targets** and **conditional visibility**.

This phase provides:
- CRUD for element instances
- Selection of template positions defined by the active theme
- A seeded catalog of core element types
- Per-element configuration and class-based styling options
- **Elements Builder UI** for visual ordering within positions
- **Assignment targets** (global and route-based)
- **Conditional visibility rules** (authentication + optional role restriction)
- Menu rendering with full Bootstrap 5 navigation support
- Content listing elements (Pages, Posts, Categories)
- HTML, Embed, and URL elements (permission-gated where applicable)

This phase does NOT provide:
- Theme rendering logic (elements are retrieved, not rendered here)
- Menu position definition UI (positions are theme-defined)
- Versioning or trash for elements
- Inline CSS or external assets
- External libraries or CDNs
- JSON anywhere

---

## Global rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer or vendor directory
- No external libraries or CDNs
- No external asset loading
- **No JSON anywhere**
- **PHP and XML only**
- Fully Bootstrap 5 compliant
- Fully responsive
- No inline CSS
- Admin UI uses Zulu conventions
- All behaviour implemented (no stubs)

---

## Conceptual model

- **Element Types** define *what* can exist (Menu, Pages, Posts, etc.)
- **Elements** are *instances* of a type, configured and placed in a position
- **Template Positions** are defined by themes and stored in the database
- **Assignment Targets** define *where* a set of elements applies
- **Conditions** define *whether* an element instance renders
- Elements are rendered later by the theme/element runtime

---

## Database tables

### `#__element_types` (element definitions)

Stores the catalog of available element types, including seeded core types.

Minimum fields:
- `id` INT PK
- `type_key` (unique, lowercase, a–z0–9_-)
- `name`
- `description`
- `is_core` (boolean; core types protected)
- `is_enabled` (boolean)
- `created_at`
- `created_by`

Rules:
- Core element types are seeded at install
- Core types cannot be deleted
- Disabling a type prevents creating new elements of that type

---

### `#__elements` (element instances)

Stores created and positioned elements.

Minimum fields:
- `id` INT PK
- `element_type_id` FK → `#__element_types`
- `title` (admin label)
- `is_published` (boolean)
- `ordering` (integer; scoped to assignment target + position)
- `template_position_id` FK → `#__element_positions`
- `assignment_id` FK → `#__element_assignments`
- `config_xml` (XML; configuration + styling + conditions)
- `created_at`
- `created_by`
- `modified_at`
- `modified_by`

Rules:
- Only published elements are eligible for rendering
- Ordering applies within the same assignment target and position
- All configuration and conditions are stored as XML only

---

### `#__element_positions` (theme-defined positions)

Stores template position declarations provided by themes.

Minimum fields:
- `id` INT PK
- `theme_key`
- `position`
- `context` (`site` or `admin`)

Rules:
- Positions are read-only in Elements Manager
- Populated from active theme (site) or Zulu (admin)
- Only valid positions for the current context are selectable

---

### `#__element_assignments` (assignment targets)

Defines where a set of elements applies.

Minimum fields:
- `id` INT PK
- `context` (`site` or `admin`)
- `target_type` (`global`, `route`, `route_prefix`)
- `target_value` (string; empty for `global`)
- `is_enabled` (boolean)
- `created_at`
- `created_by`

Rules:
- Exactly one `global` assignment per context
- Route and route_prefix values must be valid
- Elements are always managed within an assignment target

---

## Admin routes

- `/admin/elements` — elements list (per assignment target)
- `/admin/elements/builder` — visual builder
- `/admin/elements/assignments` — manage assignment targets
- `/admin/elements/new`
- `/admin/elements/{id}/edit`
- `/admin/elements/{id}/delete`

---

## Admin UI (Zulu conventions)

### Assignment selector
All relevant screens MUST include an assignment selector (context + target).

Default:
- Site context
- Global assignment

---

### Elements list

Columns:
- Title
- Type
- Position
- Assignment
- Published
- Ordering

Row actions (icons only):
- Edit
- Delete
- Publish toggle

Toolbar (left only):
- New
- Builder
- Assignments
- Close

---

### Elements Builder

Visual layout editor for the selected assignment target.

Must show:
- All positions for the active theme/context
- Elements in ordering sequence per position

Actions (icons only):
- Edit
- Publish toggle
- Move up/down
- Delete

Drag/drop ordering is optional but must be fully functional if implemented.

---

### Create / Edit Element

Fields:
- Title
- Element Type
- Template Position
- Assignment Target
- Published toggle
- Ordering
- Configuration panel (dynamic per type)
- Styling options (class-based only)
- Conditional visibility panel

Toolbar:
- Save
- Close

No inline buttons.

---

## Styling options (all elements)

Rules:
- No inline CSS
- Class-based styling only
- Allow-listed Bootstrap classes and custom tokens
- Stored in `config_xml`

---

## Conditional Visibility (mandatory)

Every element MAY define conditional visibility rules stored in XML.

### Supported rules (strict)

**Authentication**
- Logged in (yes / no)

**Role restriction (optional)**
- User has ANY of selected roles
- User has ALL of selected roles

Rules:
- Role IDs are stored (not names)
- Roles are resolved via the CMS Roles & Permissions system
- Missing roles cause the condition to evaluate as not satisfied

### Evaluation
- Evaluated server-side only
- Safe interpreter only (no code execution)
- Failure or invalid rules MUST result in non-rendering

### Example XML

```xml
<element>
  <conditions enabled="1" op="ALL">
    <rule type="user_logged_in" value="1" />
    <rule type="role_any" value="200,300" />
  </conditions>
</element>
```

---

## Seeded core element types

Seed the following with `is_core = 1`:

- Menu
- Pages
- Posts
- Categories
- HTML (permission-gated)
- Embed (permission-gated)
- URL

---

## Element type behaviour (mandatory)

(Identical to original Phase 15 spec; no behavioural regression permitted.)

### Menu element
- Bootstrap 5 compliant navigation
- Depth limit 1–3
- Navbar / Vertical / Tabs / Pills

### Pages element
- All / Top / Featured / Latest / One

### Posts element
- All / Top / Featured / Latest / One

### Categories element
- All or One (lists immediate children)

### HTML element
- Requires `use_block_html`
- Sanitised
- No scripts

### Embed element
- Requires `use_block_embed`
- Validated source

### URL element
- Safe URL schemes only

---

## Permissions

Permission:
- `manage_elements`

Grants:
- Full CRUD
- Assignment management
- Ordering and publishing

Rules:
- Deny by default
- Role 998 always allows

---

## Ordering behaviour

- Scoped to (assignment_id + template_position_id)
- Server-side validation required

---

## Logging

Log events:
- create / edit / delete element
- publish / unpublish
- reorder
- assignment change

No configuration XML is logged.

---

## Acceptance criteria

Phase 15 is complete when:

1. Core element types are seeded
2. CRUD works per assignment target
3. Builder works reliably
4. Conditional visibility evaluates correctly
5. ACL enforced everywhere
6. No JSON or external assets are used
7. Behaviour matches or exceeds original Phase 15

---

**End of Phase 15 Specification**


# Phase 16 — Menus Manager (Admin UI and System)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Implement an Admin **Menus Manager** that provides CRUD for menus and menu 
items.

This phase provides:
- Create/Edit/Delete menus
- Create/Edit/Delete menu items within a menu
- Menu item nesting up to 3 levels
- Menu item ordering via drag/drop AND via up/down controls
- Menu item types: Custom URL, Page, Post
- Zulu admin UI conventions (lists for overview, separate create/edit pages, 
icon-only row actions)

This phase does NOT provide:
- Menu positions / placement / rendering slots (handled later by Elements 
Manager)
- Frontend theme rendering rules beyond “menu data can be retrieved”
- Category menu items
- Versioning / trash for menus

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer / vendor
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- AJAX allowed; responses must be HTML or plain text only
- Bootstrap 5 compliant, fully responsive
- No inline CSS
- All behaviour implemented (no stubs)
- Admin UI uses Zulu conventions

---

## Data model

### `#__menus` (menu definitions)
Minimum fields:
- `id` INT PK
- `title` (required)
- `key` (required, unique; lowercase a–z0–9_-; deterministic max length)
- `description` (nullable)
- `created_by`
- `created_at`
- `modified_by`
- `modified_at`

Rules:
- `key` is used for referencing menus by templates/elements later.
- `key` must be unique.

### `#__menu_items` (items in a menu)
Minimum fields:
- `id` INT PK
- `menu_id` (FK to `#__menus`)
- `type` ENUM: `url`, `page`, `post`
- `title` (required; display label)
- `parent_id` (nullable; self FK)
- `ordering` (integer)
- `is_enabled` (boolean)

Type-specific fields:
- For `url`:
  - `url` (required)
  - `target` (nullable; `_self` default; allow `_blank`)
- For `page`:
  - `page_id` (required FK to `#__pages`)
- For `post`:
  - `post_id` (required FK to `#__posts`)

Common optional fields:
- `css_class` (nullable; class names only, no inline styles)
- `rel` (nullable; allow safe values such as `nofollow`, `noopener`, 
`noreferrer`)

Rules:
- Menu item nesting is limited to **3 levels** maximum.
- Cycles are forbidden (no item can become its own ancestor).
- Ordering is scoped to siblings (same `menu_id` and same `parent_id`).

---

## Routing and link resolution

Menu items resolve to final links as follows:

- `url`:
  - uses the stored URL
- `page`:
  - links to `/page/{slug}` using the current page slug from `#__pages`
- `post`:
  - links to `/blog/{slug}` using the current post slug from `#__posts`

Rules:
- If a referenced page/post does not exist, the item must be treated as invalid 
and displayed in admin with an error indicator.
- Frontend rendering behaviour for invalid items is handled later; this phase 
only guarantees safe retrieval.

---

## Admin routes

Menus:
- `/admin/menus` (menus list)
- `/admin/menus/new`
- `/admin/menus/{id}/edit`
- `/admin/menus/{id}/delete` (confirmation page)

Menu items (scoped under a menu):
- `/admin/menus/{menu_id}/items` (items list / structure view)
- `/admin/menus/{menu_id}/items/new`
- `/admin/menus/{menu_id}/items/{id}/edit`
- `/admin/menus/{menu_id}/items/{id}/delete` (confirmation page)

---

## Admin UI (Zulu conventions)

### Menus list (`/admin/menus`)
- List view only
- Columns:
  - Title
  - Key
  - Items count
  - Modified date
- Row actions are icons only:
  - Edit
  - Delete
  - Manage Items (navigates to items screen)

Toolbar (left only):
- New
- Close

### Menu edit/new
Fields:
- Title
- Key (auto from title, editable, validated)
- Description

Toolbar (left only):
- Save
- Close

No inline form buttons.

### Menu items screen (`/admin/menus/{menu_id}/items`)
Displays items in a **tree** up to 3 levels.

Per-item row shows:
- Item title
- Type icon (URL/Page/Post)
- Status (enabled/disabled)
- Ordering controls
- Row actions (icons only): Edit, Delete

#### Ordering requirements
Both must exist:
1) **Drag/drop ordering**
- Allows reordering within same parent
- Allows changing parent (drag into another parent) as long as nesting depth <= 
3
- Uses AJAX to persist ordering
- AJAX response is HTML or plain text only (no JSON)

2) **Up/Down arrows**
- Moves item among siblings deterministically
- Uses standard POST or AJAX (HTML/plain text only)

Depth enforcement:
- UI must prevent invalid drops that exceed depth.
- Server must also enforce max depth (never trust UI).

Toolbar (left only):
- New (adds item)
- Close

---

## Menu item create/edit

Fields (common):
- Title
- Type selector (URL/Page/Post)
- Enabled toggle
- Parent selector (limited to valid parents that keep depth <= 3)
- Optional:
  - CSS class (class-only; validated)
  - Link target (`_self` default; `_blank` allowed)
  - Rel (safe allow-list)

Type-specific inputs:
- URL type:
  - URL (required)
- Page type:
  - Page picker (required)
- Post type:
  - Post picker (required)

Rules:
- Only the relevant type inputs are shown for the selected type.
- Switching type clears invalid type-specific fields.

Toolbar (left only):
- Save
- Close

No inline buttons.

---

## Permissions (core ACL)

Define permissions (DB-only):

- `manage_menus`
  - list menus
  - create/edit/delete menus
  - manage menu items (create/edit/delete/reorder)

Rules:
- Deny by default
- Unknown permissions denied
- Role 998 always allows

---

## Validation and safety

- Menu key validation:
  - lowercase
  - `a–z 0–9 _ -`
  - deterministic max length
- URL validation:
  - must be a valid URL or a site-relative path
  - must not allow `javascript:` or other scriptable schemes
- CSS class validation:
  - class tokens only, no spaces outside token separation, no special 
characters 
outside safe class charset
- Parent assignment:
  - must not create cycles
  - must not exceed 3 levels
- All admin output is escaped (no XSS)

---

## Logging (ties to Phase 4)

Log menu events (event logs, e.g. `admin.log`) at minimum:
- create/edit/delete menu
- create/edit/delete menu item
- reorder operations (menu id, item id, old parent/order, new parent/order)

Errors go to `error.log`.

No secret data is logged.

---

## Acceptance criteria

Phase 16 is complete when:

1. Menus CRUD works under `/admin/menus`
2. Menu items CRUD works per-menu under `/admin/menus/{menu_id}/items`
3. Item types supported: URL, Page, Post
4. Nesting is enforced at max 3 levels (UI + server)
5. Ordering works via drag/drop AND via up/down controls
6. All UI follows Zulu conventions (lists for overview, separate create/edit, 
icon-only actions, left toolbar only)
7. ACL gates access and actions server-side
8. Validation prevents unsafe URLs and invalid nesting/cycles
9. All required events are logged
10. No JSON and no external assets are used


# Phase 17 — Documents Manager (User and Admin) and Admin UI

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Implement a core **Documents Manager** providing controlled upload, storage, 
management, and insertion of non-image files (documents) into content.

This phase provides:
- Admin Documents Manager at `/admin/documents`
- DB-backed document registry
- Secure upload pipeline with staging, validation, and sanitisation
- Download and permanent delete actions
- Ownership-based access using `/storage/media/{user_id}/files`
- Permission system integration including `_own` permissions
- **Direct integration with the Freewrite editor document inserter**

This phase does NOT provide:
- Versioning or trash/restore (delete is permanent)
- Folder/subfolder management (flat storage only)
- External storage backends
- JSON responses

---

## Global rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer / vendor
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- PHP and XML only
- Bootstrap 5 compliant, fully responsive
- No inline CSS
- Uploads MUST be staged and validated before final placement
- All behaviour implemented (no stubs)

---

## Storage model (authoritative)

User document storage location:
- `/storage/media/users/{user_id}/files/`

Rules:
- Storage is **flat** (no subfolders).
- Admin may manage documents across any user bucket.
- Users may only access their own bucket.
- All writes are performed by core logic only.

---

## Database table

### `#__documents`

Minimum fields:
- `id` INT PK
- `owner_user_id` (required)
- `original_name` (required)
- `stored_name` (required)
- `display_name` (required; renameable)
- `mime_type` (required)
- `file_ext` (required; lowercase)
- `file_size` (required; bytes)
- `storage_path` (required; relative to `/storage`)
- `uploaded_at`
- `uploaded_by`
- `modified_at` (nullable)
- `modified_by` (nullable)

Rules:
- DB row and file MUST always stay in sync.
- If the file does not exist on disk, the row is invalid and must not render.

---

## Permissions (core ACL)

Permissions MUST be defined and seeded:

Global:
- `manage_documents`
  - full access across all users

Own-scoped:
- `manage_documents_own`
- `upload_documents_own`
- `download_documents_own`
- `delete_documents_own`
- `rename_documents_own`

Rules:
- Deny by default
- Unknown permissions denied
- Role 998 always allows
- `_own` permissions MUST enforce `owner_user_id = current_user_id` server-side

---

## Admin UI

### Route
- `/admin/documents`

### Documents list

List view only, Zulu conventions.

Columns:
- Display name
- Owner
- Filename
- Size
- Type
- Uploaded date

Row actions (icons only):
- Edit
- Download
- Trash (permanent delete)

Toolbar (left only):
- New / Upload
- Close

---

## Upload workflow

Upload process is mandatory and deterministic:

1. Upload file to `/storage/tmp/`
2. Validate extension, MIME type, size
3. Sanitize filename
4. Reject if executable or scriptable
5. Move file to `/storage/media/users/{owner_user_id}/files/`
6. Insert row into `#__documents`

Failure at any step aborts the process safely and logs to `error.log`.

---

## File type enforcement

### Deny list (mandatory)
Uploads MUST be rejected if extension matches (case-insensitive):
- `php`, `phtml`, `phar`
- `js`, `mjs`
- `exe`, `dll`, `bat`, `cmd`
- `sh`, `cgi`, `pl`, `py`, `rb`
- Any multi-extension variant containing a denied extension

### Allow list (mandatory)
Only the following document-oriented extensions are permitted:
- `pdf`
- `txt`
- `rtf`
- `doc`, `docx`
- `xls`, `xlsx`
- `ppt`, `pptx`
- `csv`
- `od*`
- `zip`


Rules:
- MIME type MUST be detected server-side and must match extension.
- Mismatches cause rejection.

---

## Filename rules

- `original_name` is never modified.
- `stored_name` is sanitized:
  - unsafe characters removed/replaced
  - extension preserved
- Collisions generate a safe unique name.
- Rename operations:
  - may change `display_name`
  - may change `stored_name` (extension preserved, revalidated)

---

## Edit document

Route:
- `/admin/documents/{id}/edit`

Fields:
- Display name
- Stored filename (rename)
- Owner user (admin only)

Toolbar:
- Save
- Close

---

## Download action

- Download by document ID only
- Enforce permission scope
- Set correct headers
- No direct path access allowed

---

## Delete action (permanent)

- Confirmation page required
- Deletes:
  - file from disk
  - DB row from `#__documents`
- Failure to delete file aborts DB removal

---

## Freewrite editor integration (MANDATORY)

### Document inserter

Freewrite MUST provide:
- A **Document inserter icon and block**
- Available in both:
  - toolbar icon
  - block inserter

Behaviour:
- Opens a **modal document picker**
- Lists documents from `#__documents`
- Visibility rules:
  - Users with `_own` permissions see only their documents
  - Admin/super see all documents
- Selection inserts a link into content:
  - `<a href="{resolved_document_url}">display_name</a>`

Rules:
- Inserter does NOT upload files
- Uploads MUST go through Documents Manager
- Inserter MUST NOT expose filesystem paths
- Inserter MUST respect permissions

---

## Logging (ties to Phase 10)

Log events:
- upload
- rename
- download
- delete
- Freewrite insertion (document id + content id)

Errors go to `error.log`.

No file contents or paths are logged verbatim.

---

## Acceptance criteria

Phase 17 is complete when:

1. Documents Manager exists at `/admin/documents`
2. Upload pipeline stages, validates, sanitises, and stores documents safely
3. DB-backed document registry is authoritative
4. Storage is flat under `/storage/media/users/{user_id}/files`
5. Executable/script files are rejected
6. Admin and `_own` permissions are enforced correctly
7. Documents can be renamed, downloaded, and permanently deleted
8. Freewrite document inserter works and respects permissions
9. All actions are logged correctly
10. No JSON or external assets are used


# Phase 18 — Themes Manager, Theme Customizer & Frontend Rendering Contract

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

This phase defines the Themes system, including:
- Theme installation and management
- Frontend and admin theme resolution
- Theme customization via a WordPress-style customizer (without WordPress code)
- Database-stored style overrides
- Deterministic application of overrides at render time
- Rendering contract: how content, menus, and elements become HTML via theme 
templates and positions

This phase does NOT provide:
- Any external asset loading
- Any JSON
- Any inline CSS
- Any WordPress code usage

---

## Global rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer
- No vendor directory
- No external libraries or CDNs
- No external asset loading of any kind
- No JSON anywhere
- PHP and XML and INI only
- Fully Bootstrap 5 compliant
- Fully responsive
- No inline CSS; CSS files only
- Core system is untouchable at runtime
- Each phase must be complete for its scope

---

## Theme location (authoritative)

All themes live exclusively under:

/themes/{theme_key}/

Rules:
- {theme_key} is lowercase and unique
- Theme templates and all theme assets and theme.xml live inside this directory
- Admin theme is always zulu
- Frontend theme is the active site theme, with fallback to alpha

Core-shipped themes:
- /themes/alpha/ (site)
- /themes/zulu/ (admin)

Core themes:
- MUST be registered in DB as is_core = 1
- MAY be customized
- MUST NOT be deleted/uninstalled

---

## Theme registry table

### #__themes

Minimum fields:
- id
- theme_key
- name
- context (site or admin)
- is_active
- is_core
- version
- author
- description
- installed_at
- installed_by

Rules:
- Only one site theme may be active at a time
- Zulu is always active for admin
- Activation/deactivation is managed from /admin/themes
- Core themes cannot be removed

---

## Themes Manager (Admin UI)

### Route
- /admin/themes

### List view
Shows installed themes with:
- name
- theme_key
- context (site/admin)
- active state

Row actions (icons only):
- Activate / Deactivate (where applicable)
- Palette icon: Theme Customizer


Toolbar buttons (left only):
- Close
- Upload (manual install)


Rules:
- No New and no Save on the list page (installation handled by installer)
- Upload MUST follow the same validation and safety pipeline as the 
extension/theme installer
- Super admin only may manage themes and access the customizer

---

## Theme upload behaviour (manual install)

The Themes Manager Upload action MUST:
- Accept a theme package upload
- Process it via the same installer/uninstaller routine used for theme 
installation
- Verify package signature rules (if defined in installer phase)
- Refuse unsafe writes and paths
- Install only into /themes/{theme_key}/
- Create or update the theme record in #__themes

Rules:
- Theme installation MUST NOT modify core files
- Themes MUST NOT create or alter core DB tables
- Themes MUST NOT create their own DB tables in this phase
- Themes are presentation only

---

## Theme definition file (theme.xml)

Each theme MUST provide:

/themes/{theme_key}/theme.xml

theme.xml declares:
- Theme metadata (name, description, author, version)
- Style regions (groups shown in the customizer)
- Style tokens (CSS variable definitions)
- Control types for the customizer UI

Rules:
- XML only
- Read-only at runtime
- Invalid or missing theme.xml makes the theme not customizable; it may still 
render, but the customizer must refuse to open for that theme and log the 
failure

---

## Theme Customizer (Super-only)

### Access
- Opened via Palette icon from /admin/themes
- Super admin only

### Look and behaviour (mandatory)
- Must look and behave like the WordPress Customizer pattern:
  - Left control panel with grouped sections
  - Right live preview iframe
  - Top toolbar
- Must not use any WordPress code

### Controls (mandatory)
- Controls are generated from theme.xml
- Must support:
  - hex colour input
  - rgb colour input
  - rgba colour input
- Tokens must be grouped by region
- Each token must show label and help text

### Autosave (mandatory)
- Toolbar toggle: Autosave ON/OFF
- When Autosave is ON:
  - every change persists immediately to DB
  - responses are plain text or HTML only (no JSON)
- When Autosave is OFF:
  - changes are staged in the UI until Save is invoked
  - Save persists all staged changes to DB in a single operation

---

## Theme style overrides (DB-based)

### #__theme_style_overrides

A core DB table stores all overrides.

Minimum fields:
- id
- theme_id
- context (site/admin)
- token (matches theme.xml token)
- value
- updated_at
- updated_by

Rules:
- Overrides are allowed only for tokens declared in theme.xml
- Unknown tokens MUST be rejected (and logged)
- Overrides are stored only in DB
- Overrides must be validated by type:
  - color accepts hex/rgb/rgba only
  - range validates min/max/step
  - select validates allowed options
  - text validates allowed pattern if provided

---

## Applying overrides (no inline CSS)

Overrides MUST be applied via a dynamic CSS endpoint.

### Endpoint (authoritative)

/themes/{theme_key}/overrides.css?ctx=site
/themes/{theme_key}/overrides.css?ctx=admin

### Output rules
- Response Content-Type must be text/css
- Output must be CSS variables only
- Output structure:

:root {
  --token-name: value;
  ...
}

Rules:
- token-name is the theme.xml token name without leading --
- values must be escaped and safe
- only tokens defined in theme.xml may be output
- no inline style blocks are permitted in templates

### Load order (mandatory)
Theme templates MUST include CSS in this order:
1) Base theme CSS files
2) Overrides CSS endpoint (last)

---

## Live preview mechanics

- The customizer preview uses an iframe rendering the normal site/admin output
- The iframe must include the overrides stylesheet link
- Cache busting must be supported by adding an additional query parameter (e.g. 
rev) derived from DB updated_at or an incrementing revision value
- Preview should update quickly; full page reload is permitted but not required 
for every change

---

## Rendering contract (frontend & admin)

### Template responsibility (mandatory)
Theme templates are responsible for:
- HTML structure
- Declaring template positions
- Rendering positions by calling a core position renderer

Themes MUST NOT:
- Query DB directly
- Bypass MVC controllers
- Hardcode element output instead of rendering a position

### Elements rendering (mandatory)
- Elements are injected only via template positions
- Themes call a core renderer for a named position; the renderer outputs 
elements assigned to that position in ordering order
- The element renderer must obey element published state and permissions rules

### Menus rendering (mandatory)
- Menus MUST be rendered via Menu elements
- Themes MUST NOT render menus directly from menu tables
- Bootstrap menu layout is controlled by:
  - Menu element configuration
  - Theme CSS and overrides

---

## Asset rules

- Theme assets must live inside /themes/{theme_key}/
- No external assets
- No CDN
- No inline CSS
- JS must be local and only used where needed for theme behaviour and the 
customizer UI

---

## Security & validation

- Customizer input MUST be validated strictly per token type
- System MUST prevent arbitrary CSS injection
- Overrides endpoint MUST refuse invalid ctx values
- Sysadmin-level actions remain Super-only

---

## Logging

Log events:
- Theme activation/deactivation
- Customizer save/autosave
- Overrides endpoint errors and invalid input

Errors go to error.log.

---

## Acceptance criteria

Phase 23 is complete when:

1. Themes live only in /themes/{theme_key}/
2. Alpha and Zulu exist as core themes under /themes and are registered as 
is_core=1
3. /admin/themes lists themes and supports activation and the palette customizer
4. Customizer behaves like WordPress customizer pattern without WordPress code
5. theme.xml defines regions and tokens for customization
6. Overrides are stored only in DB
7. Overrides are applied via /theme/{theme_key}/overrides.css?ctx=site|admin
8. Templates load base CSS then overrides CSS (no inline CSS)
9. Position rendering injects elements deterministically
10. Menus are rendered via Menu elements
11. All actions are logged safely and no JSON is used


# Phase 19 — Extensions API (MVC Components + DB-Registered XML API Endpoints)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Define and implement the **Extensions system contract** for FreeflowCMS, 
including:
- One extension type only: **MVC Component**
- Fixed extension location: `/extensions/{ext_key}/`
- Extension activation toggle (`is_active`) in the core extensions registry
- Extension-declared **XML-only API endpoints** registered at install time from 
`manifest.xml`
- Session-authenticated API by default, with optional public endpoints
- Admin menu entry registration by extension, removed on uninstall
- Hard enforcement of core protection rules:
  - extensions may READ core tables
  - extensions may NOT write/alter/drop core tables
  - extensions may only write to their own tables
  - only explicit exceptions allowed: `#__extensions` registry + `#__api_routes`

This phase does NOT provide:
- JSON APIs
- Token/OAuth/API-key auth (session-only in this phase)
- External endpoint discovery / auto-mapping without registration
- Extension “hook/event bus” runtime system (additive MVC endpoints only)
- Any ability for extensions to modify core files at runtime

---

## Global rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer / vendor
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- PHP and XML only
- AJAX allowed; responses must be plain text or HTML only (admin UI). API 
responses in this phase are XML only.
- Single DB connection in core (Connection.php); extensions MUST use it
- Core tables are protected by policy and enforcement

---

## Extension identity and location (locked)

- An extension is always a **component** (MVC).
- Extensions live only at:
  - `/extensions/{ext_key}/`
- `{ext_key}` is a unique key (lowercase; `a–z 0–9 _ -`) and is the namespace 
for:
  - folder name
  - DB tables
  - API routes

Extensions may include subdirectories mirroring core MVC, e.g.:
- `/extensions/{ext_key}/Controllers/`
- `/extensions/{ext_key}/Models/`
- `/extensions/{ext_key}/Views/`
- `/extensions/{ext_key}/Templates/` (if needed)
- `/extensions/{ext_key}/Assets/` (CSS/JS/images; local only)

Rules:
- All extension logic stays inside the extension directory.
- All extension assets must stay inside the extension directory.
- Extensions MUST NOT write anywhere on disk outside:
  - `/storage/media/{ext_key}/` (allowed by global rule)
  - `/storage/tmp/` (temporary processing only, if needed)

---

## Core registry changes: `#__extensions` requires `is_active`

### `#__extensions` (core registry)
The core extensions registry MUST include an activation flag:
- `is_active` TINYINT(1) NOT NULL DEFAULT 0

Rules:
- Only admins with the appropriate permissions (and Super where locked) can 
toggle `is_active`.
- If `is_active = 0`, the extension:
  - MUST NOT serve routes
  - MUST NOT serve API endpoints
  - MUST NOT appear in admin navigation (even if it has a menu entry declared)

(Exact permissions for the Extensions Manager already exist in prior phases; 
this phase does not redefine them.)

---

## Core table allowance (locked)

Extensions may READ core tables.

Extensions may NOT write/alter/drop core tables, except:
- `#__extensions` (registry updates performed by core Extensions Manager only)
- `#__api_routes` (insert/update/delete performed by core installer/uninstaller 
only)

Extensions may write only to their own tables:
- Table name format (mandatory):
  - `#_{ext_key}_{table_name}`
- Reserved IDs and prefixes are enforced as per core DB prefix replacement 
rules.

---

## Enforcement (mandatory, server-side)

The system MUST enforce the “no core writes” rule at runtime.

Minimum enforcement requirement:
- Any SQL execution initiated in an **extension context** MUST be classified as:
  - READ (allowed on core tables)
  - WRITE (INSERT/UPDATE/DELETE/ALTER/DROP/CREATE/RENAME/TRUNCATE)

Rules:
- WRITE operations MUST be rejected if they target:
  - any core table not explicitly allowed
- The only allowed non-extension tables for writes are:
  - none (extensions themselves cannot write core tables)
- Allowed extension writes are limited to tables matching:
  - `#_{ext_key}_%`

Important:
- This enforcement is policy + code, not database foreign keys.

---

## API system overview (locked)

### Base paths
- Core API base path:
  - `/api`
- Extension API base path:
  - `/api/ext/{ext_key}/...`

Rules:
- API responses MUST be XML only.
- No JSON is allowed anywhere.
- API endpoints MUST be registered in the database from `manifest.xml` at 
install time.
- The router MUST NOT “guess” endpoints by scanning extension folders.

---

## API routes table (the only additional core table with extension data)

### `#__api_routes`

This table is the **single** core table permitted to contain extension endpoint 
registrations.

Minimum fields:
- `id` INT PK
- `ext_key` (required; matches `/extensions/{ext_key}`)
- `route_path` (required; path AFTER `/api/ext/{ext_key}/`, e.g. 
`anchorage/list`)
- `http_method` (required; `GET`/`POST` only in this phase)
- `controller` (required; extension controller class name or controller key)
- `action` (required; method/action name)
- `is_public` TINYINT(1) NOT NULL DEFAULT 0
- `required_permission` (nullable; permission string required when not public)
- `is_enabled` TINYINT(1) NOT NULL DEFAULT 1
- `created_at`
- `created_by` (user id)

Constraints:
- Unique key on (`ext_key`, `route_path`, `http_method`)

Rules:
- Only core install/uninstall/update routines may write this table.
- Extensions MUST NOT write to this table at runtime.

---

## API authentication model (locked)

### Default: session-only
Non-public endpoints require an authenticated session:
- Site-context session for site API usage
- Admin-context session for admin API usage

Rules:
- Admin-context API requests MUST require an admin session (ff_admin cookie) and 
correct path/context rules.
- Site-context API requests MUST require a site session (ff_site cookie) unless 
the route is public.
- API endpoints MUST enforce ACL when `required_permission` is set.

### Optional: public endpoints
- If `is_public = 1`, no session is required.
- Public endpoints MUST still be:
  - explicitly registered
  - explicitly enabled (`is_enabled = 1`)
  - safe by design (no sensitive output)

Rate limiting:
- Public endpoints SHOULD be rate-limited.
- If rate limiting is implemented in this phase, it MUST:
  - not use JSON
  - not require additional core tables beyond existing ones
  - be enforceable server-side (not UI-only)

---

## API response format (XML-only)

All API endpoints MUST return XML with a deterministic wrapper.

Minimum response structure:
- Success:
  - root element `response` with attribute `status="ok"`
- Error:
  - root element `response` with attribute `status="error"`
  - contains a safe `<message>` element

Rules:
- Output must be safely escaped.
- Errors MUST NOT include secrets (tokens, passwords, raw session IDs).
- Content-Type MUST be `application/xml` for API responses.

---

## API routing and dispatch (mandatory behaviour)

For any request under `/api/ext/{ext_key}/...`:

1. Match static routes first (router rule).
2. Validate `{ext_key}` exists in `#__extensions`.
3. Require `is_active = 1` for the extension, otherwise 404.
4. Determine `route_path` and `http_method`.
5. Find a matching row in `#__api_routes` where:
   - ext_key matches
   - route_path matches
   - http_method matches
   - is_enabled = 1
6. Enforce auth:
   - if `is_public = 0`, require correct session context
7. Enforce ACL:
   - if `required_permission` is non-empty, require that permission
8. Dispatch to the extension controller/action inside 
`/extensions/{ext_key}/...`
9. Return XML response only.

Unknown route behaviour:
- If no match, return 404 (XML error wrapper is allowed).

---

## Manifest-driven registration (install-time only)

### `manifest.xml` (mandatory)
Each extension package MUST contain `manifest.xml` at the root of the extension 
zip.

The manifest MUST declare at minimum:
- ext_key
- name
- version
- admin menu entry (optional)
- API routes (optional)
- install SQL (optional; extension tables only)
- uninstall SQL (optional; extension tables only)

### API route declarations
- Each declared API route in manifest MUST be inserted into `#__api_routes` at 
install time.
- On uninstall:
  - all routes for that ext_key MUST be removed from `#__api_routes`.

Rules:
- Manifest declarations MUST be validated:
  - route_path format
  - controller/action format
  - method allowed (GET/POST)
  - required_permission format
- Unsafe declarations MUST abort install unless a Super-admin per-package 
override is applied (override policy defined by installer rules).

---

## Admin menu entry registration (install/uninstall)

Extensions may declare an **admin menu entry** in `manifest.xml`.

Rules:
- The menu entry MUST be removed when the extension is uninstalled.
- To avoid creating additional core tables, admin menu entry data MUST be stored 
in the existing `#__extensions` registry row as XML (no JSON), for example:
  - a column such as `admin_menu_xml` (XML only)
- When `is_active = 0`, the menu entry MUST NOT be shown.

The admin menu entry MUST resolve to the extension’s admin controller route 
under `/admin/...` as defined by this phase’s routing rules 
(implementation-specific mapping, but deterministic).

---

## Extension admin routes (MVC)

Extensions may expose admin routes.

Rules:
- Admin routes MUST be gated by:
  - admin session context
  - ACL checks (at least one permission per admin section)
- Admin pages must follow Zulu conventions:
  - list views for overview only
  - separate create/edit/delete pages
  - icon-only row actions
  - toolbar buttons left-only (New/Save/Close pattern)

---

## Installer/uninstaller constraints (restated for extensions)

- Installer/uninstaller may create extension DB tables, but ONLY in the 
extension namespace:
  - `#_{ext_key}_...`
- Installer/uninstaller MUST refuse any attempt to:
  - write/alter/drop core tables
  - write files outside `/extensions/{ext_key}/` and permitted storage buckets
- Uninstall MUST:
  - remove API routes (`#__api_routes` rows for ext_key)
  - remove admin menu entry data (from `#__extensions`)
  - remove extension registry row (unless disabled-but-retained mode exists; not 
introduced here)
  - remove extension tables if uninstall SQL is defined (extension tables only)

---

## Logging (ties to Phase 10)

The following MUST be logged (event logs, e.g. `updates.log` or `admin.log` as 
appropriate):
- Extension install/uninstall/enable/disable actions
- API route registration/removal (ext_key, route_path, method)
- API request failures (safe, no secrets; include ext_key + route_path + status)
- Security enforcement blocks (e.g. attempted core-table write from extension 
context)

Errors go to `error.log`.

Logs MUST NOT contain DB passwords, full tokens, or raw session IDs.

## Extension activation state (`is_active`) — clarified behaviour

The `is_active` flag in `#__extensions` controls **runtime execution**, not 
administrative visibility.

Rules:

- When `is_active = 0`:
  - Site routes provided by the extension MUST NOT execute.
  - API endpoints provided by the extension MUST NOT execute.
  - Background or automatic execution of extension logic MUST NOT occur.

- Admin navigation:
  - An extension MAY still appear in the admin navigation when `is_active = 0`.
  - The menu entry MUST be visually marked as **Inactive**.
  - Selecting the menu entry MUST NOT execute site/runtime logic.

- Admin access while inactive:
  - Accessing an inactive extension from admin MUST route to a core-controlled 
wrapper page.
  - The wrapper page MUST:
    - indicate that the extension is inactive
    - provide an Activate action (if the user has permission)
  - Direct execution of extension admin controllers while inactive is NOT 
permitted unless explicitly allowed.

Optional allowance (if implemented):

- An extension MAY declare specific admin routes as **inactive-safe** at install 
time.
- Inactive-safe routes:
  - MUST be admin-context only
  - MUST enforce ACL
  - MAY read core tables
  - MAY write only extension-owned tables
  - MUST NOT execute site routing, API dispatch, or background logic
  - MUST obey all network and filesystem restrictions

If inactive-safe routing is not implemented, admin settings pages MUST NOT be 
accessible while the extension is inactive.


## External communication restrictions (mandatory)

Extensions MUST operate in a fully self-contained manner.

Hard prohibitions:

- Extensions MUST NOT send data to external systems.
- Extensions MUST NOT receive data from external systems.
- Extensions MUST NOT initiate outbound network connections of any kind, 
including but not limited to:
  - HTTP / HTTPS requests
  - Webhooks
  - cURL
  - fsockopen
  - stream_socket_client
  - FTP / SFTP
  - DNS lookups
  - Any socket-based communication

- Extensions MUST NOT embed or load external resources.
- Extensions MUST NOT “call home” for licensing, updates, telemetry, or 
analytics.

Inbound data restrictions:

- Extensions MUST NOT expose inbound listeners or endpoints intended for 
third-party systems.
- API endpoints are only for first-party system use and are subject to the core 
API rules.

Explicit exception — email:

- Extensions MAY send email **only** via the core mail system.
- Extensions MUST use the globally configured mailer.
- Extensions MUST NOT implement or configure their own mail transport.
- Email sending is permitted only as part of CMS functionality (e.g. 
notifications to users).

Enforcement:

- Any attempt by an extension to perform prohibited network operations MUST be 
blocked.
- Such attempts MUST be logged as a security violation.

## Extension language loading (mandatory)

FreeflowCMS is multilingual. Extensions MUST support language keys via INI 
language files using the same conventions as core.

### Language file format

- Language files MUST be plain INI files.
- Key/value format is mandatory:
  - `KEY=value`
- No sections.
- No JSON.
- No executable content.

Comments MAY be supported using standard INI comment syntax (`;`), consistent 
with core behaviour.

---

### Extension language file location

Each extension MAY provide language files under:

- `/extensions/{ext_key}/Languages/{lang_tag}.ini`

Examples:
- `/extensions/safehavens/Languages/en-GB.ini`
- `/extensions/safehavens/Languages/fr-FR.ini`

---

### Language loading timing

When core loads the active language file for the current context, it MUST also 
load language files for active extensions.

Rules:
- Only extensions with `is_active = 1` are considered.
- Load order MUST be deterministic:
  1) Core language (current context)
  2) Active extensions’ language files, sorted by `ext_key` ascending

Fallback order for each extension:
1) `{lang_tag}.ini`
2) `en-GB.ini`
3) If neither exists, the extension contributes no language keys

---

### Context separation

Language loading MUST respect context:

- Site context loads site language, then extension language files.
- Admin context loads admin language, then extension language files.

(Extensions may provide a single shared language file per language in this 
phase; no separate site/admin files are required.)

---

### Key namespace and collision rules

To avoid collisions, extension language keys MUST be namespaced.

Rules:
- All extension language keys MUST begin with:
  - the uppercase form of `ext_key`
  - followed by a dot
- Example:
  - ext_key: `safehavens`
  - namespace: `SAFEHAVENS.`
  - valid key: `SAFEHAVENS.TITLE=Safehavens`

Enforcement:
- Any key that does NOT start with the required namespace MUST be ignored and 
logged as a warning.
- If a key collision occurs (same key defined multiple times):
  - the first-loaded value wins
  - subsequent definitions are ignored and logged as warnings

---

## Extension language loading (mandatory)

FreeflowCMS is multilingual. Extensions MUST support language keys via INI 
language files using the same conventions as core.

### Language file format

- Language files MUST be plain INI files.
- Key/value format is mandatory:
  - `KEY=value`
- No sections.
- No JSON.
- No executable content.

Comments MAY be supported using standard INI comment syntax (`;`), consistent 
with core behaviour.

---

### Extension language file location

Each extension MAY provide language files under:

- `/extensions/{ext_key}/Languages/{lang_tag}.ini`

Examples:
- `/extensions/safehavens/Languages/en-GB.ini`
- `/extensions/safehavens/Languages/fr-FR.ini`

---

### Language loading timing

When core loads the active language file for the current context, it MUST also 
load language files for active extensions.

Rules:
- Only extensions with `is_active = 1` are considered.
- Load order MUST be deterministic:
  1) Core language (current context)
  2) Active extensions’ language files, sorted by `ext_key` ascending

Fallback order for each extension:
1) `{lang_tag}.ini`
2) `en-GB.ini`
3) If neither exists, the extension contributes no language keys

---

### Context separation

Language loading MUST respect context:

- Site context loads site language, then extension language files.
- Admin context loads admin language, then extension language files.

(Extensions may provide a single shared language file per language in this 
phase; no separate site/admin files are required.)

---

### Key namespace and collision rules

To avoid collisions, extension language keys MUST be namespaced.

Rules:
- All extension language keys MUST begin with:
  - the uppercase form of `ext_key`
  - followed by a dot
- Example:
  - ext_key: `safehavens`
  - namespace: `SAFEHAVENS.`
  - valid key: `SAFEHAVENS.TITLE=Safehavens`

Enforcement:
- Any key that does NOT start with the required namespace MUST be ignored and 
logged as a warning.
- If a key collision occurs (same key defined multiple times):
  - the first-loaded value wins
  - subsequent definitions are ignored and logged as warnings

---

### Runtime language lookup and fallback

When retrieving a language string:

1) If the requested key exists in the loaded language set, return its value.
2) If the key does not exist:
   - return the key itself as the fallback value.

Rules:
- Missing keys MUST NOT cause errors or exceptions.
- Missing keys MAY be logged (debug or warning level), but logging MUST be 
rate-limited to avoid noise.

This behaviour MUST be consistent for:
- Core language lookups
- Extension language lookups

---

### Runtime usage rules

- Extensions MUST retrieve language strings via the core Language service only.
- Extensions MUST NOT implement their own language loaders.
- Extensions MUST NOT bypass core fallback behaviour.

---

### Safety

- INI parsing MUST be performed in non-evaluating mode (no constant 
interpolation or code execution).
- Language loading failures MUST NOT break page rendering.
- All failures MUST be logged to `error.log` without exposing file paths or 
internal state.

---

## Acceptance criteria

Phase 19 is complete when:

1. Extensions are treated as MVC components only under `/extensions/{ext_key}/`
2. `#__extensions` includes `is_active` and it is enforced for routing/API/menu 
visibility
3. Extensions can only write their own tables `#_{ext_key}_...` and cannot write 
core tables
4. Core has `/api`, and extension endpoints mount at `/api/ext/{ext_key}/...`
5. API endpoints are XML-only, session-auth by default, optional public 
endpoints supported
6. Endpoints are registered from `manifest.xml` into exactly one core table: 
`#__api_routes`
7. Admin menu entry can be declared by extensions, stored as XML in 
`#__extensions`, and removed on uninstall
8. All install/uninstall and API operations are logged safely
9. No JSON and no external assets are used


# Phase 20 — Extensions & Themes Installer / Uninstaller (No Updates)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Phase 20 defines the **installer / uninstaller** for **extensions and themes**.

This phase provides:
- Install from ZIP (upload)
- Uninstall (with confirmation)
- Enable/Disable (where applicable)
- Mandatory package authenticity verification using the FreeflowCMS **public key 
baked into core**
- Mandatory safety checks preventing any write/alter of protected core paths and 
protected core tables
- Deterministic staging, extraction, and deployment rules
- Separate registries for extensions and themes to avoid cross-corruption impact

This phase does **not** provide:
- Any update execution (handled by the Updates system phase)
- Any automatic repository checking
- Any external libraries or CDNs
- Any JSON output
- Any ability to bypass cryptographic verification
- Any ability for extensions/themes to write/alter/drop core tables

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or CDNs
- No external asset loading of any kind
- No JSON anywhere
- AJAX allowed; responses must be HTML or plain text only
- Bootstrap 5 compliant, fully responsive
- No inline CSS
- Core DB connection only
- Extensions/themes may READ core tables but may NOT write/alter/drop core 
tables
- Extensions/themes may write only to:
  - their own tables
  - and their own media folders under `/storage/media/extensions/{ext_key}` or 
`/storage/media/themes{theme_key}`
- Super (role 998) is allow-all
- Safety checks must be implemented as real behaviour (no stubs)

---

## Trust model (mandatory)

### Cryptographic verification (authoritative)

All install packages handled by this manager MUST be verified using the
**trusted signing public key defined by the Global Core Trust & Integrity 
rules**.

Rules:
- The trusted signing public key is loaded exclusively from the core
  read-only location defined in Global rules.
- No private key is ever present on the installed site.
- Authenticity is proven by verifying a signature created with the
  corresponding private key, using PHP built-in OpenSSL functions only.
- Signature verification failure MUST abort install staging immediately,
  before any deployment or database changes.

Non-overridable rule:
- Cryptographic verification failure is never overridable, even by Super.


---

## Separate registries (authoritative)

Themes are stored in a dedicated themes table so corruption in one registry does 
not affect the other.

- Extensions registry: `#_extensions`
- Themes registry: `#_themes`

The installer routine is conceptually the same (stage → manifest → verify → 
safety checks → deploy), but registry writes are isolated.

---

## Database tables

### `#_extensions` (extensions registry)
Minimum fields:

- `id`
- `type` (e.g. `component`, `plugin`, `module`)
- `name`
- `extension_key` (unique; e.g. `com_safehavens`, `plg_myplugin`)
- `version`
- `is_enabled`
- `is_core`
- `author`
- `description`
- `installed_at`, `installed_by`
- `updated_at`, `updated_by`

### `#_themes` (themes registry)
Minimum fields:

- `id`
- `name`
- `theme_key` (unique; e.g. `alpha`, `zulu_child`)
- `version`
- `is_enabled` (if multiple themes can exist; otherwise this field may represent 
“published/default”)
- `is_core`
- `author`
- `description`
- `installed_at`, `installed_by`
- `updated_at`, `updated_by`

Key validation (both tables):
- lowercase
- `a–z 0–9 _ -` only
- deterministic max length enforced (e.g. 80)

---

## Permissions (core ACL)

Installer permissions (DB-only):

- `manage_extensions` — list/view extensions
- `install_extensions` — install/uninstall extensions (upload ZIP / uninstall)
- `manage_themes` — list/view themes
- `install_themes` — install/uninstall themes (upload ZIP / uninstall)

Rules:
- Super (998) always allows.
- This phase does not grant any update permissions because updates are out of 
scope.

---

## Routes and UI (Zulu conventions)

### Location and structure
A single admin area entry point provides installer access for both types, using 
tabs:

- Route: `/admin/extensions`
- Tabs:
  - **Extensions**
  - **Themes**

Tabs control which registry is displayed and which install/uninstall flows 
apply. The installer routine remains the same.

### UI rules
- Overview pages are lists only
- Separate pages for:
  - Install (upload ZIP)
  - Uninstall (confirmation)
- Row actions are icons only
- Toolbar buttons (left only): New / Save / Close (per Zulu conventions)
  - “New” opens the Install Upload page for the active tab (Extensions or 
Themes)

#### Extensions list (minimum columns)
- Name
- Type
- Key
- Version
- Enabled
- Core (indicator)

Row actions (icon-only; visibility depends on permissions and `is_core`):
- Enable/Disable (not allowed if `is_core = 1`)
- Configure (optional hook if extension provides settings route)
- Uninstall (not allowed if `is_core = 1`)

#### Themes list (minimum columns)
- Name
- Key
- Version
- Enabled/Default indicator (as applicable)
- Core (indicator)

Row actions (icon-only; visibility depends on permissions and `is_core`):
- Enable/Disable or Set Default (theme activation semantics must be consistent 
with the Theme system phases)
- Uninstall (not allowed if `is_core = 1`)

---

## Install from ZIP (mandatory)

### Staging
All installs MUST stage extraction under:

- `/storage/tmp/installer/{id}/`

Rules:
- Staging directory is unique per operation
- No collisions
- Staging is never under web-root

### ZIP package requirements
- Package is a ZIP
- Root of ZIP contains:
  - `manifest.xml` (required)

### Install steps (deterministic)
1. Validate admin session + CSRF + correct install permission 
(`install_extensions` or `install_themes`)
2. Upload ZIP to staging
3. Verify ZIP is readable and safe to extract
4. Extract ZIP into staging directory
5. Load `manifest.xml` from staging root
6. Verify package authenticity using baked-in public key (non-overridable)
7. Run safety checks (see below)
8. Deploy files into final destinations per manifest (only permitted 
destinations)
9. Run optional SQL install script (extension/theme-owned tables only)
10. Insert/update the appropriate registry table (`#_extensions` or `#_themes`)
11. Parse `<adminMenu>` nodes from `manifest.xml`
12. Upsert admin menu entries into `#__admin_menu`:
    - `owner_type = 'ext'`
    - `owner_key = {extension_key}`
    - resolve parent relationships (two-pass insert if required)
13. Log success or failure
14. Clean up staging directory (best-effort)


---

## Uninstall (mandatory)

Uninstall must:
- require confirmation page
- remove extension/theme files only from their own directories
- remove only extension/theme-owned tables (never core tables)
- remove only extension/theme media under:
  - `/storage/media/{ext_key}` or `/storage/media/{theme_key}`
- update registry record deterministically:
  - either delete the registry row OR mark uninstalled (choose one rule and 
implement consistently)During uninstall:
- Delete all rows from `#__admin_menu` where:
  - `owner_type = 'ext'`
  - `owner_key = {extension_key}`
- No extension code ever writes to `#__admin_menu`.

Rules:
- Core items (`is_core = 1`) cannot be uninstalled or disabled.
- Uninstall does not remove any user content that references the 
extension/theme; it removes code/assets only.

---

## Safety checks (mandatory)

Safety checks apply to install only in this phase.

### 1) Destination path enforcement
The manifest may declare file operations only into permitted locations.

Permitted targets:
- Extensions: `/extensions/{extension_key}/...`
- Themes: `/themes/{theme_key}/...`
- Storage media:
  - `/storage/media/extensions/{extension_key}/...`
  - `/storage/media/themes/{theme_key}/...`

Forbidden targets (examples; not exhaustive):
- `/core/...`
- `/admin/...`
- `/core/admin/sql/...`
- any path traversal (`../`), absolute paths, or symlink escapes

If any forbidden target is found:
- install FAILS by default

### 2) Core table policy enforcement
Installer must refuse any SQL scripts that:
- write/alter/drop core tables
- attempt to modify core ACL tables, sessions, users, settings, etc.

Extension/theme DB scripts may:
- create/update their own tables only

### 3) Static code scanning (mandatory)
Installer scans extracted PHP files for forbidden patterns indicative of 
rule-breaking, including at minimum:
- direct DB connections (e.g. `new PDO`, `mysqli_`)
- direct role ID checks (e.g. comparing to `998`, `100`, etc.)
- hard-coded writes into protected paths
- attempts to alter core tables by name

This scan is a safety gate.

### 4) Failure override (per-package only)
- If safety checks fail, the install is blocked by default.
- Override rules:
- Overrides apply **only** to static safety checks (path scanning, code 
scanning).
- Overrides MUST NOT bypass:
  - cryptographic verification
  - destination path enforcement
  - core table protection

- Overrides are never global defaults and are logged.

Non-overridable rule:
- Cryptographic verification failure is never overridable.

---

## Manifest (`manifest.xml`) requirements (mandatory)

The manifest is XML and must define at minimum:
- `package_type` (extension or theme)
- `type` (extension subtype such as component/plugin/module OR theme category)
- `name`
- `key` (extension_key or theme_key)
- `version`
- file install mappings (source → destination)
- optional SQL install/uninstall scripts
- signature metadata sufficient for verification (signature value and signed 
payload definition)
- - signature metadata sufficient for verification, as defined by the
  Global Core Trust & Integrity rules


No JSON is permitted.

---

## Logging (ties to Phase 10)

Event logging:
- installs, uninstalls, enable/disable actions → `admin.log`

Error logging:
- extraction failures, verification failures, filesystem errors, SQL execution 
failures → `error.log`

No secrets are logged (no private key, no tokens, no DB credentials).

---

## Acceptance criteria

Phase 20 is complete when:

1. `/admin/extensions` provides a unified UI with tabs for Extensions and Themes
2. Extensions are stored in `#_extensions`, themes are stored in `#_themes`
3. Install from ZIP works using staging + `manifest.xml`
4. Package signature verification using the baked-in public key is enforced and 
non-bypassable
5. Safety checks prevent writing into protected core paths and protected core 
tables
6. Super can override safety-check failures per package only (not global)
7. Uninstall removes only extension/theme code, extension/theme tables, and 
extension/theme media
8. Core items (`is_core = 1`) cannot be disabled or uninstalled
9. All operations are logged correctly (event logs vs `error.log`)
10. No JSON and no external assets are used


# Phase 21 - Devstore — Development-Only Signing & Repository System

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.

---

## 1. Executive Summary

Devstore is a **development-only subsystem** built into the FreeflowCMS core 
(Devstore Edition) that provides:
- Developer registration and approval workflow
- ZIP package upload and validation
- Cryptographic signing of compliant packages
- Repository publishing and feed generation
- Public directory for extensions/themes

**Key Decision:** Devstore is implemented as a **core component** (not an 
extension) to avoid location conflicts and enable manual deployment as part of 
the core system.

---

## 2. Architecture Overview

### 2.1 Directory Structure

```
core/
├── controllers/
│   ├── admin/
│   │   ├── DevstoreDashboardController.php
│   │   ├── DevstoreDevelopersController.php
│   │   ├── DevstorePackagesController.php
│   │   └── DevstoreSettingsController.php
│   ├── api/
│   │   ├── DevstoreFeedsApiController.php
│   │   └── index.php
│   └── site/
│       └── DevstoreController.php
├── models/
│   ├── DevstoreDevelopersModel.php
│   ├── DevstorePackagesModel.php
│   ├── DevstoreComplianceReportsModel.php
│   ├── DevstoreAuditLogModel.php
│   ├── DevstoreSigningService.php
│   ├── DevstoreFeedsModel.php
│   └── DevstoreComplianceService.php
├── views/
│   ├── admin/
│   │   ├── devstore_dashboard.php
│   │   ├── devstore_developers.php
│   │   ├── devstore_packages.php
│   │   └── devstore_settings.php
│   └── site/
│       └── devstore/
│           ├── portal.php
│           ├── portal_upload.php
│           ├── portal_submissions.php
│           ├── directory_list.php
│           └── directory_detail.php
├── keys/
│   ├── freeflow_private.pem (RSA 4096)
│   └── freeflow_public.pem

storage/
└── devstore/
    ├── uploads/
    ├── staging/
    └── repository/
        ├── extensions/
        └── themes/
```

### 2.2 Database Tables

```
#_ext_devstore_developers
#_ext_devstore_packages
#_ext_devstore_compliance_reports
#_ext_devstore_audit_log
```

---

## 3. Compliance Report

### 3.1 Files Created/Modified

| File | Type | Description |
|------|------|-------------|
| `core/admin/sql/install.sql` | Modified | Database schema (Devstore tables 
merged) |
| `core/admin/sql/uninstall.sql` | Modified | Cleanup SQL (Devstore tables 
merged) |
| `core/models/DevstoreDevelopersModel.php` | Created | Developer CRUD 
operations |
| `core/models/DevstorePackagesModel.php` | Created | Package management with 
upload handling |
| `core/models/DevstoreComplianceReportsModel.php` | Created | Compliance report 
storage |
| `core/models/DevstoreAuditLogModel.php` | Created | Audit trail logging |
| `core/models/DevstoreSigningService.php` | Created | RSA 4096 signing service 
|
| `core/models/DevstoreFeedsModel.php` | Created | Feed generation service |
| `core/models/DevstoreComplianceService.php` | Created | ZIP validation and 
compliance checking |
| `core/controllers/admin/DevstoreDashboardController.php` | Created | Admin 
dashboard |
| `core/controllers/admin/DevstoreDevelopersController.php` | Created | 
Developer management |
| `core/controllers/admin/DevstorePackagesController.php` | Created | Package 
management |
| `core/controllers/admin/DevstoreSettingsController.php` | Created | Settings 
management |
| `core/controllers/site/DevstoreController.php` | Created | Developer portal & 
directory |
| `core/controllers/api/DevstoreFeedsApiController.php` | Created | Feed API 
endpoints |
| `core/views/admin/devstore_dashboard.php` | Created | Dashboard view |
| `core/views/admin/devstore_developers.php` | Created | Developer list view |
| `core/views/admin/devstore_packages.php` | Created | Package list view |
| `core/views/admin/devstore_settings.php` | Created | Settings form view |
| `core/views/site/devstore/portal.php` | Created | Developer portal landing |
| `core/views/site/devstore/portal_upload.php` | Created | Upload form with 
immutability notice |
| `core/views/site/devstore/portal_submissions.php` | Created | User submissions 
list |
| `core/views/site/devstore/directory_list.php` | Created | Public 
extension/theme list |
| `core/views/site/devstore/directory_detail.php` | Created | Package detail 
view |
| `core/Application.php` | Modified | Routes added for admin, site, and API |
| `core/MenuRegistry.php` | Modified | Menu entry added |
| `core/PermissionsRegistry.php` | Modified | 7 Devstore permissions added |
| `core/languages/en-GB/language_en-GB.ini` | Modified | 90+ Devstore language 
keys added |
| `core/keys/freeflow_private.pem` | Created | RSA 4096 private signing key |
| `core/keys/freeflow_public.pem` | Created | RSA 4096 public key |

### 3.2 Language Keys

All user-facing strings use language keys with prefix `DEVSTORE.`:

| Category | Keys | Usage |
|----------|------|-------|
| Menu | `DEVSTORE.MENU_DEVSTORE` | Admin sidebar entry |
| Dashboard | `DEVSTORE.DASHBOARD_*` | Admin dashboard |
| Developers | `DEVSTORE.DEVELOPERS_*` | Developer management |
| Packages | `DEVSTORE.PACKAGES_*` | Package list and actions |
| Reports | `DEVSTORE.REPORT_*` | Compliance report display |
| Settings | `DEVSTORE.SETTINGS_*` | Settings page |
| Portal | `DEVSTORE.PORTAL_*` | Developer frontend |
| Directory | `DEVSTORE.DIRECTORY_*` | Public directory |
| Errors | `DEVSTORE.ERROR_*` | Error messages |

### 3.3 Permissions

| Permission | Description | Enforced Where |
|------------|-------------|----------------|
| `devstore_manage` | Full admin access | All admin routes |
| `devstore_approve_developers` | Approve/suspend developers | Developer 
controller |
| `devstore_publish_packages` | Publish packages | Package publish action |
| `devstore_unpublish_packages` | Unpublish packages | Package unpublish action 
|
| `devstore_regenerate_feeds` | Regenerate feeds | Settings controller |
| `devstore_view_audit` | View audit logs | Dashboard/Admin |
| `devstore_access_frontend` | Access developer portal | Site controller, 
Profile tab |

### 3.4 Database Changes

**Install SQL** (`core/admin/sql/install.sql`):
- Core tables (users, roles, permissions, settings, etc.)
- **Devstore tables merged:**
  - Creates `#_ext_devstore_developers` with user_id, status, created_at
  - Creates `#_ext_devstore_packages` with full package metadata
  - Creates `#_ext_devstore_compliance_reports` for violation tracking
  - Creates `#_ext_devstore_audit_log` for audit trail
- Devstore permissions seeded (7 permissions)
- Devstore menu entry added
- Devstore settings configured

**Uninstall SQL** (`core/admin/sql/uninstall.sql`):
- Drops all Devstore tables (in reverse dependency order)
- Drops all core tables
- Does NOT remove repository files (preserves published packages)

### 3.5 Install/Uninstall Steps

**Install:**
1. Run `core/admin/sql/install.sql`
2. Devstore permissions auto-seed via 
`PermissionsRegistry::ensureCorePermissions()`
3. Menu entry auto-seed via `MenuRegistry::ensureCoreMenuEntries()`
4. Devstore enabled by default (`devstore.enabled = 1`)
5. Generate signing key: `openssl genrsa -out core/keys/freeflow_private.pem 
4096`

**Uninstall:**
1. Run `core/admin/sql/uninstall.sql`
2. Remove storage/devstore/ directory (optional)
3. Settings entry auto-removed when table dropped

---

## 4. Access Control Model

### 4.1 Permission Keys

| Permission | Description | Who Gets It |
|------------|-------------|-------------|
| `devstore_access_frontend` | Access developer portal | Approved developers |
| `devstore_manage` | Full admin access | Superuser only (role_id=998) |
| `devstore_approve_developers` | Approve/suspend developers | Superuser only |
| `devstore_publish_packages` | Publish packages | Superuser only |
| `devstore_unpublish_packages` | Unpublish packages | Superuser only |
| `devstore_regenerate_feeds` | Regenerate feeds | Superuser only |
| `devstore_view_audit` | View audit logs | Superuser only |

### 4.2 Access Levels

| Area | Access Control |
|------|----------------|
| Devstore Admin | Superuser only (`role_id = 998`) AND Devstore enabled |
| Developer Portal | Approved developers (status='approved') |
| Public Directory | Anyone (no auth required) |
| Repository Feeds | Anyone (no auth required) |

### 4.3 Devstore Toggle

- Global on/off switch stored in `#_settings` table
- Key: `devstore.enabled` (default: `'1'`)
- When disabled: All Devstore access blocked with "Devstore is currently 
disabled" message

---

## 5. Developer Workflow

### 5.1 Registration & Approval

1. User requests developer access (profile tab or admin request)
2. Superuser reviews and approves in Devstore admin
3. User gets `devstore_access_frontend` permission
4. "Developer Portal" profile tab appears

### 5.2 Package Submission Pipeline

1. Developer fills upload form (ZIP file)
- Zip is placed in staging storage/tmp directory
2. System validates ZIP safety (no path traversal, symlinks, etc.)
3. System extracts and validates `manifest.xml`
4. System runs compliance checks via `DevstoreComplianceService`:
   - Destination path enforcement
   - SQL ownership rules
   - Static code scanning for dangerous PHP patterns
5. **If FAIL:** Compliance report shown to developer, package rejected
6. **If PASS:** 
   - Package signed with RSA 4096 via `DevstoreSigningService`
   - Published to repository
   - Copy of Preview.png/jpg extracted from zip and placed in same directory as 
signature file, if present. 
   - Feeds regenerated
   - Audit logged

### 5.3 Immutability Rule

> "Once a package version has been accepted, signed, and published by the 
system, it becomes immutable. No further alterations are permitted."

Displayed in upload form with checkbox acknowledgement.

---

## 6. Signing Model

### 6.1 Key Management

- **Private Key:** `/core/keys/freeflow_private.pem`
- **Permissions:** `chmod 600` (owner read/write only)
- **Location:** Inside core directory
- **Never:** Stored in database, exposed via UI, or logged

### 6.2 Signing Process

```php
// Canonical string to sign
$payload = $packageType . '|' . $key . '|' . $version . '|' . $sha256;

// Sign with RSA 4096, SHA-256
$signature = openssl_sign($payload, $signature, $privateKey, 
OPENSSL_ALGO_SHA256);
```

### 6.3 Signed Package Contents

```
/repository/{type}/{key}/{version}/
├── package.zip           (original package)
├── signed_payload.xml    (metadata + SHA-256)
└── signature.sig         (base64-encoded signature)
```

---

## 7. Routes

### Admin Routes

| Route | Controller | Method |
|-------|------------|--------|
| `/devstore` | DevstoreDashboardController | index |
| `/devstore/developers` | DevstoreDevelopersController | index |
| `/devstore/developers/{id}/approve` | DevstoreDevelopersController | approve |
| `/devstore/developers/{id}/suspend` | DevstoreDevelopersController | suspend |
| `/devstore/packages` | DevstorePackagesController | index |
| `/devstore/packages/{id}` | DevstorePackagesController | view |
| `/devstore/packages/{id}/publish` | DevstorePackagesController | publish |
| `/devstore/packages/{id}/unpublish` | DevstorePackagesController | unpublish |
| `/devstore/packages/{id}/report` | DevstorePackagesController | report |
| `/devstore/settings` | DevstoreSettingsController | index |
| `/devstore/settings/save` | DevstoreSettingsController | save |

### Site Routes (Developer Portal)

| Route | Controller | Method | Auth |
|-------|------------|--------|------|
| `/devstore` | DevstoreController | portal | Any |
| `/devstore/upload` | DevstoreController | upload | `devstore_access_frontend` 
|
| `/devstore/my-submissions` | DevstoreController | submissions | 
`devstore_access_frontend` |

### Site Routes (Public Directory)

available through /repository

| Route | Controller | Method |
|-------|------------|--------|
| `/directory/extensions` | DevstoreController | directoryExtensions |
| `/directory/themes` | DevstoreController | directoryThemes |
| `/directory/extensions/{key}` | DevstoreController | directoryExtensionDetail 
|
| `/directory/themes/{key}` | DevstoreController | directoryThemeDetail |

### API Routes (Public Feeds)

| Route | Controller | Method |
|-------|------------|--------|
| `/api/ext/devstore/feeds/extensions/index` | DevstoreFeedsApiController | 
extensionsIndex |
| `/api/ext/devstore/feeds/themes/index` | DevstoreFeedsApiController | 
themesIndex |
| `/api/ext/devstore/feeds/extensions/{key}` | DevstoreFeedsApiController | 
extensionFeed |
| `/api/ext/devstore/feeds/themes/{key}` | DevstoreFeedsApiController | 
themeFeed |



---

## 8. Manual Smoke Test Steps

### 8.1 Initial Setup

1. **Run SQL install:**
   ```bash
   mysql -u root -p freecms_devstore < core/admin/sql/install.sql
   ```

2. **Verify permissions:**
   - Go to Admin → Roles
   - Assign `devstore_manage` to Superuser role

3. **Verify menu:**
   - Go to Admin
   - Check "Devstore" appears in sidebar

4. **Check signing key:**
   - Go to Admin → Devstore → Settings
   - Status should show "Signing key is available"
   - Key was generated at: `openssl genrsa -out core/keys/freeflow_private.pem 
4096`

### 8.2 Developer Workflow Test

1. **Create test user:**
   - Register a new user account
   - Note user ID

2. **Approve developer:**
   - Go to Admin → Devstore → Developers
   - Click "Approve" for test user

3. **Test developer portal:**
   - Log in as test user
   - Go to Profile → Developer Portal
   - Verify upload form displays with immutability notice

4. **Test upload:**
   - Create a valid extension ZIP (must have manifest.xml at root)
   - Upload via developer portal
   - Verify success message and package appears in submissions

### 8.3 Admin Workflow Test

1. **View submissions:**
   - Go to Admin → Devstore → Packages
   - Verify uploaded package appears with status

2. **Publish package:**
   - Click "Publish" for a compliant package
   - Verify status changes to "Published"

3. **View directory:**
   - Go to `/directory/extensions`
   - Verify published package appears

4. **Test feeds:**
   - Visit `/api/ext/devstore/feeds/extensions/index`
   - Verify XML feed is returned

---

## 9. Acceptance Criteria Status

| Criterion | Status | Notes |
|-----------|--------|-------|
| Devstore built as  a modular core component | ✅ Done | All files in core/ 
directory |
| Devstore switchable on/off | ✅ Done | Settings toggle in database |
| Superuser-only admin access | ✅ Done | Permissions registered |
| Developer approval workflow | ✅ Done | Admin approve/suspend actions |
| Developer profile tab | ✅ Done | ProfileTabRegistry registration |
| ZIP upload with validation | ✅ Done | Full pipeline implemented |
| Full compliance reports | ✅ Done | DevstoreComplianceService with 20+ rule 
checks |
| RSA 4096 signing | ✅ Done | DevstoreSigningService implemented |
| Repository publishing | ✅ Done | Package publishing to storage/repository |
| XML feeds generated | ✅ Done | DevstoreFeedsModel with API endpoints |
| Public directory UI | ✅ Done | All views created with Bootstrap 5 |
| Immutability enforcement | ✅ Done | Notice and checkbox in upload form |

---

## 10. Summary

Devstore implementation is **complete**. All specification requirements from the 
Phase  have been implemented:

- ✅ Core component architecture (not extension-based)
- ✅ Developer registration and approval workflow
- ✅ ZIP upload with full compliance validation
- ✅ RSA 4096 cryptographic signing
- ✅ Repository publishing with feeds
- ✅ Public directory for extensions/themes
- ✅ Admin interface for Superusers
- ✅ Developer frontend via profile tab
- ✅ Immutability enforcement

**Ready for database installation and testing.**

---

**Plan Version:** 3.2  
**Last Updated:** 2026-01-06



# Phase 22 — Updates & Management (Core, Extensions, Themes)

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope

Phase 21 defines the **manual updates system** for FreeflowCMS, covering:
- Core (system) updates
- Extension updates
- Theme updates
- Repository trust and verification
- Update check storage and presentation
- Update execution, safety, and logging

This phase provides:
- A **manual-only** updates workflow (no cron, no auto-check)
- **XML-only** repository feeds
- **ZIP packages** with internal `manifest.xml`
- **Cryptographic trust** using a public key baked into core
- Super-only update execution
- Deterministic ordering and enforcement rules

This phase does **not** provide:
- Any JSON usage
- Any external libraries or CDNs
- Any automatic update scheduling
- Any bypass of cryptographic verification
- Any user-configurable repository trust without verification

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- AJAX allowed; responses must be HTML or plain text only
- Bootstrap 5 compliant, fully responsive
- No inline CSS
- Core DB connection only
- Super (role 998) is allow-all
- All behaviour must be implemented (no stubs)

---

## Repository model (authoritative)

### Repository endpoints

Update repositories are defined as **authoritative endpoints**.
Endpoints may be stored in the database but **trust is enforced 
cryptographically**, not by DB values.

Default endpoints:

- **Main (core/system):**
  - `https://repo.freeflowcms.online/main`
- **Extensions:**
  - `https://repo.freeflowcms.online/extensions/{extension_key}`
- **Themes:**
  - `https://repo.freeflowcms.online/themes/{theme_key}`

Rules:
- Endpoints must be HTTPS.
- Endpoints are not user-editable via admin UI.
- Endpoints may be changed only via a **verified core update** (DB update 
performed by the core updater).
- Any endpoint change is logged to `updates.log` and `admin.log`.

---

## Trust and verification (mandatory)

### Cryptographic trust model

- A **public key is embedded in core code** (read-only).
- Update feeds and/or package checksums are **signed with the corresponding 
private key**.
- Verification uses **PHP built-in OpenSSL functions only**.
- Signature verification is **mandatory** and **cannot be overridden**, even by 
Super.

If verification fails:
- Update is refused
- No files are extracted
- No DB changes occur

### What is signed

At minimum, the update feed XML provides:
- component key
- version
- ZIP filename
- checksum
- signature

The updater verifies:
- signature validity using the embedded public key
- checksum match after download

---

## Package format

### Update feed
- XML document
- Lives **outside** the ZIP
- Contains metadata about available updates
- Contains signature

### Update package
- ZIP file
- Root contains `manifest.xml`
- `manifest.xml` defines:
  - component key
  - version
  - file operations
  - migrations (if any)
  - compatibility constraints

No JSON is used at any point.

---

## Permissions

Only **Super User (role 998)** may run updates.

Locked permissions:
- `manage_updates_core` — Super only
- `manage_updates_extensions` — Super only
- `manage_updates_themes` — Super only

Non-super admins:
- May use extensions/themes
- May not see or run update actions

---

## Updates UI

### Location
- Admin menu: **System → Updates**
- Route: `/admin/updates`
- Visible to Super only

### Manual model (mandatory)

- The Updates page MUST NOT auto-check on load.
- The page provides a button:
  - **Check for updates**

---

## Check for updates behaviour

When **Check for updates** is clicked, the system MUST:

1. Validate CSRF and permissions.
2. Determine installed components from DB:
   - system/core version
   - installed extensions
   - installed themes
3. Perform repository checks in a **single run**:
   - system (main endpoint)
   - each installed extension
   - each installed theme
4. Parse XML responses.
5. Verify signatures.
6. Store results in the database.
7. Refresh the Updates page list view.

Outbound requests and results are logged to `updates.log`.

---

## Check results storage

Results MUST be stored in DB (row-based; no JSON).

Minimum fields:
- component_type (`system`, `extension`, `theme`)
- component_key (`system`, `{extension_key}`, `{theme_key}`)
- installed_version
- available_version (or NULL)
- status (`up_to_date`, `update_available`, `error`, `unknown`)
- checked_at
- last_error (safe text only)

Optionally:
- raw XML may be stored for diagnostics (XML only).

---

## Check results presentation (UPDATED)

The Updates page displays a **single unified list** of installed components:
- system
- installed extensions
- installed themes

The list exists regardless of update status.

After **Check for updates** completes:
- The same list view is refreshed (full reload or HTML-only AJAX refresh).
- Each list item is **decorated** with update state based on stored check 
results:
  - An “update available” icon is shown when applicable.
  - The available version number is displayed alongside the installed version.
  - Update action controls become visible/enabled only when an update is 
available.
- Items with no available update remain unchanged.

There is **no separate results list**.

### Icons
- Icons use **Font Awesome 6** loaded from the **local admin bundle**
  - `/admin/assets/fontawesome6`
- No external asset loading.

Status mapping (example; mapping must be consistent):
- up-to-date → check icon
- update available → download/arrow icon
- error → warning icon
- unknown → question icon

---

## Update selection model

Updates may only be run if a **successful check** exists in DB.

### Per-item actions
- System: **Update system**
- Extension: **Update extension**
- Theme: **Update theme**

(Shown only when an update is available.)

### Scope actions
- **Update all**
- **Update system**
- **Update extensions**
- **Update themes**

### Deterministic order
`Update all` MUST execute in this order:
1. system
2. extensions
3. themes

---

## Core update execution

When running a core update:

1. Verify permissions and CSRF.
2. Verify feed signature.
3. Download ZIP from official endpoint.
4. Verify checksum.
5. Extract to staging:
   - `/storage/updates/{id}/`
6. Validate `manifest.xml`.
7. Apply file changes per manifest.
8. Run DB migrations (transactions where possible).
9. Update core version in DB.
10. Clear update-available flags.
11. Log success/failure to `updates.log`.

No automatic rollback is required in this phase.

---

## Extension & theme updates

### Eligibility
An extension or theme may be updated only if:
- It is installed
- Its update source is the official Freeflow repository
- Signature verification passes

### Safety rules
- Extension/theme updates MUST NOT modify protected core paths.
- Any attempt to touch protected paths causes failure unless:
  - The package is an official **core** update
  - Processed by the core updater (not extension installer)

---

## Installer safety checks

Before installing or updating an extension or theme:

1. Extract to staging.
2. Load `manifest.xml`.
3. Perform safety checks:
   - Path validation (no protected core writes)
   - Static code scanning for forbidden patterns:
     - raw DB connections
     - hard-coded core writes
     - direct role ID checks
4. On failure:
   - Default: block update
   - Super may **override per-package only**

Overrides:
- Are explicit
- Are per package/run
- Are logged

---

## Logging (ties to Phase 4)

- Update checks, results, and actions are logged to `updates.log`.
- Signature failures and technical faults are logged to `error.log`.
- No secrets, private keys, or tokens are logged.

---

## Acceptance criteria

Phase 21 is complete when:

1. Updates are manual-only and Super-only
2. Repository feeds are XML-only
3. ZIP packages use internal `manifest.xml`
4. Cryptographic signature verification is enforced
5. Update checks store results in DB
6. Updates decorate the existing installed-components list
7. Updates execute in deterministic order
8. Core, extension, and theme updates are supported
9. Safety checks protect core paths and tables
10. Super overrides are per-package and logged
11. No JSON, external assets, or unsafe trust paths exist


# Phase 23 — System Maintenance

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.

## Scope

Phase 14 defines **core system maintenance operations** required to keep 
FreeflowCMS stable, secure, and predictable over time.

This phase provides:
- A Super-only manual maintenance interface
- Safe cleanup of temporary, staging, and orphaned system data
- Explicit, user-triggered maintenance tasks
- Clear logging of all maintenance actions

This phase does **not** provide:
- Any automated or scheduled maintenance
- Any background cron tasks
- Any content-level operations (handled in later phases)
- Any external tooling or libraries

---

## Hard rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer, no vendor directory
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- AJAX allowed, responses must be HTML or plain text only
- Bootstrap 5 compliant, fully responsive
- No inline CSS
- Core DB connection only
- Super (role 998) is allow-all
- All behaviour must be implemented (no stubs)

---

## UI location and access

### Location
- Admin area → **Settings → Maintenance** tab
- No standalone sidebar menu entry

### Access control
- **Super User only**
- No other role may see or access the Maintenance tab
- No granular permissions are defined in this phase (Super-only by design)

---

## Maintenance model

- All maintenance actions are **manual**
- Each action is:
  - Explicitly triggered by the Super user
  - Clearly described in UI
  - Executed synchronously
- No action may run automatically on page load

---

## Maintenance tasks (mandatory)

The following maintenance tasks MUST be implemented.

### 1. Temporary files cleanup

Target:
- `/storage/tmp/`

Behaviour:
- Remove files and directories created by:
  - uploads staged during media handling
  - failed or abandoned operations
- Active temp files must not be removed

Rules:
- Only files older than a safe threshold may be removed (e.g. >24h)
- Directory traversal must be impossible
- Task reports:
  - number of files removed
  - total disk space freed

---

### 2. Update staging cleanup

Target:
- `/storage/updates/`

Behaviour:
- Remove completed or abandoned update staging directories
- Preserve:
  - currently running updates (if any)
  - directories explicitly marked as active

Rules:
- Must not delete update data required for rollback within the same request
- Task reports:
  - number of staging directories removed
  - total disk space freed

---

### 3. Log file maintenance

Target:
- `/storage/logs/`

Behaviour:
- Provide an action to **truncate all logs**
- Individual log management is handled in Phase 10

Rules:
- Logs are truncated, not deleted
- Confirmation required
- Report which logs were cleared

---

### 4. Orphaned thumbnail cleanup

Targets:
- `/storage/media/users/{user_id}/thumbs/`
- Equivalent thumbs directories in other buckets

Behaviour:
- Remove thumbnails whose original image no longer exists

Rules:
- Only thumbnails are removed
- Originals are never inferred or recreated
- Task reports:
  - number of thumbnails removed

---

### 5. Orphaned media reference check (non-destructive)

Behaviour:
- Scan for media records/files that are no longer referenced by:
  - content
  - user profiles (avatar/cover)
- **No automatic deletion**

Rules:
- This task is **report-only**
- Output is informational
- Used to assist Super admin decisions

---

## Maintenance task execution

For each maintenance task:

- UI provides:
  - task name
  - description
  - action button
- Execution flow:
  1. Confirm intent (where destructive)
  2. Validate CSRF and Super role
  3. Execute task
  4. Display result summary

Results must be shown inline in the Maintenance tab.

---

## Safety constraints

- All filesystem operations must:
  - use canonical paths
  - be constrained to allowed directories
- No user-supplied input influences paths
- No recursive deletes without strict boundary checks
- Failures must stop the task safely

---

## Logging (ties to Phase 10)

All maintenance actions are logged:

- Successful runs → `updates.log` or `admin.log` (event entries)
- Failures or exceptions → `error.log`

Log entries include:
- task name
- executed by (user id)
- timestamp
- summary outcome

No secrets or filesystem paths are logged.

---

## UI conventions

- Uses Zulu admin layout
- Maintenance tasks displayed as a list
- Actions are icon-based
- No inline buttons inside forms
- Fully responsive

---

## Acceptance criteria

Phase 22 is complete when:

1. Settings → Maintenance tab exists and is Super-only
2. All listed maintenance tasks are implemented
3. No task runs automatically
4. Cleanup tasks respect safety boundaries
5. Orphan detection is report-only
6. All actions are logged correctly
7. No JSON, external assets, or unsafe filesystem access exists


# Phase 24 — Help Docs System and Admin UI Docs Builder

This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.

## Scope

Implement a deterministic **Docs Builder** that:
- reads Markdown source files from `/docs/`
- generates public-facing HTML into `/docs-html/`
- creates/maintains admin navigation entries to view generated docs by section

Docs source is written by developers/agents as `.md` files.
The system MUST NOT generate `.md` files and MUST NOT hand-edit source docs.

This phase provides:
- `/admin/docs` builder UI (manual build)
- Markdown-to-HTML conversion (safe subset, no external libraries)
- Output generation into `/docs-html/user`, `/docs-html/developer`, 
`/docs-html/sysadmin`
- Admin menu sub-items to browse generated docs

This phase does NOT provide:
- Any public “Docs” auto-routing beyond the generated HTML paths
- Any need for integration beyond generation + admin menu subitems
- Any doc editor in admin
- Any `.docs-html` content authored by agents/users
- Any external asset loading or external markdown libraries
- JSON anywhere

---

## Global rules restated (phase-relevant)

- PHP 8.2+
- MVC architecture
- No Composer / vendor
- No external libraries or CDNs
- No external asset loading
- No JSON anywhere
- PHP only for runtime code; docs source is Markdown files on disk
- Bootstrap 5 compliant output
- No inline CSS (use existing template CSS)
- All behaviour implemented (no stubs)

---

## Directory model (authoritative)

### Source docs (NOT public-facing)
- `/docs/user/`
- `/docs/developer/`
- `/docs/sysadmin/`

Rules:
- `/docs` is source-of-truth and MUST NOT be served publicly.
- Docs Builder reads only from `/docs`.
- Only `.md` files are considered valid source documents.

### Generated docs (public-facing via menu entry or element placement)
- `/docs-html/user/`
- `/docs-html/developer/`
- `/docs-html/sysadmin/`

Rules:
- `/docs-html` is generated output only.
- The system MUST NOT require “integration” beyond the existence of the 
generated output and admin menu subitems.
- The builder MUST preserve directory structure mirroring the source docs 
folders.


## Documentation completeness requirements (mandatory)

FreeflowCMS documentation is not optional or illustrative.
It is a **first-class deliverable** of the system build.

### Authoritative requirement

When the system is built, the agent MUST generate **highly detailed Markdown 
documentation** under `/docs/` for all required sections.

Placeholders, stubs, summaries, or “to be completed later” text are NOT 
permitted.

---

### Required documentation sections and minimum content

#### `/docs/user/`

User documentation MUST include, at minimum:
- Full explanation of the admin interface structure
- How to manage content (pages, posts, categories)
- How media and documents work from a user perspective
- How permissions affect visible actions
- How menus and elements behave
- Clear step-by-step workflows, not summaries

This documentation is written for **non-technical users**.

---

#### `/docs/developer/`

Developer documentation MUST be **extensive and practical**.

It MUST include, at minimum:

##### Extension development
- Full explanation of the Extensions architecture and rules
- Directory layout examples for extensions:
  - MVC structure
  - Assets placement
  - Language files
- Detailed explanation of:
  - `manifest.xml` structure
  - All supported manifest fields
  - API route declarations
  - Admin menu declarations
- At least one **fully worked example extension layout**, including:
  - folder structure
  - example controllers/models/views
  - example API endpoint
  - example permissions
  - example language file

##### API documentation
- Explanation of the `/api` system
- Extension API endpoint registration model
- XML response format requirements
- Authentication rules (session-only, public endpoints)
- ACL enforcement expectations
- Example API request/response (XML)

##### Theme development
- Full explanation of theme structure
- Theme directory layout example
- Template positions and how they are declared
- Relationship between themes and elements
- Theme `manifest.xml` explanation
- Example theme manifest file with annotations

This documentation is written for **developers building extensions and themes**, 
and MUST assume no prior knowledge of FreeflowCMS.

---

#### `/docs/sysadmin/`

Sysadmin documentation MUST include:
- Installation overview
- Directory structure explanation
- Permissions and role model explanation
- Security considerations
- Updates system behaviour
- Backup, maintenance, and recovery concepts
- How to manage extensions and themes safely

Sysadmin documentation MUST assume responsibility for a live production system.

---

### Quality and depth requirements

- Documentation MUST be **descriptive, explicit, and instructional**.
- Bullet lists alone are insufficient; explanatory prose is required.
- Every system feature exposed to users, developers, or sysadmins MUST be 
documented.
- Examples MUST be concrete and realistic, not abstract placeholders.
- Where configuration files exist (`manifest.xml`, language files, etc.), 
examples MUST be included and explained.

---

### Prohibited documentation practices

The following are NOT permitted:
- Placeholder text (e.g. “TODO”, “Coming soon”, “Explain later”)
- One-line descriptions of complex systems
- References to external documentation
- Statements such as “self-explanatory” or “obvious”

Failure to meet documentation depth requirements is considered a **failed 
build**, even if code is otherwise complete.


---

## Access rules (authoritative)

### `/docs` (source)
- MUST NOT be publicly accessible.
- Any attempt to route/serve `/docs` content must be denied.

### `/docs-html` (generated output)
- `/docs-html/user` MUST be publicly accessible.
- `/docs-html/developer` MUST be publicly accessible.
- `/docs-html/sysadmin` MUST be accessible to **Super admin only**.

Rules:
- Public access is satisfied by the presence of generated HTML files and links 
to them.
- Sysadmin docs must enforce Super-only access at request time (server-side).

---

## Admin UI

### Route
- `/admin/docs`

### Admin page behaviour
- List view showing:
  - sections: User / Developer / Sysadmin
  - count of `.md` source files found
  - last build time (per section, stored in DB or file marker)
  - last build result (success/fail, safe text)

Toolbar (left only):
- Build (or Build All)
- Close

Rules:
- Build MUST be manual (no auto-build on page load).
- Build MUST validate permissions (Super only is acceptable and recommended).

---

## Build process (mandatory)

When build is invoked:

1) Validate admin context session and permissions (Super-only recommended).
2) For each docs section (`user`, `developer`, `sysadmin`):
   - scan source: `/docs/{section}/`
   - include only `.md` files
   - ignore hidden files and non-md files
3) For each `.md` file:
   - compute the mirrored target path under `/docs-html/{section}/`
   - convert Markdown → safe HTML using the internal parser described below
4) Write the HTML output file(s) to `/docs-html`:
   - create directories as needed
   - refuse unsafe writes (no traversal; no writing outside `/docs-html`)
5) Generate/refresh index pages:
   - `/docs-html/{section}/index.html` listing available docs in that section
6) Update admin navigation sub-items to point to the generated docs sections:
   - Docs → User
   - Docs → Developer
   - Docs → Sysadmin
7) Log build results.

Rules:
- Builder MUST be deterministic (same inputs produce same outputs).
- Builder MUST NOT fetch anything externally.
- Builder MUST NOT generate `.md` files.
- Builder MUST NOT generate any files outside `/docs-html`.

---

## Markdown parsing rules (no external libraries)

### Supported Markdown subset (mandatory)
The internal parser MUST support at minimum:
- Headings: `#` through `######`
- Paragraphs
- Ordered and unordered lists
- Inline emphasis: `*italic*`, `**bold**`
- Inline code: `` `code` ``
- Code blocks fenced with triple backticks (rendered as escaped text inside 
`<pre><code>`)
- Links: `[text](url)` (URL must be safe)
- Horizontal rule: `---`

### Raw HTML in Markdown
- Raw HTML in `.md` source MUST NOT be passed through.
- Any raw HTML must be escaped and displayed as text.

### Output HTML safety
- All generated HTML must be safe by construction (allow-list output only).
- Any user-provided content in Markdown must be escaped unless specifically 
emitted as safe HTML by the parser.

### Link safety
- Links MUST be rejected or sanitized if they use unsafe schemes:
  - deny `javascript:`, `data:`, or other scriptable schemes
- Allow:
  - `http`, `https`, and site-relative paths
- Unsafe links must be rendered as plain text.

---

## Output HTML structure

Generated pages MUST be wrapped in the site template or a simple Bootstrap 
layout.

Minimum structure:
- A consistent header showing:
  - section name
  - document title (derived from filename or first H1 if present)
- A content container (Bootstrap container)
- Optional sidebar navigation listing docs in the same section (recommended)

Rules:
- No inline CSS.
- No external assets.
- Use existing core/admin template CSS and Bootstrap 5.

---

## Admin navigation sub-items (mandatory)

The system MUST generate/refresh admin navigation entries after build:

- Admin menu: `Docs`
  - `User Docs` → points to `/docs-html/user/`
  - `Developer Docs` → points to `/docs-html/developer/`
  - `Sysadmin Docs` → points to `/docs-html/sysadmin/` (Super-only)

Rules:
- These entries MUST exist after a successful build.
- Sysadmin link MUST be visible only to Super admin.
- Entries MUST be removed/updated deterministically by the builder (no 
duplicates).

Storage rule:
- If admin navigation is stored in DB, it MUST be updated only by core logic.
- No new extension-specific tables are introduced by this phase.

---

## Logging

Log to event logs (e.g. `admin.log`) at minimum:
- build start/end
- section counts
- failures (safe text, no filesystem secrets)

Errors go to `error.log`.

---

## Acceptance criteria

Phase 23 is complete when:

1. `/admin/docs` exists with a manual Build action
2. Source Markdown is read only from `/docs/{user,developer,sysadmin}/`
3. Output HTML is written only under `/docs-html/{user,developer,sysadmin}/`
4. Directory structure is mirrored
5. Markdown is converted using an internal safe subset parser (no external libs)
6. Raw HTML in Markdown is not passed through (escaped)
7. User + Developer docs are publicly accessible (by linking to generated 
output)
8. Sysadmin docs are Super-only at request time
9. Admin menu sub-items are generated/updated after build
10. Build results and failures are logged safely


# Phase 25 — Installer (Independent from System)

## Installation Routine (Self-contained)


This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.


## Scope
Implement `/install/` as a self-contained mini application that:
- runs requirements checks
- collects DB details + validates connection
- collects Super User details
- executes schema from SQL scripts
- seeds mandatory initial data (home page, menus, elements, ACL tables, etc.)
- writes DB-only `/admin/config/config.php`
- shows completion screen with “Go to site” and “Go to admin”

## Self-contained rule
- The installation routine MUST NOT bootstrap or depend on the main runtime 
stack (no Router/View/Auth/ACL/Logger usage).
- The ONLY permitted interaction with main app code is reading the SQL scripts 
as plain text.

## Required outputs
- `/install/index.php`
- `/core/admin/sql/install.sql`
- `/core/admin/sql/uninstall.sql`

### SQL token rule
- All schema table names MUST use the prefix token `#__` (example: `#__users`).
- During installation, the routine MUST replace `#__` with the effective prefix 
before executing SQL.

### DB prefix rule
- The installation routine asks for a table prefix.
- If blank/whitespace, default prefix is `ff_`.
- Prefix MUST match `^[a-z0-9_]+$` and MUST end with `_`.

## Installation flow (authoritative)
The installation routine MUST be implemented as a fully self-contained 
application.

- It MUST be totally independent from the main runtime.
- It MUST NOT use namespaces.
- It MUST NOT use the main router/view/auth/ACL/logger systems.
- It MUST be procedural PHP.
- it must present the logo fflogo.png on its frontend, centralised in the header 
of the page.

The installer MUST follow this exact flow and control logic.

### Step 1 — Requirements check, then welcome screen with results

- On first load, the installer MUST run all environment and requirements checks.
- The installer MUST then render a welcome screen showing the results (pass/fail 
per item).
- If requirements fail, the installer MUST NOT allow proceeding, and if a form 
had been presented, it must repolulate the form with the collected data.

### Step 2 — Collect DB details, test connection, flash result

- The installer MUST collect DB details and table prefix.
- On submit, the installer MUST attempt a test connection and required privilege 
checks.
- The installer MUST show a flash-style success/failure message result.
- On failure, the DB form MUST be re-rendered with fields preserved (except 
password).

### Step 3 — Collect Super User details

- After a successful DB connection test, the installer MUST collect Super User 
details (name, username, email, password, confirm password).
- Validation failures MUST re-render the form with fields preserved (except 
password).

### Step 4 — Execute install.sql, stop on error, back returns to form prefilled

On confirming the Super User form, the installer MUST:

1) Execute `/core/admin/sql/install.sql`
   - Read as plain text.
   - Replace `#__` tokens with the effective prefix.
   - Execute statements sequentially.
2) If ANY SQL error occurs:
   - Installation MUST stop immediately.
   - The error MUST be displayed (sanitised, non-secret).
   - A Back button MUST return to the Super User form with previously entered DB 
and Super User fields preserved (except password).
   - The configuration file MUST NOT be written.

If SQL execution completes successfully:

3) The installer MUST seed exactly ONE record set in PHP:
   - create the initial Super User user account using the submitted details
   - NO other data MUST be seeded in PHP

4) The installer MUST write the configuration file at 
`/core/admin/config/config.php`.

5) The installer MUST stop and render the completion screen.

### Step 5 — Completion

- The completion screen MUST display: **“Initial setup complete.”**
- It MUST provide:
  - “Go to site” → `/`
  - “Go to admin” → `/admin`
- It MUST NOT auto-redirect.
- It MUST display a safety notice that `/install/` should be removed or 
disabled.

## Database creation rule
- The installation routine MUST NOT attempt to create a database. The DB must 
already exist.

## Seeds required (minimum)
After schema execution, the routine MUST seed:
- Super User account (role_id = 998)


## Configuration write rule
- The installation routine MUST write `/core/admin/config/config.php` 
containing ONLY the `db` array (host/port/name/user/password/prefix).
- It MUST NOT write this file if installation failed.


## Installer requirements
### Installation gate and install flag (authoritative)

#### Install flag

- The presence of `/core/admin/config/config.php` is the ONLY install flag.

#### Installation gate

If `/core/admin/config/config.php` is missing:

- Any request except `/install/...` and static assets MUST redirect to 
`/install/` before DB init, routing init, templates, or auth.

If `/core/admin/config/config.php` exists:

- `/install/...` MUST be blocked from reinstall.
- It MUST either redirect to `/` (or `/admin`) or render an “Already installed” 
page.

#### Configuration file location and creation rule

- The configuration file MUST be: `/core/admin/config/config.php`
- `/core/admin/config/config.php` MUST NOT be created by the build or by any 
phase work.
- It MUST be written ONLY by the Installation Routine during a successful 
installation.
- The Installation Routine MUST NOT write this file if installation failed.

#### Configuration contents rule (DB + meta only)

- `config.php` MUST return a PHP array containing ONLY:
  - `db` settings: host, port, name, user, password, prefix
  - `meta` settings: product_name, installed_at, license
- No other settings are allowed in this file.
- Runtime configuration (site name, debug mode, email settings, etc.) MUST be 
stored in the database, not in `config.php`.

> **Completeness:** All requirements in this section MUST be implemented as 
working behaviour (no stubs) per Global Principle 0.11.

#### 4.1 Steps

1. **Welcome / Environment Check**
   - PHP version, extensions, file permissions, `/core/admin/config` writable, 
etc.

2. **Database Configuration**
   - Fields:
     - DB host (optional, default `localhost`; if empty treat as `localhost`)
     - DB port (optional, default `3306`)
     - DB name
     - DB user
     - DB password
     - Table prefix (e.g. `ff_`)
   - Test connection before proceeding.

3. **Site Configuration & Super User**
   - Fields:
     - Site name (required)
     - Tagline (optional; 2nd field)
     - Site URL (auto-detected but editable)
     - Super User:
      - First Name
      - Last Name
       - Username
       - Email
       - Password
       - Confirm password
   - Super User username:
     - Required, min 6 chars, allowed `a–z`, `0–9`, `_`, `-`, unique.
   - Super User password:
     - Must follow global password policy (10+ chars, 1 CAP, 1 digit, 1 symbol).

4. **Install (run `core/admin/sql/install.sql`)**
   - Create all tables.
   - Seed roles & permissions map.
   - Seed initial settings.
   - Seed Alpha & Zulu templates, positions.
   - Seed default menu type + `Home` menu item in a default menu & element.
   - Seed default email templates.
   - Seed default dashboard elements.

5. **Write `config.php`**
   - With DB details and install metadata (including product name: Freeflow 
CMS).
   - Attempt to make it read‑only.

6. **Finish**
   - Lock `/install` directory.
   - Provide link to:
     - Frontend
     - Admin login


---


This phase **MUST** be fully implemented and operational in its entirety; 
partial implementations, deferred completion, simulations, test stubs, 
placeholders, or “finish later” logic are not permitted.

All functionality delivered in this phase **MUST** fully comply with the Global 
Rules, without exception.

_End of running order_
