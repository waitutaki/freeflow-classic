# Backup, Maintenance, and Recovery

Regular backups and maintenance keep the system stable and recoverable.

## Backup strategy

Back up both database and filesystem:

- Database: export all tables with the configured prefix.
- Filesystem: include `/core/`, `/themes/`, `/extension/`, and `/storage/`.

Store backups outside the web root and verify them periodically.

## Maintenance tasks

Maintenance actions are manual and Super-only:

- Temporary file cleanup
- Update staging cleanup
- Log truncation
- Orphaned thumbnail cleanup
- Orphaned media reference report

Run these tasks during low-traffic windows.

## Recovery basics

1. Restore the database from backup.
2. Restore the filesystem to the same point in time.
3. Verify configuration in `/core/admin/config/config.php`.
4. Check logs for errors after recovery.
