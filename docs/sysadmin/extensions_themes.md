# Managing extension and Themes

extension and themes add functionality and appearance. They are installed and managed from the Admin area.

## Installation rules

- Packages must be ZIP files with a valid manifest.
- The system verifies signatures before installing.
- Installers enforce safe target paths and table namespaces.

## Extension management

- Enable or disable extension from the extension list.
- Core extension cannot be disabled or removed.
- Uninstall removes extension files and owned tables only.

## Theme management

- Site themes control the front-end appearance.
- Admin themes control the Admin UI appearance.
- Only one site theme is active at a time.

## Safe removal

Before uninstalling:

1. Identify content that depends on the extension or theme.
2. Disable the extension or switch themes.
3. Run uninstall to remove code and assets.

## Devstore

The Devstore system is for development-only package signing and distribution. It should not be used as a public production marketplace unless your organization operates its own trusted signing process.
