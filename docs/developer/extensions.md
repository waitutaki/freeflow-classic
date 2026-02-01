# Extension Development

Freeflow CMS extension are installed from ZIP packages that include a manifest and a structured directory layout. extension are sandboxed by namespace and by database table ownership.

## Extension architecture

An extension is a self-contained module that lives under `/extension/{ext_key}/`. It uses the same MVC conventions as core:

- Controllers live in `Controllers/`
- Models live in `Models/`
- Views live in `Views/`
- Language files live in `Languages/`

extension may expose:

- Admin pages under `/admin/ext/{ext_key}/...`
- API endpoints under `/api/ext/{ext_key}/...`
- Optional media assets under `/storage/media/extension/{ext_key}/`

## Directory layout example

```
/extension/com_example/
  Controllers/
    Admin/
      DashboardController.php
    Api/
      StatusController.php
  Models/
    ItemsModel.php
  Views/
    admin/
      dashboard.php
  Languages/
    en-GB.ini
  assets/
    css/
    js/
  manifest.xml
```

## Table ownership rules

extension may only write to tables that they own:

- `#__ext_{ext_key}_*`

Core tables are read-only. Any attempt to write to core tables is blocked by the database guard.

## Language files

extension must provide language files under `Languages/` in INI format. Keys are namespaced using a prefix:

```
COM_EXAMPLE.TITLE="Example extension"
COM_EXAMPLE.DESCRIPTION="..."
```

The prefix must be the uppercased extension key.

## Admin controllers

Admin controllers are dispatched through:

```
/admin/ext/{ext_key}/{controller}/{action}
```

- `controller` defaults to `Dashboard`
- `action` defaults to `index`

The controller class follows the namespace pattern:

```
extension\{StudlyKey}\Controllers\Admin\DashboardController
```

## API controllers

API controllers are dispatched through:

```
/api/ext/{ext_key}/{route_path}
```

Routes are registered in the database from the manifest, and their controllers live in:

```
extension\{StudlyKey}\Controllers\Api\StatusController
```

## Install and uninstall

- extension are installed through the Admin installer.
- SQL install and uninstall scripts must only target your namespace tables.
- Packages are rejected if they attempt to modify core tables or paths.

## Static safety scan

During installation, the system scans your PHP files for forbidden patterns:

- Direct database connections (`new PDO`, `mysqli_*`)
- Direct role ID checks
- Hard-coded core table names

If you must use sensitive operations, the install is blocked unless a Super admin overrides the static scan. Cryptographic verification can never be overridden.

## manifest.xml fields (detailed)

The manifest is required at the ZIP root. Required fields:

- package_type: must be "extension".
- type: component, plugin, or module.
- name: human readable name.
- key: unique extension key, lowercase with a-z, 0-9, underscore, or dash.
- version: semantic version string.
- author: displayed in the extension list.
- description: short summary for admins.

Optional fields:

- sql/install and sql/uninstall: relative paths to SQL files.
- adminMenu: menu entries for the Admin sidebar.
- apiRoutes: API route definitions.
- signature: cryptographic signature (required for install).

## Admin menu declarations

Each admin menu item is declared inside adminMenu:

```
<adminMenu>
  <item key="example" parent="" label_key="COM_EXAMPLE.MENU" icon="fa-layer-group" route="/admin/ext/com_example/Dashboard/index" sort="1" perm_key="manage_example" />
  <item key="settings" parent="example" label_key="COM_EXAMPLE.SETTINGS" icon="fa-gear" route="/admin/ext/com_example/Settings/index" sort="2" perm_key="manage_example" />
</adminMenu>
```

- key: unique per extension.
- parent: optional parent item key.
- label_key: language key (required).
- icon: Font Awesome 6 class.
- route: admin route to your controller.
- perm_key: permission key for ACL checks.

## Packaging rules

- All files listed in the manifest must exist in the ZIP.
- All destinations must stay inside `/extension/{ext_key}/` or the extension media bucket.
- Use SQL scripts for schema changes only, never PHP install scripts.
- Never write to core tables or core files.
