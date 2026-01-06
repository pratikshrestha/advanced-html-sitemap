<?php
/**
 * Plugin Name:       Advanced HTML Sitemap
 * Description:       Generate an HTML sitemap with customizable post types, taxonomies, columns, and more.
 * Version:           1.0.0
 * Author:            Pratik Shrestha
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       advanced-html-sitemap
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AHS_VERSION', '1.0.0');
define('AHS_FILE', __FILE__);
define('AHS_DIR', plugin_dir_path(__FILE__));
define('AHS_URL', plugin_dir_url(__FILE__));

require_once AHS_DIR . 'includes/class-advanced-html-sitemap.php';

add_action('plugins_loaded', static function () {
    Advanced_HTML_Sitemap::instance();
});

require_once AHS_DIR . 'includes/class-advanced-html-sitemap-block.php';
new Advanced_HTML_Sitemap_Block();
