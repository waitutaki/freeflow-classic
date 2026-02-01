# UNCLE Task Brief — Fix Forms That Don’t Post/Save/Reload Correctly (Whole Codebase, No Logic Changes)

## Context / Problem
Across the codebase (admin + site), multiple forms fail to save and/or reload correctly because:
- fields are not present in POST (missing `name`, outside `<form>`, `disabled`, JS widgets without real inputs)
- controllers/save-handlers do not persist every posted field (incomplete save maps/whitelists)
- controllers/views load defaults because they read different keys/scopes than those being saved

We have confirmed the pattern with logging: some fields appear in POST and save, while others (including toggles and even some selects/text inputs) do not appear in POST at all.

---

## Scope (Whole Codebase)
This task applies to **every HTML form** in the repository:
- Admin forms
- Site/frontend forms
- Any extension/module forms included in the codebase

---

## Non-Negotiable Rules (MUST FOLLOW)
1. **DO NOT change business logic or workflows.**
   - No refactors of how features work.
   - No new behaviours (e.g. autosave timing, validation rules, permissions).
2. **DO NOT change database schema.**
3. **DO NOT rewrite/merge controllers or routes.**
4. Only touch:
   - Form markup/templates (views)
   - The “save/retrieve plumbing” in handlers: mapping POST→persistence, and persistence→view data
5. **Autosaves and special flows must remain intact.**
   - You may add missing `name` fields / hidden mirrors, but do not alter when/why autosave triggers.
6. **No styling changes** unless required to move controls into the correct `<form>` element.
7. All changes must be mechanical, minimal, and consistent.

---

## Primary Objective
For every form:
1) Every user-editable field shown in the UI must be included in the submitted payload (POST), **for all states** (including “off/unchecked”).
2) The save handler must persist all of those fields correctly.
3) The retrieve/load path must populate the form with the persisted values (no unexpected defaults due to mismatched keys/scopes).

---

## Required Fix Patterns (Apply Everywhere)

### A) Missing `name` Attribute
If an input/select/textarea has no `name`, it will not POST.
- Add `name="..."` for every field intended to be saved.
- Ensure the `name` matches the key expected by the save handler.

### B) Fields Outside the `<form>`
Inputs outside the form do not POST.
- Ensure all fields are inside the correct `<form>`.
- If UI structure prevents this, add a hidden input inside the form and mirror the UI value into it.

### C) `disabled` Fields Do Not Submit
`disabled` inputs/selects are never submitted.
- Prefer `readonly` for text inputs when you must prevent editing.
- For disabled selects/inputs that must still submit, add a hidden field:
```html
<input type="hidden" name="KEY" value="...">
<select name="KEY" disabled>...</select>
```

### D) Checkboxes / Switches Must Submit Both States
Unchecked checkboxes submit nothing unless handled.
- Use the hidden+checkbox pattern:
```html
<input type="hidden" name="KEY" value="0">
<input type="checkbox" name="KEY" value="1" ...>
```

### E) JS Widgets / “Fake Controls”
If the UI uses custom dropdowns/toggles/editors (div-based), ensure there is a real form field:
- hidden input OR real select/input with `name="KEY"`
- JS must keep it updated

### F) Multiple Forms / Wrong Form Submission
Tabbed UIs often contain multiple forms.
- Ensure the Save button submits the correct form.
- Use `form="formId"` if needed to bind buttons/inputs to the right form.

### G) Server-side Field Loss Due to Limits (Guard Check)
If a form has many fields, PHP may drop inputs due to `max_input_vars` (common).
- If POST keys appear truncated, document it in the report and recommend increasing `max_input_vars`.
- Do not change server config in this task.

---

## Save/Retrieve Plumbing Rules (No Logic Changes)
For each form handler (controller):
- Identify the persistence keys it saves (DB/settings/model)
- Ensure it saves **every** posted field that appears in the UI for that form
- Ensure the corresponding load/retrieve path uses the **same keys** to populate the view

**Important:** Do not change validation rules or behaviour; only ensure the data path is complete and consistent.

---

## Verification Method (Mandatory, Temporary Logging)
For each form endpoint, add temporary logs (guarded by `APP_DEBUG` if available):

### 1) Log POST keys on submit
```php
if (defined('APP_DEBUG') && APP_DEBUG === true) {
    error_log('[FORMFIX] ' . __METHOD__ . ' POST keys=' . implode(',', array_keys($_POST)));
}
```

### 2) Log key names being saved (without sensitive values)
```php
if (defined('APP_DEBUG') && APP_DEBUG === true) {
    error_log('[FORMFIX] saving keys=' . implode(',', $keysBeingSaved));
}
```

Remove or guard these logs after verifying each form.

---

## Work Plan (MUST DO IN ORDER)

### 1) Inventory All Forms
- Find all `<form` occurrences in templates and list:
  - template path
  - action/route
  - handler/controller method
  - fields inside form (name + type)

### 2) For Each Form: POST Coverage Fix
- Ensure each field intended to be saved:
  - has `name`
  - is inside the correct `<form>`
  - is not `disabled` (or has a hidden mirror)
  - checkboxes have hidden+checkbox
  - JS widgets have hidden mirror
  - Save button submits the correct form

### 3) For Each Form: Save Handler Completeness
- Ensure the handler persists all expected keys.
- If handler uses a whitelist/allowed-keys list, ensure it includes every field in that form.
- Do not change logic; only ensure missing fields are included in the persistence map.

### 4) For Each Form: Retrieve/Load Consistency
- Ensure the handler/view loads values from the same keys that are saved.
- Fix mismatches of key names (e.g. `maintenance_enabled` vs `maintenance.mode`) only if they are clearly inconsistent and causing defaults.
- Keep key changes minimal; document any changes.

### 5) Verification Pass
For each form:
- Submit with representative values
- Confirm POST keys include all expected fields
- Confirm persistence updated (DB/settings)
- Reload form and confirm values reflect persistence

---

## Deliverable / Change Report (Mandatory)
Provide a report listing:
- All forms audited (path + route + handler)
- For each form:
  - Fields fixed (name/inside form/disabled/checkbox hidden/JS mirror/multi-form)
  - Save handler fixes (keys persisted)
  - Retrieve fixes (keys used to populate view)
- Files changed

---

## Definition of Done
- Every form in the codebase posts all intended fields on submit
- Every form’s handler saves all intended fields (including “off/unchecked”)
- Reloading forms reflects persisted data (no silent defaults from key mismatches)
- No functional behaviour changes beyond fixing form submission + save/retrieve consistency
