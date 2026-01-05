=== Advanced HTML Sitemap ===
Contributors: pratikshrestha
Tags: html sitemap, shortcode, responsive
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A responsive and customizable HTML sitemap plugin for WordPress with GitHub-powered private updates.

== Description ==

Advanced HTML Sitemap is a lightweight plugin for displaying a responsive sitemap of your website’s pages, posts, and taxonomies. It’s built with developers in mind and includes filters, caching, and a customizable shortcode.

== Installation ==

1. Upload the ZIP via `Plugins > Add New > Upload Plugin` in your WordPress admin.
2. Activate the plugin.

== GitHub Auto Update Setup ==

This plugin supports automatic updates using GitHub. Follow these steps:

1. Generate a GitHub Personal Access Token at https://github.com/settings/tokens with `repo` scope.
2. Add this to your `wp-config.php`:
   ```php
   define('GITHUB_UPDATER_TOKEN', 'your_personal_access_token');
   ```

== Usage ==

Use the `[advanced_html_sitemap]` shortcode anywhere in posts or pages. Example:
```bash
[advanced_html_sitemap post_types="post,page"]
```

== Changelog ==

= 1.0.0 =
* Initial release with shortcode support, filters, GitHub-based auto-update.

== License ==

This plugin is proprietary and distributed privately.
