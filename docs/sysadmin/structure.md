# Directory Structure

Understanding the filesystem layout helps you manage permissions and backups safely.

## Core directories

```
/core/
  controllers/
  models/
  views/
  assets/
  languages/
  admin/
```

- Core is read-only at runtime.
- Updates are the only approved way to modify core files.

## extension and themes

```
/extension/
/themes/
```

- extension are isolated by key.
- Themes are isolated by key and context (site/admin).

## Storage

```
/storage/
  backups/
  logs/
  media/
  tmp/
  updates/
  devstore/
```

Only the `storage/` tree is writable at runtime. All user uploads, logs, and staging work are stored here.

## Media buckets

```
/storage/media/system/
/storage/media/users/{user_id}/
/storage/media/extension/{ext_key}/
/storage/media/themes/{theme_key}/
```

Each bucket has a defined purpose. Do not mix content between buckets.

## Docs

```
/docs/
/docs-html/
```

- `/docs/` is source only and must not be publicly accessible.
- `/docs-html/` is generated output and can be served publicly, with sysadmin docs restricted to Super users.
