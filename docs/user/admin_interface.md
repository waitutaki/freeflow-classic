# Admin Interface Overview

The Admin area is the control center for Freeflow CMS. It is separated from the site front end and uses its own login, layout, and navigation. Every screen follows the same structure so you can learn it once and reuse it everywhere.

## Layout structure

- Top bar: brand logo and quick actions.
- Sidebar: the main navigation for system sections.
- Workspace: the main content area where lists and forms appear.
- Toolbar row (inside the workspace): primary actions such as New, Save, and Close.

## Sidebar navigation

The sidebar is generated dynamically from the system menu registry. Items are grouped by section. If an item is inactive or unavailable to your role, it is either hidden or marked as inactive. Sub-items appear under their parent section.

## Toolbars and actions

- Toolbar buttons always include an icon and a visible label.
- Buttons are grouped on the left side of the toolbar.
- Row actions in lists are shown at the end of each row.
- Status indicators in lists are not clickable and are used only to show state.

## List screens

Most screens in Admin are list screens with actions on the right. Lists are consistent:

- Primary content appears first (name, title, or key).
- Actions appear last.
- Status indicators use icons for enabled/disabled states.
- Rows have clear spacing and separators for readability.

## Forms

Forms are used for editing, creating, and configuring items. Each form:

- Groups fields into sections.
- Uses required markers where needed.
- Validates inputs before saving.
- Shows errors inline.

## Common actions

- View: opens a read-only detail or preview.
- New: creates a new record.
- Edit: opens the edit form.
- Save: persists changes.
- Delete: removes a record after confirmation.

## Roles and permissions impact

If you do not have permission for a section or action, it will not appear or will redirect you to the login screen. Your visibility in the Admin area is always based on your role permissions.
