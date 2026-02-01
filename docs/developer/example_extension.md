# Example Extension

This section walks through a complete example of a minimal component extension named `com_example`.

## Folder structure

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
  manifest.xml
```

## manifest.xml

```
<manifest>
  <package_type>extension</package_type>
  <type>component</type>
  <name>Example Component</name>
  <key>com_example</key>
  <version>1.0</version>
  <author>Example Author</author>
  <description>Sample extension used for documentation.</description>
  <files>
    <file source="Controllers/Admin/DashboardController.php" destination="/extension/com_example/Controllers/Admin/DashboardController.php" />
    <file source="Controllers/Api/StatusController.php" destination="/extension/com_example/Controllers/Api/StatusController.php" />
    <file source="Models/ItemsModel.php" destination="/extension/com_example/Models/ItemsModel.php" />
    <file source="Views/admin/dashboard.php" destination="/extension/com_example/Views/admin/dashboard.php" />
    <file source="Languages/en-GB.ini" destination="/extension/com_example/Languages/en-GB.ini" />
    <file source="manifest.xml" destination="/extension/com_example/manifest.xml" />
  </files>
  <sql>
    <install>sql/install.sql</install>
    <uninstall>sql/uninstall.sql</uninstall>
  </sql>
  <adminMenu>
    <item key="example" label_key="COM_EXAMPLE.MENU" icon="fa-layer-group" route="/admin/ext/com_example/Dashboard/index" sort="1" />
  </adminMenu>
  <apiRoutes>
    <route path="status" method="GET" controller="StatusController" action="index" public="1" />
  </apiRoutes>
  <signature algorithm="sha256">ZXhhbXBsZV9zaWduYXR1cmU=</signature>
</manifest>
```

## Controller example

```
<?php
namespace extension\ComExample\Controllers\Admin;

use Core\Controller;
use Core\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return $this->render('admin/dashboard', ['page_title' => 'Example'], 'Zulu');
    }
}
```

## Language file example

```
COM_EXAMPLE.MENU="Example"
COM_EXAMPLE.DASHBOARD_TITLE="Example Dashboard"
```

## SQL namespace example

```
CREATE TABLE IF NOT EXISTS `#__ext_com_example_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`)
);
```

## Install summary

- Package is uploaded in Admin -> extension.
- The manifest is verified and files are installed to the extension folder.
- Tables are created under the `#__ext_com_example_` namespace.
- Admin menu entries are registered automatically.
