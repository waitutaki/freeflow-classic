# Extension Management Files in Freeflow CMS

This document lists all files involved in extension management, installation, and updating in the Freeflow CMS project.

## Core Files

### Extension Management and Installation
- [`core/UpdateService.php`](core/UpdateService.php): Handles fetching feeds, downloading packages, and managing updates for extension and themes.
- [`core/PackageInstaller.php`](core/PackageInstaller.php): Manages the installation, uninstallation, and validation of packages.
- [`core/ExtensionRouter.php`](core/ExtensionRouter.php): Routes requests to extension-specific controllers.
- [`core/ExtensionRuntime.php`](core/ExtensionRuntime.php): Manages the runtime environment for extension.

### Models
- [`core/models/extensionModel.php`](core/models/extensionModel.php): Manages extension data in the database.
- [`core/models/ThemesModel.php`](core/models/ThemesModel.php): Manages theme data in the database.
- [`core/models/UpdateChecksModel.php`](core/models/UpdateChecksModel.php): Tracks update status and checks for extension and themes.

### Controllers
- [`core/controllers/admin/AdminextensionController.php`](core/controllers/admin/AdminextensionController.php): Handles admin-side extension management, including installation, uninstallation, and updates.
- [`core/controllers/api/DevstoreFeedsApiController.php`](core/controllers/api/DevstoreFeedsApiController.php): Manages API endpoints for fetching DevStore feeds.

### Views
- [`core/views/admin/extension.php`](core/views/admin/extension.php): Displays the extension management interface, including the "Install from Web" tab.
- [`core/views/admin/extension_install.php`](core/views/admin/extension_install.php): Provides the form for installing extension from uploaded packages.
- [`core/views/admin/extension_uninstall.php`](core/views/admin/extension_uninstall.php): Confirms uninstallation of extension.
- [`core/views/admin/updates.php`](core/views/admin/updates.php): Displays available updates for extension and themes.

### Additional Supporting Files
- [`core/Trust.php`](core/Trust.php): Manages trust and security checks for extension.
- [`core/Logger.php`](core/Logger.php): Logs events related to extension management and updates.
- [`core/Session.php`](core/Session.php): Manages session data, including user permissions for extension management.
- [`core/Auth.php`](core/Auth.php): Handles authentication and authorization for extension management actions.
- [`core/Csrf.php`](core/Csrf.php): Provides CSRF protection for extension management forms.

## Summary

These files collectively manage the entire lifecycle of extension and themes in the Freeflow CMS, including installation, updates, and removal. The key files for resolving the "Feed fetch failed" issue were [`core/UpdateService.php`](core/UpdateService.php) and [`core/controllers/admin/AdminextensionController.php`](core/controllers/admin/AdminextensionController.php), where the endpoints for fetching package feeds were corrected.