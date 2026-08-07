# Changelog

## [0.0.19] - 2026-08-07

### Changes
- Add theme-neutral default styling: section rules, spacing, index chips, and indented hierarchy, all derived from inherited type and currentColor so no theme palette or font is overridden.
- Raise specificity on list rules so themes that style content lists (e.g. `.page-content article ul`) no longer indent sitemap entries.
- Add `--ahs-rule`, `--ahs-column-gap`, `--ahs-row-gap`, `--ahs-item-gap`, and `--ahs-heading-size` custom properties for overriding spacing without editing plugin CSS.

## [0.0.18] - 2026-08-07

### Changes
- Enqueue the front-end stylesheet before the cache check. A cached sitemap returned early and never enqueued its CSS, so every visit after the first was unstyled and the column layout had no effect.

## [0.0.17] - 2026-08-07

### Changes
- Add a `taxonomies` option that lists term archives as their own sitemap sections, with `exclude_terms`, `hide_empty_terms`, and `show_counts` controls.
- Add taxonomy selection to the shortcode generator and the block inspector.
- Fix the front-end stylesheet being registered with a filesystem path, so no plugin CSS ever loaded.
- Restore the column layout styles, which were commented out.
- Clear the sitemap cache when taxonomy terms change.

## [0.0.16] - 2026-04-30

### Changes
- Remove duplicate View details link from the Plugins screen.

## [0.0.15] - 2026-04-30

### Changes
- Update WordPress compatibility metadata for plugin details.

## [0.0.14] - 2026-04-30

### Changes
- Refresh GitHub version metadata during WordPress update checks.

## [0.0.13] - 2026-04-30

### Changes
- Add plugin list Settings and View details action links.

## [0.0.12] - 2026-04-30

### Changes
- Prevent stale WordPress update notices when the installed version already matches GitHub.

## [0.0.11] - 2026-04-30

### Changes
- Add author URI and bump plugin version for GitHub update testing.

## [0.0.1] - 2026-04-30

### Changes
- Initial deployment workflow.
