<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once AHS_DIR . 'includes/class-advanced-html-sitemap-renderer.php';
require_once AHS_DIR . 'includes/class-advanced-html-sitemap-admin.php';

final class Advanced_HTML_Sitemap {

    const SHORTCODE  = 'advanced_html_sitemap';
    const CACHE_KEYS_OPT = 'ahs_cache_keys';

    private static $instance = null;

    /** @var Advanced_HTML_Sitemap_Renderer */
    public $renderer;

    /** @var Advanced_HTML_Sitemap_Admin */
    public $admin;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->renderer = new Advanced_HTML_Sitemap_Renderer(self::CACHE_KEYS_OPT);
        $this->admin    = new Advanced_HTML_Sitemap_Admin();

        add_shortcode(self::SHORTCODE, [$this->renderer, 'shortcode']);

        // Flush cache on content changes
        add_action('save_post', [$this->renderer, 'maybe_flush_cache'], 10, 2);
        add_action('deleted_post', [$this->renderer, 'flush_all_cache']);
        add_action('trashed_post', [$this->renderer, 'flush_all_cache']);
    }
}
