# Installation Overview

Freeflow CMS is installed through the `/install` application. The installer is fully self-contained and does not bootstrap the main runtime.

## Prerequisites

- PHP 8.2 or higher
- MySQL database (already created)
- Web server configured to route `/` and `/admin` to the PHP entrypoints
- Write access to `/core/admin/config/` for the installer

## Installer flow

### Step 1: Requirements check

The installer verifies:

- PHP version
- Required extension
- File permissions
- Ability to write `/core/admin/config/`

If any check fails, installation cannot proceed.

### Step 2: Database configuration

Provide:

- Host (default: localhost)
- Port (default: 3306)
- Database name
- User
- Password
- Table prefix (default: `ff_`)

The installer tests the connection before continuing.

### Step 3: Site and Super user

Enter:

- Site name
- Optional tagline
- Site URL
- Super user name, username, email, and password

Passwords must meet the system password policy.

### Step 4: Database install

The installer:

1. Loads `core/admin/sql/install.sql` as plain text.
2. Replaces the `#__` prefix token.
3. Executes statements sequentially.
4. Seeds the Super user account in PHP.

If any SQL error occurs, installation stops and the configuration file is not written.

### Step 5: Configuration write

On success, the installer writes:

```
/core/admin/config/config.php
```

The file contains only the database configuration and install metadata.

### Step 6: Completion

The installer displays:

- "Initial setup complete."
- Links to the site and admin login
- A notice to remove or disable `/install/`

## Reinstall protection

The presence of `/core/admin/config/config.php` blocks the installer. If the file exists, `/install/` is disabled.
