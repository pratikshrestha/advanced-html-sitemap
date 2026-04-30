=== Advanced HTML Sitemap ===
Contributors: pratikshrestha
Tags: html sitemap, sitemap, seo, navigation
Requires at least: 5.8
Tested up to: 6.9.4
Requires PHP: 7.2
Stable tag: 0.0.15
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate fast, flexible, and SEO-aware HTML sitemaps with full control over post types, hierarchy, exclusions, and noindex support.

== Description ==

Advanced HTML Sitemap is a lightweight yet powerful plugin for generating clean, human-friendly HTML sitemaps that respect your SEO settings.

Designed for site owners, agencies, and SEO professionals, the plugin ensures your HTML sitemap only displays indexable, relevant content — without bloated settings or unnecessary overhead.

It works seamlessly alongside popular SEO plugins and automatically excludes content marked as noindex.

== Features ==

= Flexible Sitemap Generation =
* Generate HTML sitemaps using a simple shortcode
* Include pages, posts, and custom post types
* Choose 1, 2, or 3 column layouts
* Optional hierarchical (parent → child) display
* Alphabetical ordering by title

= Smart Exclusions & Filtering =
* Exclude specific posts or pages by ID
* Automatically exclude unpublished or non-public content
* Optional taxonomy and term filtering
* Built-in caching for better performance

= SEO Plugin–Aware Noindex Support =
Automatically excludes content marked as noindex by popular SEO plugins:

* Yoast SEO
* Rank Math
* All in One SEO (AIOSEO)
* Slim SEO
* SEOPress
* The SEO Framework
* Custom robots meta containing "noindex"

This keeps your HTML sitemap aligned with your SEO strategy and avoids exposing URLs you don’t want indexed.

= Performance Optimized =
* Efficient queries (no unnecessary data loading)
* Transient-based caching
* Automatic cache clearing when content changes
* No external requests or tracking

= Developer Friendly =
* Clean, object-oriented architecture
* Extendable via well-prefixed WordPress hooks
* Safe HTML output using allow-listed tags
* Easy to extend for Pro or custom integrations

= GitHub Updates =
Checks the GitHub main branch for a newer plugin version and shows the update in the WordPress dashboard when the remote version is higher than the installed version.
Private repositories can be checked by defining AHS_GITHUB_TOKEN in wp-config.php with repository contents read access.

= Admin Shortcode Generator =
* Built-in admin page to generate shortcodes
* No complex settings pages
* Copy-and-paste ready output

== Installation ==

1. Upload the `advanced-html-sitemap` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the “Plugins” menu in WordPress
3. Add the shortcode to any page where you want the sitemap to appear

== Usage ==

Basic example:
[advanced_html_sitemap]


Advanced example:
[advanced_html_sitemap
post_types="page,post"
columns="2"
hierarchical="true"
exclude_noindex="true"
]


== Frequently Asked Questions ==

= Does this replace XML sitemaps? =
No. This plugin generates a human-readable HTML sitemap. XML sitemaps should still be handled by your SEO plugin.

= Does it work with SEO plugins? =
Yes. It detects and respects noindex rules from major SEO plugins such as Yoast, Rank Math, AIOSEO, Slim SEO, SEOPress, and The SEO Framework.

= Will it slow down my site? =
No. The plugin is optimized for performance and uses caching to minimize database queries.

= Can I exclude specific pages? =
Yes. You can exclude pages or posts by ID using the shortcode.

= Is this plugin GDPR compliant? =
Yes. The plugin does not collect data, make external requests, or use cookies.

== Changelog ==

= 0.0.15 =
* Initial public release
* HTML sitemap shortcode
* Hierarchical display support
* SEO plugin noindex detection
* Performance optimizations and caching
* Admin shortcode generator

== Upgrade Notice ==

= 0.0.15 =
Initial release.

== Privacy ==

Advanced HTML Sitemap does not collect, store, or transmit any personal data and includes no tracking or analytics. In the WordPress admin, it can request plugin version metadata from GitHub to support dashboard updates.
