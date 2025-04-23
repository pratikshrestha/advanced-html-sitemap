
<?php
/*
Plugin Name: Advanced HTML Sitemap
Description: Generate an HTML sitemap with customizable post types, taxonomies, columns, and more.
Version: 1.0.0
Author: Pratik Shrestha
GitHub Plugin URI: outpaceseo/advance-html-sitemap
GitHub Plugin URI: https://github.com/outpaceseo/advance-html-sitemap
*/

add_shortcode('html_sitemap', 'advanced_html_sitemap_shortcode');

function advanced_html_sitemap_shortcode($atts) {
    $atts = shortcode_atts([
        'post_types' => 'page,post',
        'columns' => '1',
        'taxonomy' => '',
        'term' => '',
        'limit' => -1,
        'exclude' => '',
        'show_dates' => 'false',
        'hierarchical' => 'false',
        'class' => '',
        'id' => '',
        'index' => 'true',
        'exclude_noindex' => 'true'
    ], $atts, 'html_sitemap');

    $columns = in_array($atts['columns'], ['1', '2', '3']) ? (int)$atts['columns'] : 1;
    $post_types = array_map('trim', explode(',', $atts['post_types']));
    $exclude_ids = array_map('intval', array_filter(explode(',', $atts['exclude'])));
    $show_dates = filter_var($atts['show_dates'], FILTER_VALIDATE_BOOLEAN);
    $hierarchical = filter_var($atts['hierarchical'], FILTER_VALIDATE_BOOLEAN);
    $show_index = filter_var($atts['index'], FILTER_VALIDATE_BOOLEAN);
    $exclude_noindex = filter_var($atts['exclude_noindex'], FILTER_VALIDATE_BOOLEAN);

    $cache_key = 'html_sitemap_' . md5(json_encode($atts));
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    ob_start();
    echo '<div class="html-sitemap columns-' . esc_attr($columns) . ' ' . esc_attr($atts['class']) . '" id="' . esc_attr($atts['id']) . '">';

    if ($show_index) {
        echo '<ul class="sitemap-index">';
        foreach ($post_types as $post_type) {
            $post_type_obj = get_post_type_object($post_type);
            if ($post_type_obj) {
                echo '<li><a href="#sitemap-' . esc_attr($post_type) . '">' . esc_html($post_type_obj->labels->name) . '</a></li>';
            }
        }
        echo '</ul>';
    }

    foreach ($post_types as $post_type) {
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => (int)$atts['limit'],
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'post__not_in' => $exclude_ids,
        ];

        if ($atts['taxonomy'] && $atts['term']) {
            $args['tax_query'] = [[
                'taxonomy' => sanitize_text_field($atts['taxonomy']),
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['term'])
            ]];
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $post_type_obj = get_post_type_object($post_type);
            $label = $post_type_obj ? $post_type_obj->labels->name : ucfirst($post_type);

            echo '<div class="sitemap-section" id="sitemap-' . esc_attr($post_type) . '">';
            echo '<h3>' . esc_html($label) . '</h3>';
            echo '<ul>';

            if ($hierarchical && is_post_type_hierarchical($post_type)) {
                $pages = get_pages([
                    'post_type' => $post_type,
                    'exclude' => $exclude_ids,
                    'sort_column' => 'post_title',
                    'hierarchical' => 1,
                ]);
                if ($exclude_noindex) {
                    $pages = array_filter($pages, 'sitemap_exclude_noindex');
                }
                echo build_sitemap_tree($pages, 0, $show_dates, $exclude_noindex);
            } else {
                $posts = [];
                while ($query->have_posts()) {
                    $query->the_post();
                    if ($exclude_noindex && !sitemap_exclude_noindex(get_the_ID())) continue;
                    $posts[get_the_title()] = get_the_ID();
                }
                ksort($posts, SORT_NATURAL | SORT_FLAG_CASE);
                foreach ($posts as $title => $post_id) {
                    echo '<li><a href="' . esc_url(get_permalink($post_id)) . '">' . esc_html($title);
                    if ($show_dates) echo ' <small>(' . esc_html(get_the_date('', $post_id)) . ')</small>';
                    echo '</a></li>';
                }
            }

            echo '</ul>';
            echo '</div>';
        }
        wp_reset_postdata();
    }

    echo '</div>';

    $output = ob_get_clean();
    set_transient($cache_key, $output, HOUR_IN_SECONDS);

    return apply_filters('advanced_html_sitemap_output', $output);
}

