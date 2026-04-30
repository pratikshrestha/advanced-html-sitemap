
# Advanced HTML Sitemap

A customizable and responsive HTML sitemap plugin for WordPress, built with performance and flexibility in mind.

---

## 🧩 Features

- Responsive HTML sitemap
- Customize which post types and taxonomies to include
- Include/exclude specific post or term IDs
- Limit post count per taxonomy or post type
- Show hierarchy for taxonomies
- Include post publish dates
- Add custom CSS classes and IDs
- Includes shortcode `[advanced_html_sitemap]` with many options
- Developer-friendly with filters, hooks, and optional caching
- GitHub-based update support

---

## 🔄 GitHub Updates

This plugin checks the `main` branch on GitHub for the version in `advanced-html-sitemap.php`.
When the version on GitHub is higher than the installed version, WordPress will show a plugin update in the dashboard.

Default update source:

```php
https://github.com/pratikshrestha/advanced-html-sitemap
```

The update check is cached for one hour. You can force WordPress to check again from `Dashboard > Updates`.

## 🚀 Installation

### Option 1: From ZIP File
1. Download the latest plugin ZIP from GitHub [Releases](https://github.com/pratikshrestha/advanced-html-sitemap/releases).
2. In your WordPress dashboard, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file and activate the plugin.

## 🔧 Shortcode Usage

```bash
[advanced_html_sitemap post_types="post,page" taxonomies="category,post_tag" include_post_ids="1,2,3"]
```

Check the plugin documentation for all supported shortcode attributes.

## 🧑‍💻 Developer Notes

- Add your own filters or hooks to extend output
- Supports action hooks for wrapper markup
- Easily disable sections or override query logic via filters

---

## 📄 License

Advanced HTML Sitemap is free software licensed under the GPL v2 or later.

See [LICENSE](LICENSE) for details.
