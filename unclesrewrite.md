# UNCLE DIRECTIVE: WEB INSTALL SYSTEM REWRITE (UPDATED — DOWNLOAD_URL + SHA256 + 
ARCHIVE_NAME)
## IMPORTANT
**DO EACH PHASE ONE AT A TIME.  
DO NOT SKIP A PHASE.  
DO NOT “IMPROVE” OR INTERPRET.  
COMPLETE AND TEST EACH PHASE BEFORE MOVING TO THE NEXT.**

This document defines WHAT must change and WHERE.
It does NOT ask for creative redesigns.
It does NOT ask for speculative features.
It does NOT ask for placeholder logic.

---

## CONTEXT (READ ONCE)
The current system can:
- Fetch and display DevStore index feeds correctly (extensions and themes)
- Render package cards correctly

The current system FAILS because:
- It has historically guessed endpoints (`{key}/feed`) and guessed download 
paths from `repo_path`
- Per-package feed endpoints returned 404
- Even after adding download URLs to feeds, the client still derives download 
URLs from `repo_path`
- The checksum key is inconsistent (feed provides `sha256`, client expects 
`checksum`)

This update makes the system:
- Use the DB-backed `archive_name` in feeds
- Emit a canonical `download_url` in feeds (extensions + themes)
- Force the client to use `download_url` verbatim (never derive)
- Force checksum verification using the correct `sha256`

---

# PHASE 0 — STABILISATION (NO FEATURE WORK)

### 0.1 Fix invalid static call
**File:** `core/controllers/admin/AdminExtensionsController.php`

- Replace any call to:
  - `self::getUpdateStatusMap()`
- With:
  - `$this->getUpdateStatusMap()`
- OR make the method static if and only if it is intended to be static.

✅ Phase 0 complete when the Extensions admin page loads without fatal errors.

---

# PHASE 1 — DEVSTORE API FEEDS ARE DB-BACKED AND PUBLISHED-ONLY (EXTENSIONS + 
THEMES)

## 1.1 DATA SOURCE RULE (AUTHORITATIVE)
**ALL data exposed by DevStore API feeds MUST come directly from the database.**
- No guessing
- No constructing values from conventions
- No inferring values from directory structure

If required data does not exist in the database, the package MUST NOT be exposed 
in the feed.

## 1.2 PUBLISHED-ONLY RULE (NON-NEGOTIABLE)
DevStore API feeds MUST include **ONLY packages marked as published** in the 
database.
- Unpublished/draft/pending/disabled packages MUST NOT appear in any feed
- Filter by published state at query level

## 1.3 SCOPE (EXTENSIONS AND THEMES)
This applies to:
- Extensions index feed
- Themes index feed

Valid `type` values:
- `extension`
- `theme`

Plural forms MUST NOT appear anywhere (feeds, URLs, POST data, installer logic).

---

# PHASE 2 — DEVSTORE FEED OUTPUT CONTRACT (ARCHIVE_NAME + DOWNLOAD_URL + SHA256)

## 2.1 REQUIRED FIELDS IN INDEX FEED (DB-BACKED)
Each `<package>` entry in the DevStore **index feed** MUST include:

- `key`
- `name`
- `version`
- `author`
- `description`
- `signature`
- `sha256`  (this is the authoritative checksum value)
- `repo_path` (allowed but informational)
- `preview_url` (optional, may be relative)
- `filename`  (MUST be the DB archive name)
- `download_url` (MUST be absolute and correct)

## 2.2 CANONICAL ARCHIVE NAME RULE (MANDATORY)
The feed MUST output the archive filename from the database column:
- `archive_name`

Mapping:
- `<filename>` = `{archive_name}` (exact match)

The feed MUST NOT:
- invent filenames
- derive filenames from key/version
- guess archive names

If `archive_name` is missing, the package MUST NOT appear in the feed.

## 2.3 CANONICAL DOWNLOAD URL RULE (MANDATORY)
The feed MUST output `download_url` using the canonical pattern:

`https://freeflowcms.online/api/ext/devstore/downloads/{type}/{key}/{version}/{
archive_name}`

Where:
- `{type}` is singular: `extension` or `theme`
- `{key}` from DB
- `{version}` from DB
- `{archive_name}` from DB (`archive_name`)

Hard requirements:
- `download_url` MUST be absolute
- `download_url` MUST point to a real downloadable file
- `download_url` MUST NOT be constructed client-side

