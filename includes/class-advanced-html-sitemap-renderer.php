<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Advanced_HTML_Sitemap_Renderer
{

    /**
     * Option name that stores transient cache keys created by this plugin.
     *
     * @var string
     */
    private $cache_keys_option;

    public function __construct(string $cache_keys_option)
    {
        $this->cache_keys_option = $cache_keys_option;

        // Register front-end assets early (prints in wp_head correctly)
        add_action('wp_enqueue_scripts', [$this, 'register_public_assets']);
    }

    public function shortcode($atts): string
    {
        $atts = shortcode_atts([
            'post_types'      => 'page,post',
            'columns'         => '1',
            'taxonomy'        => '',
            'term'            => '',
            'limit'           => -1,
            'exclude'         => '',
            'show_dates'      => 'false',
            'hierarchical'    => 'false',
            'disable_css'     => 'false',
            'class'           => '',
            'id'              => '',
            'index'           => 'false',
            'exclude_noindex' => 'true',
            'template'        => 'default',
            'preset'          => '',
            'group_by'        => '',
            'cache' => 'true',
        ], (array) $atts, Advanced_HTML_Sitemap::SHORTCODE);


        /**
         * WP.org hook naming: prefix with plugin slug.
         * Keep old hook for backwards compatibility (deprecated).
         */
        $atts = apply_filters('ahs_resolve_preset_atts', $atts, (string) $atts['preset']);
        $atts = apply_filters('advanced_html_sitemap_resolve_preset_atts', $atts, (string) $atts['preset']);


        $use_cache = filter_var($atts['cache'], FILTER_VALIDATE_BOOLEAN);

        // Disable cache in dev environments
        if (
            (defined('WP_DEBUG') && WP_DEBUG) ||
            (defined('AHS_DISABLE_CACHE') && AHS_DISABLE_CACHE)
        ) {
            $use_cache = false;
        }

        // Sanitize/normalize attrs
        $columns = in_array((string) $atts['columns'], ['1', '2', '3'], true) ? (int) $atts['columns'] : 1;

        $post_types = array_values(array_filter(array_map('sanitize_key', array_map('trim', explode(',', (string) $atts['post_types'])))));
        if (empty($post_types)) {
            $post_types = ['page', 'post'];
        }

        $exclude_ids = array_map('intval', array_filter(explode(',', (string) $atts['exclude'])));
        $limit       = (int) $atts['limit'];

        $show_dates      = filter_var($atts['show_dates'], FILTER_VALIDATE_BOOLEAN);
        $hierarchical    = filter_var($atts['hierarchical'], FILTER_VALIDATE_BOOLEAN);
        $show_index      = filter_var($atts['index'], FILTER_VALIDATE_BOOLEAN);
        $exclude_noindex = filter_var($atts['exclude_noindex'], FILTER_VALIDATE_BOOLEAN);
        $disable_css     = filter_var($atts['disable_css'], FILTER_VALIDATE_BOOLEAN);

        $taxonomy = $atts['taxonomy'] !== '' ? sanitize_key($atts['taxonomy']) : '';
        $term     = $atts['term'] !== '' ? sanitize_title($atts['term']) : '';

        // Cache
        $cache_key = $this->cache_key($atts);

        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return (string) wp_kses((string) $cached, $this->allowed_html());
            }
        }

        // Enqueue stylesheet (must be registered early on wp_enqueue_scripts in main plugin)
        if (!$disable_css) {
            wp_enqueue_style('advanced-html-sitemap-public');
        }

        ob_start();

        $wrapper_classes = trim('advanced-html-sitemap columns-' . $columns . ' ' . sanitize_html_class((string) $atts['class']));
        echo '<div class="' . esc_attr($wrapper_classes) . '" id="' . esc_attr(sanitize_key((string) $atts['id'])) . '">';

        if ($show_index) {
            echo '<ul class="sitemap-index">';
            foreach ($post_types as $post_type) {
                $obj = get_post_type_object($post_type);
                if ($obj) {
                    echo '<li><a href="#sitemap-' . esc_attr($post_type) . '">' . esc_html($obj->labels->name) . '</a></li>';
                }
            }
            echo '</ul>';
        }

        foreach ($post_types as $post_type) {

            $args = [
                'post_type'              => $post_type,
                'posts_per_page'         => $limit,
                'post_status'            => 'publish',
                'orderby'                => 'title',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                'fields'                 => 'ids',
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ];

            // Only add exclusion when needed (reduces sniffs and avoids NOT IN () patterns)
            if (!empty($exclude_ids)) {
                $args['post__not_in'] = $exclude_ids;
            }

            // Only add tax query when requested
            if ($taxonomy && $term) {
                $args['tax_query'] = [[
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term,
                ]];
            }

            $args = apply_filters('ahs_query_args', $args, $post_type, $atts);
            $args = apply_filters('advanced_html_sitemap_query_args', $args, $post_type, $atts);

            $query = new WP_Query($args);

            if (!empty($query->posts)) {
                $obj   = get_post_type_object($post_type);
                $label = $obj ? $obj->labels->name : ucfirst($post_type);

                echo '<div class="sitemap-section" id="sitemap-' . esc_attr($post_type) . '">';
                echo '<h3>' . esc_html($label) . '</h3>';

                do_action('ahs_before_section', $post_type, $atts);
                do_action('advanced_html_sitemap_before_section', $post_type, $atts);

                echo '<ul>';

                if ($hierarchical && is_post_type_hierarchical($post_type)) {

                    $page_args = [
                        'post_type'    => $post_type,
                        'sort_column'  => 'post_title',
                        'hierarchical' => 1,
                    ];

                    // Add exclude only when needed (VIP sniff mitigation)
                    if (!empty($exclude_ids)) {
                        $page_args['exclude'] = $exclude_ids;
                    }

                    $pages = get_pages($page_args);

                    if ($exclude_noindex) {
                        $pages = array_filter($pages, [$this, 'is_indexable']);
                    }

                    $tree_html = $this->build_tree($pages, 0, $show_dates, $exclude_noindex, true);
                    echo wp_kses($tree_html, $this->allowed_html());
                } else {

                    $posts = [];
                    foreach ($query->posts as $post_id) {
                        if ($exclude_noindex && !$this->is_indexable($post_id)) {
                            continue;
                        }

                        $title = get_the_title($post_id);
                        if ($title === '') {
                            $title = __('(no title)', 'advanced-html-sitemap');
                        }

                        $posts[$title] = (int) $post_id;
                    }

                    ksort($posts, SORT_NATURAL | SORT_FLAG_CASE);

                    foreach ($posts as $title => $post_id) {
                        $item_html  = '<li><a href="' . esc_url(get_permalink($post_id)) . '">' . esc_html($title);
                        if ($show_dates) {
                            $item_html .= ' <small>(' . esc_html(get_the_date('', $post_id)) . ')</small>';
                        }
                        $item_html .= '</a></li>';

                        $item_html = apply_filters('ahs_item_html', $item_html, $post_id, $post_type, $atts);
                        $item_html = apply_filters('advanced_html_sitemap_item_html', $item_html, $post_id, $post_type, $atts);

                        echo wp_kses($item_html, $this->allowed_html());
                    }
                }

                echo '</ul>';

                do_action('ahs_after_section', $post_type, $atts);
                do_action('advanced_html_sitemap_after_section', $post_type, $atts);

                echo '</div>';
            }

            wp_reset_postdata();
        }

        echo '</div>';

        $output = (string) ob_get_clean();

        if ($use_cache) {
            set_transient($cache_key, $output, HOUR_IN_SECONDS);
            $this->track_cache_key($cache_key);
        }

        $output = apply_filters('advanced_html_sitemap_output', $output);
        return (string) wp_kses($output, $this->allowed_html());
    }

    /**
     * Allowed HTML + filters (prefixed).
     */
    private function allowed_html(): array
    {
        $allowed = [
            'div'   => ['class' => true, 'id' => true],
            'ul'    => ['class' => true],
            'li'    => ['class' => true],
            'a'     => ['href' => true, 'title' => true, 'class' => true],
            'h3'    => ['class' => true],
            'small' => [],
        ];

        $allowed = apply_filters('ahs_allowed_html', $allowed);
        return apply_filters('advanced_html_sitemap_allowed_html', $allowed);
    }

    /**
     * Determines if a post is indexable. true = include, false = exclude.
     */
    public function is_indexable($post_id): bool
    {

        if (is_object($post_id) && isset($post_id->ID)) {
            $post_id = (int) $post_id->ID;
        } else {
            $post_id = (int) $post_id;
        }

        if ($post_id <= 0) {
            return true;
        }

        // If post is not published, don't show (extra safety)
        $status = get_post_status($post_id);
        if ($status && $status !== 'publish') {
            return false;
        }

        // Allow developers to short-circuit
        $pre = apply_filters('advanced_html_sitemap_pre_is_indexable', null, $post_id);
        if ($pre !== null) {
            return (bool) $pre;
        }

        // Yoast SEO
        if (get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true) === '1') {
            return false;
        }

        // Rank Math (string or array)
        $rm = get_post_meta($post_id, 'rank_math_robots', true);
        if ($rm === 'noindex') return false;
        if (is_array($rm) && in_array('noindex', $rm, true)) return false;

        // All in One SEO
        if (get_post_meta($post_id, '_aioseo_robots_noindex', true) === '1') {
            return false;
        }

        // Slim SEO (common storage: `_slim_seo` array)
        $slim = get_post_meta($post_id, '_slim_seo', true);
        if (is_array($slim)) {
            $robots = '';

            if (isset($slim['robots']) && is_string($slim['robots'])) {
                $robots = $slim['robots'];
            } elseif (isset($slim['meta_robots']) && is_string($slim['meta_robots'])) {
                $robots = $slim['meta_robots'];
            } elseif (isset($slim['noindex'])) {
                if ($slim['noindex'] === '1' || $slim['noindex'] === 1 || $slim['noindex'] === true) {
                    return false;
                }
            }

            if ($robots !== '' && stripos($robots, 'noindex') !== false) {
                return false;
            }

            if (isset($slim['robots']) && is_array($slim['robots']) && in_array('noindex', $slim['robots'], true)) {
                return false;
            }
        }

        // SEOPress
        $seopress_index = get_post_meta($post_id, '_seopress_robots_index', true);
        if ($seopress_index === 'no' || $seopress_index === '0') {
            return false;
        }
        $seopress_robots = get_post_meta($post_id, '_seopress_robots', true);
        if (is_string($seopress_robots) && stripos($seopress_robots, 'noindex') !== false) {
            return false;
        }
        if (is_array($seopress_robots) && in_array('noindex', $seopress_robots, true)) {
            return false;
        }

        // The SEO Framework
        $tsf_robots = get_post_meta($post_id, '_the_seo_framework_robots', true);
        if (is_string($tsf_robots) && stripos($tsf_robots, 'noindex') !== false) {
            return false;
        }
        if (is_array($tsf_robots) && in_array('noindex', $tsf_robots, true)) {
            return false;
        }

        // Generic custom robots field fallback
        $custom_robots = get_post_meta($post_id, 'robots', true);
        if (is_string($custom_robots) && stripos($custom_robots, 'noindex') !== false) {
            return false;
        }

        return (bool) apply_filters('advanced_html_sitemap_is_indexable', true, $post_id);
    }

    /**
     * Build hierarchical sitemap.
     * If $top_level=true, we output only <li>...</li> (outer <ul> is already printed).
     */
    private function build_tree(array $pages, int $parent_id = 0, bool $show_dates = false, bool $exclude_noindex = true, bool $top_level = false): string
    {

        $children = array_filter($pages, static function ($p) use ($parent_id) {
            return (int) $p->post_parent === (int) $parent_id;
        });

        if (empty($children)) {
            return '';
        }

        usort($children, static fn($a, $b) => strcasecmp($a->post_title, $b->post_title));

        $out = '';
        if (!$top_level) {
            $out .= '<ul>';
        }

        foreach ($children as $child) {
            if ($exclude_noindex && !$this->is_indexable($child)) {
                continue;
            }

            $out .= '<li><a href="' . esc_url(get_permalink($child->ID)) . '">' . esc_html($child->post_title);
            if ($show_dates) {
                $out .= ' <small>(' . esc_html(get_the_date('', $child->ID)) . ')</small>';
            }
            $out .= '</a>';

            $out .= $this->build_tree($pages, (int) $child->ID, $show_dates, $exclude_noindex, false);
            $out .= '</li>';
        }

        if (!$top_level) {
            $out .= '</ul>';
        }

        return $out;
    }

    /**
     * Register the front-end stylesheet.
     */
    public function register_public_assets(): void
    {
        $handle  = 'advanced-html-sitemap-public';
        $css_rel = 'assets/public.css';

        $css_path = AHS_DIR . $css_rel;
        $css_ver  = file_exists($css_path) ? filemtime($css_path) : AHS_VERSION;

        wp_register_style($handle, $css_path, [], $css_ver);
    }

    private function cache_key(array $atts): string
    {
        return 'ahs_' . md5(wp_json_encode($atts));
    }

    private function track_cache_key(string $key): void
    {
        $keys = get_option($this->cache_keys_option, []);
        if (!is_array($keys)) {
            $keys = [];
        }
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            update_option($this->cache_keys_option, $keys, false);
        }
    }

    public function maybe_flush_cache(int $post_id, $post): void
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        $this->flush_all_cache();
    }

    public function flush_all_cache(): void
    {
        $keys = get_option($this->cache_keys_option, []);
        if (!is_array($keys) || empty($keys)) {
            return;
        }

        foreach ($keys as $key) {
            delete_transient($key);
        }

        update_option($this->cache_keys_option, [], false);
    }
}
