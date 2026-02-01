# Updates System

Freeflow CMS updates are manual and cryptographically verified. Only Super users can run updates.

## Update checks

1. Go to System -> Updates.
2. Click Check for updates.
3. The system contacts the official repository over HTTPS.
4. XML feeds are verified against the trusted public key.
5. Results are stored in the database.

## Update execution

- Updates are only available if a prior check succeeded.
- Each component (system, extension, theme) has its own update action.
- The Update All flow runs in this order:
  1. System
  2. extension
  3. Themes

## Signature verification

Signature verification is mandatory and cannot be overridden. If verification fails:

- No files are applied.
- No database changes are made.
- The error is logged.

## Update staging

Downloads are staged under:

```
/storage/updates/
```

Staging directories are removed by the Maintenance task after updates are complete.

## Logging

Update checks and execution results are logged to `updates.log`. Failures are also recorded in `error.log`.
