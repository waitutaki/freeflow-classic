# Security Considerations

Freeflow CMS includes built-in security controls. As a sysadmin, you must enforce safe operations at the server and role levels.

## Trusted signing key

- The trusted signing public key lives at `/core/keys/freeflow_public.pem`.
- The fingerprint is stored in the settings table.
- If the fingerprint mismatches, all installs and updates are blocked.

## Roles and permissions

- Super users have all permissions.
- Non-super users must be explicitly granted permissions.
- Never grant update or installer permissions unless absolutely required.

## File permissions

- Core and theme directories should be read-only for the runtime user.
- Only `/storage/` should be writable.
- `/core/admin/config/` must be writable only during installation.

## Logs

Security events, update actions, and admin actions are logged in `/storage/logs/`:

- `admin.log`
- `updates.log`
- `error.log`
- `auth.log`

Review logs regularly and rotate them as part of maintenance.

## Freegate

Freegate provides basic traffic and rule protections. Ensure it remains enabled and review the rules when unusual activity is detected.

## Admin access boundaries

Admin access is restricted to users with high roles. The system enforces this at login. Never reuse a standard user account for admin access.

## Update trust

- Updates are accepted only from the official HTTPS endpoints.
- Feeds and packages are signed.
- Signature failures are logged and block execution.

## Extension and theme safety

- Extension and theme installers reject unsafe path targets and core table access.
- Static scans block direct DB connections and role ID checks.
- Super overrides are recorded in admin logs.
