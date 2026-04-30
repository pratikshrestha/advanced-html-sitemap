<?php
/**
 * Plugin Name:       Advanced HTML Sitemap
 * Description:       Generate an HTML sitemap with customizable post types, taxonomies, columns, and more.
 * Version:           0.0.14
 * Author:            Pratik Shrestha
 * Author URI:        https://pratik-shrestha.com.np
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/pratikshrestha/advanced-html-sitemap
 * Text Domain:       advanced-html-sitemap
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AHS_VERSION', '0.0.14');
define('AHS_FILE', __FILE__);
define('AHS_DIR', plugin_dir_path(__FILE__));
define('AHS_URL', plugin_dir_url(__FILE__));
define('AHS_GITHUB_OWNER', 'pratikshrestha');
define('AHS_GITHUB_REPO', 'advanced-html-sitemap');
define('AHS_GITHUB_BRANCH', 'main');

require_once AHS_DIR . 'includes/class-advanced-html-sitemap.php';
require_once AHS_DIR . 'includes/class-advanced-html-sitemap-github-updater.php';

add_action('plugins_loaded', static function () {
    Advanced_HTML_Sitemap::instance();
    Advanced_HTML_Sitemap_GitHub_Updater::instance();
});

require_once AHS_DIR . 'includes/class-advanced-html-sitemap-block.php';
new Advanced_HTML_Sitemap_Block();
