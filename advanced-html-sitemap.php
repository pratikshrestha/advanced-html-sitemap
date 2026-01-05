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

if (!defined('ABSPATH')) exit;

final class Advanced_HTML_Sitemap {
    const VERSION         = '1.0.0';
    const SHORTCODE       = 'html_sitemap';
    const OPTION_PAGE     = 'html-sitemap-generator';
    const CACHE_KEYS_OPT  = 'ahs_cache_keys'; // store transient keys we create

    public function __construct()
    {
        add_shortcode(self::SHORTCODE, [$this, 'shortcode']);

        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);

        // Flush cache on content changes
        add_action('save_post', [$this, 'maybe_flush_cache'], 10, 2);
        add_action('deleted_post', [$this, 'flush_all_cache']);
        add_action('trashed_post', [$this, 'flush_all_cache']);
    }

    public function shortcode($atts): string
    {
        $atts = shortcode_atts([
            'post_types'       => 'page,post',
            'columns'          => '1',
            'taxonomy'         => '',
            'term'             => '',
            'limit'            => -1,
            'exclude'          => '',
            'show_dates'       => 'false',
            'hierarchical'     => 'false',
            'class'            => '',
            'id'               => '',
            'index'            => 'true',
            'exclude_noindex'  => 'true',
        ], (array) $atts, self::SHORTCODE);

        // Sanitize/normalize attrs
        $columns = in_array((string) $atts['columns'], ['1', '2', '3'], true) ? (int) $atts['columns'] : 1;

        $post_types = array_values(array_filter(array_map('sanitize_key', array_map('trim', explode(',', (string)$atts['post_types'])))));
        if (empty($post_types)) $post_types = ['page', 'post'];

        $exclude_ids = array_map('intval', array_filter(explode(',', (string) $atts['exclude'])));
        $limit = (int) $atts['limit'];

        $show_dates      = filter_var($atts['show_dates'], FILTER_VALIDATE_BOOLEAN);
        $hierarchical    = filter_var($atts['hierarchical'], FILTER_VALIDATE_BOOLEAN);
        $show_index      = filter_var($atts['index'], FILTER_VALIDATE_BOOLEAN);
        $exclude_noindex = filter_var($atts['exclude_noindex'], FILTER_VALIDATE_BOOLEAN);

        $taxonomy = $atts['taxonomy'] !== '' ? sanitize_key($atts['taxonomy']) : '';
        $term     = $atts['term'] !== '' ? sanitize_title($atts['term']) : '';

        // Cache
        $cache_key = $this->cache_key($atts);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            // Ensure any HTML is still within allowed tags
            return (string) wp_kses((string)$cached, $this->allowed_html());
        }

        // Ensure styles load only when shortcode renders
        $this->enqueue_front_styles();

        ob_start();

        $wrapper_classes = trim('html-sitemap columns-' . $columns . ' ' . sanitize_html_class($atts['class']));
        echo '<div class="' . esc_attr($wrapper_classes) . '" id="' . esc_attr(sanitize_key($atts['id'])) . '">';

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
                'post_type'      => $post_type,
                'posts_per_page' => $limit,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
                'post__not_in'   => $exclude_ids,
                'no_found_rows'  => true,
                'fields'         => 'ids',
            ];

            if ($taxonomy && $term) {
                $args['tax_query'] = [[
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term,
                ]];
            }

            $query = new WP_Query($args);

            if (!empty($query->posts)) {
                $obj = get_post_type_object($post_type);
                $label = $obj ? $obj->labels->name : ucfirst($post_type);

                echo '<div class="sitemap-section" id="sitemap-' . esc_attr($post_type) . '">';
                echo '<h3>' . esc_html($label) . '</h3>';
                echo '<ul>';

                if ($hierarchical && is_post_type_hierarchical($post_type)) {
                    $pages = get_pages([
                        'post_type'    => $post_type,
                        'exclude'      => $exclude_ids,
                        'sort_column'  => 'post_title',
                        'hierarchical' => 1,
                    ]);

                    if ($exclude_noindex) {
                        $pages = array_filter($pages, [$this, 'is_indexable']);
                    }

                    $tree_html = $this->build_tree($pages, 0, $show_dates, $exclude_noindex, true);

                    // ✅ Correct escaping for HTML output:
                    echo wp_kses($tree_html, $this->allowed_html());
                } else {
                    $posts = [];

                    foreach ($query->posts as $post_id) {
                        if ($exclude_noindex && !$this->is_indexable($post_id)) continue;

                        $title = get_the_title($post_id);
                        if ($title === '') $title = __('(no title)', 'advanced-html-sitemap');

                        $posts[$title] = (int) $post_id;
                    }

                    ksort($posts, SORT_NATURAL | SORT_FLAG_CASE);

                    foreach ($posts as $title => $post_id) {
                        echo '<li><a href="' . esc_url(get_permalink($post_id)) . '">' . esc_html($title);
                        if ($show_dates) {
                            echo ' <small>(' . esc_html(get_the_date('', $post_id)) . ')</small>';
                        }
                        echo '</a></li>';
                    }
                }

                echo '</ul>';
                echo '</div>';
            }

            wp_reset_postdata();
        }

        echo '</div>';

        $output = (string) ob_get_clean();

        // Store cache + track key for safe deletion later
        set_transient($cache_key, $output, HOUR_IN_SECONDS);
        $this->track_cache_key($cache_key);

        // Return filtered + safe HTML
        $output = apply_filters('advanced_html_sitemap_output', $output);
        return (string) wp_kses($output, $this->allowed_html());
    }

    /** Only allow safe tags/attrs in our HTML sitemap output. */
    private function allowed_html(): array
    {
        return [
            'div' => ['class' => true, 'id' => true],
            'ul'  => ['class' => true],
            'li'  => ['class' => true],
            'a'   => ['href' => true, 'title' => true, 'class' => true],
            'h3'  => ['class' => true],
            'small' => [],
        ];
    }

    /** Determines if a post is indexable. Returns true = keep. */
    public function is_indexable($post_id): bool
    {
        if (is_object($post_id) && isset($post_id->ID)) {
            $post_id = (int) $post_id->ID;
        } else {
            $post_id = (int) $post_id;
        }

        if ($post_id <= 0) return true;

        // Yoast SEO
        if (get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true) === '1') return false;

        // Rank Math: can be string or array
        $rm = get_post_meta($post_id, 'rank_math_robots', true);
        if ($rm === 'noindex') return false;
        if (is_array($rm) && in_array('noindex', $rm, true)) return false;

        // AIOSEO
        if (get_post_meta($post_id, '_aioseo_robots_noindex', true) === '1') return false;

        return true;
    }

    /**
     * Build hierarchical sitemap.
     * If $top_level=true, we output only <li>...</li> (outer <ul> is already printed).
     */
    private function build_tree(array $pages, int $parent_id = 0, bool $show_dates = false, bool $exclude_noindex = true, bool $top_level = false): string
    {
        $children = array_filter($pages, static fn($p) => (int)$p->post_parent === (int)$parent_id);
        if (empty($children)) return '';

        usort($children, static fn($a, $b) => strcasecmp($a->post_title, $b->post_title));

        $out = '';
        if (!$top_level) $out .= '<ul>';

        foreach ($children as $child) {
            if ($exclude_noindex && !$this->is_indexable($child)) continue;

            $out .= '<li><a href="' . esc_url(get_permalink($child->ID)) . '">' . esc_html($child->post_title);
            if ($show_dates) {
                $out .= ' <small>(' . esc_html(get_the_date('', $child->ID)) . ')</small>';
            }
            $out .= '</a>';

            $out .= $this->build_tree($pages, (int)$child->ID, $show_dates, $exclude_noindex, false);
            $out .= '</li>';
        }

        if (!$top_level) $out .= '</ul>';

        return $out;
    }

    /** Front-end CSS: enqueue only when shortcode runs (no wp_head dumping). */
    private function enqueue_front_styles(): void
    {
        $handle = 'advanced-html-sitemap';
        if (!wp_style_is($handle, 'registered')) {
            wp_register_style($handle, false, [], self::VERSION);
        }
        wp_enqueue_style($handle);

        $css = '
        .html-sitemap { display:flex; flex-wrap:wrap; gap:20px; }
        .html-sitemap.columns-1 .sitemap-section { flex:0 0 100%; }
        .html-sitemap.columns-2 .sitemap-section { flex:0 0 48%; }
        .html-sitemap.columns-3 .sitemap-section { flex:0 0 31%; }
        .html-sitemap ul { list-style:none; padding:0; margin:0; }
        .html-sitemap li { margin-bottom:6px; }
        .sitemap-index { width:100%; margin-bottom:20px; display:flex; flex-wrap:wrap; gap:15px; }
        .sitemap-index li { margin-right:10px; }
        .html-sitemap h3 { margin-top:0; }
        .html-sitemap .sitemap-section ul ul { margin-left:20px; }
        ';
        wp_add_inline_style($handle, $css);
    }

    /** Admin page */
    public function register_admin_page(): void
    {
        add_options_page(
            esc_html__('HTML Sitemap Settings', 'advanced-html-sitemap'),
            esc_html__('HTML Sitemap', 'advanced-html-sitemap'),
            'manage_options',
            self::OPTION_PAGE,
            [$this, 'render_admin_page']
        );
    }

    public function admin_assets($hook): void
    {
        if ($hook !== 'settings_page_' . self::OPTION_PAGE) return;

        wp_enqueue_script(
            'ahs-admin',
            plugins_url('assets/admin.js', __FILE__),
            [],
            self::VERSION,
            true
        );
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) return;