✅ Phase 2 complete when curl of the index feed shows correct:
- `<sha256>`
- `<filename>` (matches DB `archive_name`)
- `<download_url>` (matches the canonical pattern and returns HTTP 200 for 
published packages)

---

# PHASE 3 — CLIENT MUST USE FEED download_url AND FEED sha256 (NO DERIVATION)

## 3.1 STANDARDISE INTERNAL KEYS (MANDATORY)
**Client-side internal descriptor MUST store checksum as SHA256 from feed.**

Choose ONE internal field name and use it everywhere:
- Either store it internally as `sha256`
- OR store it internally as `checksum`

If you choose `checksum` internally:
- feed `<sha256>` MUST be mapped into `$desc['checksum']`
- do not look for `$selected['checksum']` unless you mapped it during parsing

🚫 Do not mix `sha256` and `checksum` without mapping.

## 3.2 PARSE download_url + filename FROM INDEX FEED
**File:** `core/UpdateService.php`
Where packages are parsed from the index feed, ensure each package array 
includes:
- `download_url` (from feed)
- `filename` (from feed; equals DB archive_name)
- `sha256` (from feed)

## 3.3 RESOLVE PACKAGE MUST NEVER BUILD download_url
**File:** `core/UpdateService.php`
**Function:** `resolvePackage(type, key, version)`

`resolvePackage()` MUST:
- select the correct package entry from index feed packages
- return the selected `download_url` verbatim
- return the selected `filename` verbatim
- return the selected `sha256` (mapped to the chosen internal checksum key)

🚫 `resolvePackage()` MUST NOT:
- derive download_url from repo_path
- append `.zip`
- guess paths
- use any `{key}/feed` endpoint

If `download_url` is missing/blank:
- fail with a clear error: `No download_url in feed for {type}/{key}/{version}`

✅ Phase 3 complete when logs show the exact `download_url` from the feed being 
used.

---

# PHASE 4 — DOWNLOAD AND VERIFY USING FEED VALUES

## 4.1 downloadPackage MUST USE download_url EXACTLY
**File:** `core/UpdateService.php`
**Function:** `downloadPackage(type, key, version)`

Must:
- call `resolvePackage()`
- download from `$desc['download_url']` exactly (no modification)
- save file using `$desc['filename']` if provided (preferred) else fallback name
- verify SHA256 using the feed value (`sha256` mapped correctly)
- return diagnostics:
  - http status
  - bytes written
  - final URL

If HTTP is 404:
- log the exact download_url
- return failure with HTTP status

## 4.2 NO MEMORY DOWNLOADS
Zip download MUST be streaming (cURL preferred) with:
- redirects enabled
- timeouts set
- bytes written recorded

✅ Phase 4 complete when a published package downloads successfully and sha256 
verifies.

---

# PHASE 5 — INSTALLER BOUNDARY ENFORCEMENT

PackageInstaller MUST:
- accept a local zip path only
- never know about feeds/URLs/devstore

UpdateService MUST:
- handle all remote logic and verification

✅ Phase 5 complete when PackageInstaller is invoked only after a verified 
download.

---

# PHASE 6 — ROUTING + UX + ERROR VISIBILITY (EXTENSIONS + THEMES)

## 6.1 SINGLE CANONICAL POST VALUES
Install POST must send:
- `type` = `extension` or `theme` (singular only)
- `key`
- `version`

## 6.2 REDIRECT BACK TO WEB TAB ON FAILURE
On failure, redirect to:
- `/admin/extensions?tab=web`

Surface error reason via admin flash message.

✅ Phase 6 complete when failures are visible on the web tab.

---

# ACCEPTANCE TESTS (ALL MUST PASS)

## Feed tests
- `curl https://freeflowcms.online/api/ext/devstore/feeds/extensions/index` 
returns only published extensions
- `curl https://freeflowcms.online/api/ext/devstore/feeds/themes/index` returns 
only published themes
- Each package includes:
  - `sha256`
  - `filename` matching DB `archive_name`
  - `download_url` matching canonical pattern
- `curl -I {download_url}` returns HTTP 200 for published packages

## Install tests
- Clicking Install uses `download_url` from feed (no repo_path derivation)
- Download completes and sha256 verifies
- PackageInstaller runs
- Extension/theme appears installed
- Downgrades are possible by selecting older versions

---

## FINAL RULE
**DO NOT PROCEED TO THE NEXT PHASE UNTIL THE CURRENT PHASE IS COMPLETE AND 
VERIFIED.**
