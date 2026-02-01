# Freeflow CMS — Extension & Theme Installation and Update Specification

## 1. Scope

This specification defines the **installation and update system for extension and themes** in Freeflow CMS.

It applies to:
- Installing new extension and themes
- Updating extension and themes on an **existing Freeflow CMS installation** (the expected and primary use case)

This specification does **not** describe:
- The Freeflow CMS base installer
- Initial database creation or seeding

All required tables and core settings are assumed to already exist before this system runs.

---

## 2. Architectural overview

### 2.1 Admin UI responsibility

- The **extension Manager** is the **single control centre** for:
  - Installing extension and themes
  - Checking for updates (extension and themes)
  - Triggering updates (single item or update all)

A separate **Themes Manager** may exist for theme administration, but it is 
**not involved** in update discovery or update execution.

### The admin UI must list all installed extension or themes in two seperate 
tabs.
- The list items, in ALL cases, must display the extension/theme name, 
installed version number, date installed or updated whichever is later, it 
must also show available updates or 'No Update Available'. 
- it must also have suitable action icons for Publish/Unpublish, uninstall, 
update.

There is **no separate “Updates” section** in the Admin UI.
 the old one can be removed.

---

### 2.2 Execution responsibility

A shared **Installer / Updater pipeline** performs:
- Staging
- Validation
- Signature verification
- Extraction
- Registration
- Database updates

This pipeline is used for both **fresh installs** and **updates**.

---

## 3. Critical independence from DevStore

### 3.1 Non-negotiable requirement

The installer/update pipeline must be **fully self-contained within the core CMS** and must **not** depend on any DevStore runtime code.

This requirement is critical because DevStore may be removed entirely when producing the base CMS distribution.

After DevStore is removed:
- Extension and theme installs must continue to work
- Updates must continue to work
- Trust and verification must not be weakened
- No missing class, file, or service references may exist

---

### 3.2 Forbidden dependencies

The installer/update pipeline must **not**:
- Import or call DevStore classes or helpers
- Depend on DevStore database tables
- Reuse DevStore compliance or validation routines
- Change behaviour based on DevStore being present

DevStore is an **upstream approval and signing authority**, not a runtime dependency.

---

## 4. Repository communication (“calling home”)

### 4.1 API-only rule

All communication with repositories must be performed **only via the official repository API**.

Forbidden:
- Direct repository filesystem access
- Scraping repository HTML
- Hard-coded assumptions about repository storage layout

This rule applies even if DevStore is installed.

---

## 5. Trust anchor (canonical and immutable)

### 5.1 Embedded public signing key

The official DevStore public signing key is distributed with Freeflow CMS at:

```
core/keys/freeflow_public.pem
```

Rules:
- Must be treated as read-only
- Must never be downloaded, regenerated, or replaced at runtime
- Installer must load the public key **only from this location**

---

### 5.2 Fingerprint (database trust anchor)

The trusted fingerprint of the embedded public key is stored in:

- Table: `#__settings`
- Column: `setting_key`
- Key name: `freeflow_public_key_fingerprint`

Rules:
- Seeded during CMS installation
- Read-only for the installer/update pipeline
- Missing or unreadable fingerprint = **fail closed**
- This fingerprint is a **shared security primitive** and is also used by Freegate

---

## 6. Package archive and manifest rules

### 6.1 Package archive filename

The ZIP archive filename is **arbitrary**.

The installer must not rely on the archive filename for:
- Package key
- Package type
- Package version

---

### 6.2 Manifest file (mandatory)

Every package must contain a file named:

```
manifest.xml
```

Rules:
- `manifest.xml` must be located in the **root of the ZIP archive**
- The installer must look **only** in the ZIP root for `manifest.xml`
- Exactly one manifest must exist

Forbidden:
- Searching subdirectories for a manifest
- Guessing manifest location
- Accepting multiple manifests

If `manifest.xml` is missing or unreadable:
- Abort install/update
- Delete staged files
- Display an error to the admin

---

## 7. Install destination rules

The install/update destination is determined **only** by `manifest.xml:type`.

- `type = extension` → `extension/{key}/`
- `type = theme` → `themes/{key}/`
- `type = main` → `main/` Handled by updater not installer

Both `extension/` and `themes/` are **root install directories**.

Forbidden:
- Guessing type from file contents
- Inferring destination from directory layout
- Installing to any other path

Missing or invalid `type` → abort install/update.

---

## 8. Install vs update decision

Whether an operation is an **install** or an **update** is determined by the database, not the filesystem.

- extension: check `#__extension` for the package key
- Themes: check `#__themes` for the package key