?>
        <div class="wrap">
            <h1><?php echo esc_html__('HTML Sitemap Shortcode Generator', 'advanced-html-sitemap'); ?></h1>
            <p><?php echo esc_html__('Use the options below to generate a shortcode:', 'advanced-html-sitemap'); ?></p>

            <form id="sitemap-shortcode-generator">
                <label><?php echo esc_html__('Post Types (comma separated):', 'advanced-html-sitemap'); ?><br>
                    <input type="text" name="post_types" value="page,post" style="width: 400px;">
                </label><br><br>

                <label><?php echo esc_html__('Columns:', 'advanced-html-sitemap'); ?><br>
                    <select name="columns">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </label><br><br>

                <label><?php echo esc_html__('Exclude IDs (comma separated):', 'advanced-html-sitemap'); ?><br>
                    <input type="text" name="exclude" style="width: 400px;">
                </label><br><br>

                <label><input type="checkbox" name="show_dates"> <?php echo esc_html__('Show Post Dates', 'advanced-html-sitemap'); ?></label><br>
                <label><input type="checkbox" name="hierarchical"> <?php echo esc_html__('Hierarchical Display', 'advanced-html-sitemap'); ?></label><br>
                <label><input type="checkbox" name="index" checked> <?php echo esc_html__('Show Index', 'advanced-html-sitemap'); ?></label><br>
                <label><input type="checkbox" name="exclude_noindex" checked> <?php echo esc_html__('Exclude noindex Posts', 'advanced-html-sitemap'); ?></label><br><br>

                <button type="button" onclick="AdvancedHTMLSitemapGenerateShortcode()" class="button button-primary">
                    <?php echo esc_html__('Generate Shortcode', 'advanced-html-sitemap'); ?>
                </button>
            </form>

            <h2><?php echo esc_html__('Shortcode', 'advanced-html-sitemap'); ?></h2>
            <textarea id="generated-shortcode" rows="3" style="width: 100%;"></textarea>
        </div>
<?php
    }

    private function cache_key(array $atts): string
    {
        return 'ahs_' . md5(wp_json_encode($atts));
    }

    private function track_cache_key(string $key): void
    {
        $keys = get_option(self::CACHE_KEYS_OPT, []);
        if (!is_array($keys)) $keys = [];
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            update_option(self::CACHE_KEYS_OPT, $keys, false);
        }
    }

    public function maybe_flush_cache(int $post_id, $post): void
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
        $this->flush_all_cache();
    }

    public function flush_all_cache(): void
    {
        $keys = get_option(self::CACHE_KEYS_OPT, []);
        if (!is_array($keys) || empty($keys)) return;

        foreach ($keys as $key) {
            delete_transient($key);
        }

        update_option(self::CACHE_KEYS_OPT, [], false);
    }
}

new Advanced_HTML_Sitemap();
