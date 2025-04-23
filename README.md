
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
- Private GitHub-based update support

---

## 🚀 Installation

### Option 1: From ZIP File
1. Download the latest plugin ZIP from GitHub [Releases](https://github.com/outpaceseo/advanced-html-sitemap/releases).
2. In your WordPress dashboard, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file and activate the plugin.

## 🔧 Shortcode Usage

```bash
[advanced_html_sitemap post_types="post,page" taxonomies="category,post_tag" include_post_ids="1,2,3"]
```

Check the plugin documentation for all supported shortcode attributes.

---

## 🧑‍💻 Developer Notes

- Add your own filters or hooks to extend output
- Supports action hooks for wrapper markup
- Easily disable sections or override query logic via filters

---

## 📄 License

This plugin is privately licensed and not intended for distribution via WordPress.org.
