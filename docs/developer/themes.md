# Theme Development

Themes control how the site renders content. A theme is installed from a ZIP package with a manifest and a defined structure.

## Theme layout

Themes live under `/themes/{theme_key}/` and can include:

- `assets/` (CSS, JS, images)
- `theme.xml` (manifest)
- `index.php` (template entry)

Example layout:

```
/themes/alpha/
  assets/
    css/
    js/
  theme.xml
  index.php
```

## Theme manifest

The theme manifest defines metadata and positions. Example:

```
<theme>
  <name>Alpha</name>
  <key>alpha</key>
  <version>1.0</version>
  <author>Acme</author>
  <description>Clean site theme</description>
  <context>site</context>
  <positions>
    <position key="topbar" />
    <position key="sidebar" />
    <position key="footer" />
  </positions>
</theme>
```

## Positions

Positions are slots where elements render. Examples include `topbar`, `sidebar`, and `footer`. When a theme is installed, the system syncs these positions into the database so that elements can be assigned.

## Template rendering

The theme `index.php` uses the template region system to render content. It receives variables such as:

- `page_title`
- `content`
- region arrays for elements

Themes must load Bootstrap and the core CSS, followed by theme CSS.

## Theme assets

All assets must be local and stored under the theme directory. External CDNs are not permitted.

## Theme positions and elements

Positions declared in `theme.xml` are the slots where elements render. Examples:

- topbar
- header_left
- sidebar
- footer

Elements are assigned to positions in the Admin area. If a position is removed from the manifest, existing element assignments will no longer render for that position.

## Admin vs site themes

- Site themes define public-facing templates.
- Admin themes define the Admin UI appearance.
- A theme is bound to a context (site or admin) in its manifest.

## Theme updates

Theme updates are distributed as ZIP packages and installed via the Updates system. Updates must not modify core paths or core tables.
