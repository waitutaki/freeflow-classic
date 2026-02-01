# UNCLE Task Brief — Fix Admin Settings Forms Not Posting/Saving Values (One Pass)

## Context / Problem
Many Admin settings subtabs (across multiple tabs) **do not save** because some UI controls (especially toggles/checkboxes) **do not appear in POST**, so the controller never writes them to `ff_settings`.  
We have confirmed via logging that only a subset of fields appear in POST (e.g. `maintenance_message`, `maintenance_retry_after_minutes`, `maintenance_allowlist_ips`) and **the on/off toggle key is missing from POST**.

This task is to fix **all settings forms** so every field displayed in the UI is:
1) inside the correct `<form>`  
2) has a proper `name="..."`  
3) is posted on save **including “off/unchecked” states**  
4) is saved by `AdminSettingsController.php` to settings storage.

---

## Scope
### Admin Settings Tabs/Subtabs to cover
**Global tab**
- General
- Maintenance
- SEO
- Meta Display
- Media
- Locale

**Mail tab**
- Mail Templates
- Mail Wrappers
- Settings

**Other tabs**
- Security
- Freegate
- Logs
- Maintenance

---

## Non‑Negotiable Rules (MUST FOLLOW)
1. **DO NOT refactor architecture.** No new services, no moving files, no renaming routes, no changing app flow.
2. **DO NOT change database schema.** Do not alter the `ff_settings` table or constraints.
3. Only edit:
   - the admin settings view template(s) that render forms (e.g. `core/views/admin/settings.php` and any included partials)
   - `core/controllers/admin/AdminSettingsController.php`
4. **DO NOT change styling/layout** unless absolutely required to ensure controls are inside the `<form>`.
5. Keep existing setting keys unless they are clearly inconsistent/duplicated. If you must standardise, do it **minimally** and document the decision in the change report.
6. Avoid introducing new dependencies. Keep changes simple and mechanical.

---

## Required Fix Patterns

### A) Checkboxes / Switches MUST POST both states
Unchecked checkboxes do not submit a value. Fix all checkbox/switch inputs using the *Hidden + Checkbox* pattern:

```html
<input type="hidden" name="SOME_KEY" value="0">
<input type="checkbox" name="SOME_KEY" value="1" <?= !empty($data['SOME_KEY']) ? 'checked' : '' ?>>
```

This guarantees `SOME_KEY` is present in POST whether checked or unchecked.

**Apply this to every boolean/toggle field across every subtab.**

### B) Ensure every control is inside the correct `<form>`
Any input outside the `<form>` will never be posted.

- Ensure every setting field shown in each subtab is within the `<form>` that submits that subtab.
- If UI layout makes that difficult, place a hidden input inside the form and mirror the UI value into it.

### C) Ensure every control has a `name="..."` attribute
No `name` means no POST.
- Audit all controls for missing `name`.
- Ensure the `name` matches the setting key expected by the controller.

### D) Controller MUST save every posted setting field for that subtab
For each subtab save handler in `AdminSettingsController.php`, ensure:
- every field rendered in that subtab is saved to settings
- boolean values are saved as `'0'` or `'1'` and type `'bool'` (or `'int'` if the system expects that)
- numeric fields are cast/validated minimally (int values saved as type `'int'`)
- string/text fields are saved as type `'string'` or `'text'` as appropriate

**Preferred simple pattern (because hidden+checkbox guarantees keys exist):**
```php
Settings::set('some_boolean_key', (string)($_POST['some_boolean_key'] ?? '0'), 'bool', false, 'global');
```

### E) POST Key Coverage Verification (Temporary Debug)
Add temporary debug logging in `AdminSettingsController.php` **for each subtab save path**:

```php
error_log('[FFSET] subtab=' . ($subtab ?? '') . ' POST keys=' . implode(',', array_keys($_POST)));
```

Use this to confirm that **every field displayed** in the subtab appears in POST.  
After verification, remove or guard this behind `APP_DEBUG`.

---

## Work Plan (MUST DO IN ORDER)

### 1) Inventory Fields Per Subtab
For each subtab listed above:
- List every input/control shown to the user (name + type).

### 2) Fix the View(s)
For each subtab’s form UI:
- Ensure each field:
  - has a `name`
  - is inside the correct `<form>`
  - checkboxes/switches use hidden+checkbox pattern
- Ensure the currently saved value is displayed correctly from `$data[...]` (or equivalent)

### 3) Fix the Controller Save Logic
In `AdminSettingsController.php`:
- For each subtab save handler, ensure it saves:
  - all string fields
  - all numeric fields
  - all boolean fields (including off state)
- Do **not** rely on “missing POST key means keep old value” for booleans—always write `'0'` or `'1'`.

### 4) Verification Pass (Mandatory)
For each subtab:
- Load the subtab
- Change every field (including toggling booleans on and off)
- Save
- Confirm:
  - POST contains all keys (via temporary log)
  - DB row(s) updated in `ff_settings`
  - Reload shows the new values (not defaults)

### 5) Deliverable / Change Report (Mandatory)
Provide a summary listing:
- Files changed
- Each tab/subtab validated
- For each subtab:
  - fields fixed to ensure POST coverage
  - booleans fixed with hidden+checkbox pattern
- Any keys you had to standardise (with reasons)

---

## Notes / Constraints
- Settings storage uses `ff_settings` and `Settings::set()` is already functional.
- The recurring bug is **UI not posting values** (especially unchecked checkboxes and controls outside `<form>`).
- Fixes must be mechanical and consistent; do not “improve” unrelated code.

---

## Definition of Done
This task is complete when:
- Every field visible in the settings UI for all subtabs listed is included in POST on save
- Every field is written to DB on save (including boolean “off”)
- Reloading the page reflects DB values (no unexpected defaults)
- A concise change report is produced