function sitemap_exclude_noindex($post_id) {
    if (is_object($post_id)) {
        $post_id = $post_id->ID;
    }

    $noindex = false;

    // Yoast SEO
    if (get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true) === '1') {
        $noindex = true;
    }

    // Rank Math
    if (get_post_meta($post_id, 'rank_math_robots', true) === 'noindex') {
        $noindex = true;
    }

    // All in One SEO
    if (get_post_meta($post_id, '_aioseo_robots_noindex', true) === '1') {
        $noindex = true;
    }

    return !$noindex;
}

function build_sitemap_tree($pages, $parent_id = 0, $show_dates = false, $exclude_noindex = true) {
    $output = '';
    $children = array_filter($pages, fn($p) => $p->post_parent == $parent_id);
    if (!empty($children)) {
        usort($children, fn($a, $b) => strcasecmp($a->post_title, $b->post_title));
        $output .= '<ul>';
        foreach ($children as $child) {
            if ($exclude_noindex && !sitemap_exclude_noindex($child)) continue;
            $output .= '<li><a href="' . esc_url(get_permalink($child->ID)) . '">' . esc_html($child->post_title);
            if ($show_dates) $output .= ' <small>(' . esc_html(get_the_date('', $child->ID)) . ')</small>';
            $output .= '</a>';
            $output .= build_sitemap_tree($pages, $child->ID, $show_dates, $exclude_noindex);
            $output .= '</li>';
        }
        $output .= '</ul>';
    }
    return $output;
}

add_action('wp_head', function () {
    echo '<style>
    .html-sitemap { display: flex; flex-wrap: wrap; gap: 20px; }
    .html-sitemap.columns-1 .sitemap-section { flex: 0 0 100%; }
    .html-sitemap.columns-2 .sitemap-section { flex: 0 0 48%; }
    .html-sitemap.columns-3 .sitemap-section { flex: 0 0 31%; }
    .html-sitemap ul { list-style: none; padding: 0; margin: 0; }
    .html-sitemap li { margin-bottom: 6px; }
    .sitemap-index { width: 100%; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; }
    .sitemap-index li { margin-right: 10px; }
    .html-sitemap h3 { margin-top: 0; }
    .html-sitemap .sitemap-section ul ul { margin-left: 20px; }
    </style>';
});

// Admin shortcode generator UI
add_action('admin_menu', function () {
    add_options_page('HTML Sitemap Settings', 'HTML Sitemap', 'manage_options', 'html-sitemap-generator', 'html_sitemap_generator_page');
});

function html_sitemap_generator_page() {
    ?>
    <div class="wrap">
        <h1>HTML Sitemap Shortcode Generator</h1>
        <p>Use the options below to generate a shortcode:</p>
        <form id="sitemap-shortcode-generator">
            <label>Post Types (comma separated):<br>
                <input type="text" name="post_types" value="page,post" style="width: 400px;">
            </label><br><br>
            <label>Columns:<br>
                <select name="columns">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </label><br><br>
            <label>Exclude IDs (comma separated):<br>
                <input type="text" name="exclude" style="width: 400px;">
            </label><br><br>
            <label><input type="checkbox" name="show_dates"> Show Post Dates</label><br>
            <label><input type="checkbox" name="hierarchical"> Hierarchical Display</label><br>
            <label><input type="checkbox" name="index" checked> Show Index</label><br>
            <label><input type="checkbox" name="exclude_noindex" checked> Exclude noindex Posts</label><br><br>
            <button type="button" onclick="generateShortcode()" class="button button-primary">Generate Shortcode</button>
        </form>
        <h2>Shortcode</h2>
        <textarea id="generated-shortcode" rows="3" style="width: 100%;"></textarea>
    </div>
    <script>
        function generateShortcode() {
            const form = document.getElementById('sitemap-shortcode-generator');
            const formData = new FormData(form);
            const attrs = [];
            for (let [key, value] of formData.entries()) {
                if (key && (value || key === 'exclude_noindex')) {
                    if (form.elements[key].type === 'checkbox') {
                        if (form.elements[key].checked) value = 'true'; else value = 'false';
                    }
                    attrs.push(`${key}="${value}"`);
                }
            }
            const shortcode = `[html_sitemap ${attrs.join(' ')}]`;
            document.getElementById('generated-shortcode').value = shortcode;
        }
    </script>
    <?php
}

require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/outpaceseo/advanced-html-sitemap/',
    __FILE__,
    'advanced-html-sitemap'
);

// Optional: If your repo is private, use an access token.
$myUpdateChecker->setAuthentication('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');

// Optional: Set the branch to use for updates.
$myUpdateChecker->setBranch('main');

// Optional: Show changelog from releases.
$myUpdateChecker->getVcsApi()->enableReleaseAssets();