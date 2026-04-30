<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Advanced_HTML_Sitemap_Admin
{

    const OPTION_PAGE = 'advanced-html-sitemap-generator';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_filter('plugin_action_links_' . plugin_basename(AHS_FILE), [$this, 'plugin_action_links']);
    }

    public function register_admin_page(): void
    {
        add_options_page(
            esc_html__('Advanced HTML Sitemap Settings', 'advanced-html-sitemap'),
            esc_html__('Advanced HTML Sitemap', 'advanced-html-sitemap'),
            'manage_options',
            self::OPTION_PAGE,
            [$this, 'render_admin_page']
        );
    }

    public function admin_assets($hook): void
    {
        if ($hook !== 'settings_page_' . self::OPTION_PAGE) return;

        $admin_js_path = AHS_DIR . 'assets/admin.js';
        $admin_js_ver  = file_exists($admin_js_path) ? filemtime($admin_js_path) : AHS_VERSION;

        wp_enqueue_script(
            'advanced-html-sitemap-admin',
            AHS_URL . 'assets/admin.js',
            [],
            $admin_js_ver,
            true
        );
    }

    public function plugin_action_links(array $links): array
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=' . self::OPTION_PAGE)),
            esc_html__('Settings', 'advanced-html-sitemap')
        );

        $details_link = sprintf(
            '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
            esc_url(network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . dirname(plugin_basename(AHS_FILE)) . '&TB_iframe=true&width=600&height=550')),
            esc_attr__('View Advanced HTML Sitemap details', 'advanced-html-sitemap'),
            esc_html__('View details', 'advanced-html-sitemap')
        );

        array_unshift($links, $settings_link, $details_link);

        return $links;
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $post_types = get_post_types(
            [
                'public'  => true,
                'show_ui' => true,
            ],
            'objects'
        );
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Advanced HTML Sitemap Shortcode Generator', 'advanced-html-sitemap'); ?></h1>
            <p><?php echo esc_html__('Select options below to generate a shortcode for your HTML sitemap.', 'advanced-html-sitemap'); ?></p>

            <form id="sitemap-shortcode-generator" class="ahs-admin-form">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label><?php echo esc_html__('Post Types', 'advanced-html-sitemap'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <?php echo esc_html__('Post Types', 'advanced-html-sitemap'); ?>
                                    </legend>

                                    <?php foreach ($post_types as $post_type) :
                                        $checked = in_array($post_type->name, ['page', 'post'], true);
                                    ?>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="post_types[]"
                                                value="<?php echo esc_attr($post_type->name); ?>"
                                                <?php checked($checked); ?>>
                                            <?php echo esc_html($post_type->labels->singular_name); ?>
                                            <span class="description">(<?php echo esc_html($post_type->name); ?>)</span>
                                        </label>
                                        <br>
                                    <?php endforeach; ?>

                                    <p class="description">
                                        <?php echo esc_html__('Choose which post types to include in the sitemap.', 'advanced-html-sitemap'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="ahs-columns"><?php echo esc_html__('Columns', 'advanced-html-sitemap'); ?></label>
                            </th>
                            <td>
                                <select name="columns" id="ahs-columns">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                                <p class="description">
                                    <?php echo esc_html__('Controls the layout width of sitemap sections.', 'advanced-html-sitemap'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="ahs-exclude"><?php echo esc_html__('Exclude IDs', 'advanced-html-sitemap'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    name="exclude"
                                    id="ahs-exclude"
                                    class="regular-text"
                                    placeholder="<?php echo esc_attr__('e.g. 12,34,56', 'advanced-html-sitemap'); ?>">
                                <p class="description">
                                    <?php echo esc_html__('Comma-separated post/page IDs to exclude from the sitemap.', 'advanced-html-sitemap'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('Display Options', 'advanced-html-sitemap'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <?php echo esc_html__('Display Options', 'advanced-html-sitemap'); ?>
                                    </legend>

                                    <label>
                                        <input type="checkbox" name="show_dates" value="true">
                                        <?php echo esc_html__('Show post dates', 'advanced-html-sitemap'); ?>
                                    </label>
                                    <br>

                                    <label>
                                        <input type="checkbox" name="hierarchical" value="true" checked>
                                        <?php echo esc_html__('Hierarchical display (parent → child)', 'advanced-html-sitemap'); ?>
                                    </label>
                                    <br>

                                    <label>
                                        <input type="checkbox" name="index" value="true">
                                        <?php echo esc_html__('Show index links', 'advanced-html-sitemap'); ?>
                                    </label>
                                    <br>

                                    <label>
                                        <input type="checkbox" name="exclude_noindex" value="true" checked>
                                        <?php echo esc_html__('Exclude noindex content', 'advanced-html-sitemap'); ?>
                                    </label>
                                    <br>

                                    <label>
                                        <input type="checkbox" name="disable_css" value="true">
                                        <?php echo esc_html__('Disable all plugin css', 'advanced-html-sitemap'); ?>
                                    </label>

                                    <p class="description">
                                        <?php echo esc_html__('These options control sitemap layout and what gets included.', 'advanced-html-sitemap'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p>
                    <button type="button" id="ahs-generate" class="button button-primary">
                        <?php echo esc_html__('Generate Shortcode', 'advanced-html-sitemap'); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h2><?php echo esc_html__('Generated Shortcode', 'advanced-html-sitemap'); ?></h2>

            <div class="ahs-shortcode-output">
                <textarea
                    id="generated-shortcode"
                    rows="3"
                    class="large-text code"
                    readonly></textarea>

                <p>
                    <button
                        type="button"
                        id="ahs-copy-shortcode"
                        class="button button-secondary"
                        disabled>
                        <?php echo esc_html__('Copy Shortcode', 'advanced-html-sitemap'); ?>
                    </button>

                    <span
                        id="ahs-copy-status"
                        class="description"
                        style="margin-left:8px; display:none;"
                        aria-live="polite">
                        <?php echo esc_html__('Copied!', 'advanced-html-sitemap'); ?>
                    </span>
                </p>

                <p class="description">
                    <?php echo esc_html__('Copy and paste this shortcode into any page or post.', 'advanced-html-sitemap'); ?>
                </p>
            </div>
        </div>
<?php
    }
}