Rules:
- Record exists → update
- Record does not exist → new install
- Filesystem presence must not be used as truth

---

## 9. Installer / updater pipeline

### 9.1 Staging

All packages must be staged to:

```
storage/tmp
```

No direct extraction from upload or download streams is permitted.

---

### 9.2 Manifest inspection

The installer must read `manifest.xml` and extract:
- `type`
- `key`
- `version`

Missing or invalid fields → abort and discard staged files.

---

### 9.3 Signature and fingerprint verification

Before extraction:

1. Load `core/keys/freeflow_public.pem`
2. Read fingerprint from `#__settings.freeflow_public_key_fingerprint`
3. Compute fingerprint of the loaded public key
4. Compare fingerprints (must match exactly)
5. Call repository API to retrieve signature data for `{key, version}`
6. Verify package signature using the embedded public key

Proceed **only if all checks pass**.

Failure behaviour:
- Display warning with details
- Delete staged files
- Make no file or database changes

---

### 9.4 Extraction

If verification passes:
- Extract package to the destination dictated by `manifest.xml:type`
- Overwrite existing package files during updates

---

### 9.5 Post-install / post-update actions

Only actions explicitly declared in the manifest may be executed:

- Admin menu registration → `#__admin_menu_items`
- API route registration → `#__api_routes`
- SQL scripts → explicit file paths declared in the manifest

Auto-discovery of SQL files is forbidden.

---

### 9.6 Atomic behaviour

Per package:
- Either the operation fully succeeds
- Or the system remains unchanged

No partial installs, no deferred steps.

---

## 10. Database interaction limits

### 10.1 Core tables of interest

The installer/update pipeline may directly manage state only in:
- `#__extension`
- `#__themes`

These tables already exist.

Adjustments may be required (e.g. new columns for update markers), but:
- Changes must be additive
- No destructive schema changes unless explicitly specified
- No unrelated tables may be modified

---

### 10.2 Package-declared SQL

Packages may manage their own tables via SQL scripts declared in the manifest.

Rules:
- Install SQL runs only on first install
- Migration SQL runs only on update
- Migrations should be idempotent or guarded

---

## 11. Update discovery (extension Manager)

### 11.1 Toolbar: Check Updates

The extension Manager must provide a **Check Updates** button.

When triggered:
1. Read installed extension from `#__extension`
2. Read installed themes from `#__themes`
3. For each installed package, collect:
   - type
   - key
   - installed version
4. Call the repository API to retrieve the package version feed
5. Compare installed version to latest version
6. Persist update availability state for UI display

No downloads or installs occur during this step.

---

## 12. Repository version feed

### 12.1 Feed location

For each package:

- extension: `extension/{key}/feed.xml`
- Themes: `themes/{key}/feed.xml`

Core uses the same mechanism under `main/feed.xml`.

Feeds must be accessed **via the repository API only**.

---

### 12.2 Source-of-truth rule

`feed.xml` is the **authoritative source of truth** for update availability.

Rules:
- Feeds contain **only DevStore-approved stable releases**
- Versions follow strict `MAJOR.MINOR.PATCH` numeric format
- The latest feed entry is always eligible for update

The updater must not implement:
- Channels
- Pre-release handling
- Version qualifiers

---

### 12.3 Version parsing

Accepted versions must match:

```
^\d+\.\d+\.\d+$
```

Comparison must be numeric by segment. String comparison is forbidden.

---

## 13. Update actions (extension Manager)

### 13.1 Update indicators

For each updateable package:
- Display a visually distinct **Update Available** indicator
- Indicator must be clickable to update that single package

---

### 13.2 Update All

If any updates exist:
- Display **Update All** in the toolbar
- Applies to all updateable extension and themes
- Does not include core updates

---

## 14. Applying updates

Triggered updates are handed off to the installer pipeline.

After a successful update:
- Update the correct table (`#__extension` or `#__themes`)
- Update installed version
- Clear update markers
- Update timestamps

The installer must never update the wrong table.

---

## 15. Feedback to Admin UI

The installer must return per-package results:
- success or failure
- human-readable message(s)
- ambiguity or remediation hints

The extension Manager must display results clearly.

Silent failures are forbidden.

---

## 16. Core updates (explicitly separate)

Core updates:
- Use the same `feed.xml` mechanism under `main/feed.xml`
- Require explicit admin action
- Require admin password re-entry
- Are the only operations permitted to overwrite core files
- Are never included in Update All

---

## 17. Summary

This system ensures:
- Identical trust outcomes with or without DevStore installed
- No DevStore runtime dependency
- Strong, local verification of all installs and updates
- A single, predictable admin workflow

The update mechanism is deterministic, secure, and intentionally boring.

