<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Advanced_HTML_Sitemap_Block {

    /**
     * Block name.
     */
    private const BLOCK_NAME = 'advanced-html-sitemap/block';

    public function __construct() {
        add_action('init', [$this, 'register']);
    }

    /**
     * Register editor script + dynamic block.
     */
    public function register(): void {
        // Editor JS
        $js_rel  = 'assets/js/block.js';
        $js_path = AHS_DIR . ltrim($js_rel, '/');
        $js_ver  = file_exists($js_path) ? filemtime($js_path) : AHS_VERSION;

        wp_register_script(
            'advanced-html-sitemap-block',
            plugins_url($js_rel, AHS_FILE),
            ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'],
            $js_ver,
            true
        );

        register_block_type(self::BLOCK_NAME, [
            'api_version'     => 2,
            'editor_script'   => 'advanced-html-sitemap-block',
            'render_callback' => [$this, 'render'],
            'attributes'      => [
                'postTypes'      => ['type' => 'array',  'default' => ['page', 'post'], 'items' => ['type' => 'string']],
                'columns'        => ['type' => 'number', 'default' => 1],
                'exclude'        => ['type' => 'string', 'default' => ''],
                'showDates'      => ['type' => 'boolean','default' => false],
                'hierarchical'   => ['type' => 'boolean','default' => false],
                'index'          => ['type' => 'boolean','default' => false],
                'excludeNoindex' => ['type' => 'boolean','default' => true],
                'cache'          => ['type' => 'boolean','default' => true],
            ],
        ]);
    }

    /**
     * Dynamic render callback. Delegates to shortcode for a single source of truth.
     */
    public function render(array $attributes, string $content = ''): string {
        $post_types = !empty($attributes['postTypes'])
            ? implode(',', array_map('sanitize_key', (array) $attributes['postTypes']))
            : 'page,post';

        $columns = isset($attributes['columns'])
            ? (string) max(1, min(3, (int) $attributes['columns']))
            : '1';

        $exclude = isset($attributes['exclude']) ? sanitize_text_field((string) $attributes['exclude']) : '';

        $atts = [
            'post_types'      => $post_types,
            'columns'         => $columns,
            'exclude'         => $exclude,
            'show_dates'      => !empty($attributes['showDates']) ? 'true' : 'false',
            'hierarchical'    => !empty($attributes['hierarchical']) ? 'true' : 'false',
            'index'           => !empty($attributes['index']) ? 'true' : 'false',
            'exclude_noindex' => !empty($attributes['excludeNoindex']) ? 'true' : 'false',
            'cache'           => !empty($attributes['cache']) ? 'true' : 'false',
        ];

        $shortcode = '[' . Advanced_HTML_Sitemap::SHORTCODE . ' ';
        foreach ($atts as $k => $v) {
            $shortcode .= $k . '="' . esc_attr($v) . '" ';
        }
        $shortcode .= ']';

        return do_shortcode($shortcode);
    }
}
