<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Advanced_HTML_Sitemap_GitHub_Updater {

    private const CACHE_KEY = 'ahs_github_update_data';

    private static $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('site_transient_update_plugins', [$this, 'remove_stale_update_notice']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_pre_download', [$this, 'download_private_package'], 10, 4);
        add_filter('upgrader_source_selection', [$this, 'rename_github_source'], 10, 4);
    }

    public function check_for_update($transient) {
        if (!is_object($transient)) {
            return $transient;
        }

        $remote = $this->get_remote_plugin_data();

        if (!$remote || empty($remote['version']) || !version_compare($remote['version'], $this->installed_version(), '>')) {
            return $this->mark_as_current($transient, $remote['version'] ?? '');
        }

        $transient->response[plugin_basename(AHS_FILE)] = $this->update_payload($remote);

        return $transient;
    }

    public function remove_stale_update_notice($transient) {
        if (!is_object($transient)) {
            return $transient;
        }

        $plugin = plugin_basename(AHS_FILE);

        if (empty($transient->response[$plugin]->new_version)) {
            return $transient;
        }

        if (version_compare((string) $transient->response[$plugin]->new_version, $this->installed_version(), '<=')) {
            return $this->mark_as_current($transient, (string) $transient->response[$plugin]->new_version);
        }

        return $transient;
    }

    public function plugin_info($result, string $action, object $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== dirname(plugin_basename(AHS_FILE))) {
            return $result;
        }

        $remote = $this->get_remote_plugin_data();

        if (!$remote) {
            return $result;
        }

        return (object) [
            'name'          => 'Advanced HTML Sitemap',
            'slug'          => dirname(plugin_basename(AHS_FILE)),
            'version'       => $remote['version'],
            'author'        => '<a href="' . esc_url($this->github_url()) . '">Pratik Shrestha</a>',
            'homepage'      => $this->github_url(),
            'download_link' => $this->zip_url(),
            'requires'      => $remote['requires'],
            'tested'        => $remote['tested'],
            'requires_php'  => $remote['requires_php'],
            'sections'      => [
                'description' => $remote['description'],
                'changelog'   => $remote['changelog'],
            ],
        ];
    }

    public function rename_github_source(string $source, string $remote_source, \WP_Upgrader $upgrader, array $hook_extra) {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== plugin_basename(AHS_FILE)) {
            return $source;
        }

        $expected_source = trailingslashit($remote_source) . dirname(plugin_basename(AHS_FILE));

        if (untrailingslashit($source) === untrailingslashit($expected_source)) {
            return $source;
        }

        if (is_dir($expected_source)) {
            return $source;
        }

        if (!rename($source, $expected_source)) {
            return $source;
        }

        return trailingslashit($expected_source);
    }

    public function download_private_package($reply, string $package, \WP_Upgrader $upgrader, array $hook_extra) {
        if (!empty($reply) || empty($hook_extra['plugin']) || $hook_extra['plugin'] !== plugin_basename(AHS_FILE)) {
            return $reply;
        }

        if ($package !== $this->zip_url() || !$this->github_token()) {
            return $reply;
        }

        $download_file = wp_tempnam($package);

        if (!$download_file) {
            return new WP_Error('ahs_no_temp_file', __('Could not create a temporary file for the GitHub update.', 'advanced-html-sitemap'));
        }

        $response = wp_remote_get($package, [
            'timeout'  => 300,
            'stream'   => true,
            'filename' => $download_file,
            'headers'  => $this->github_headers('application/vnd.github+json'),
        ]);

        if (is_wp_error($response)) {
            @unlink($download_file);
            return $response;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            @unlink($download_file);
            return new WP_Error('ahs_github_download_failed', __('GitHub returned an error while downloading the plugin update.', 'advanced-html-sitemap'));
        }

        return $download_file;
    }

    private function get_remote_plugin_data(): array {
        $cached = get_site_transient(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $defaults = [
            'version'      => '',
            'requires'     => '',
            'tested'       => '',
            'requires_php' => '',
            'description'  => '',
            'changelog'    => '',
        ];

        $plugin_file = $this->remote_get($this->raw_url('advanced-html-sitemap.php'));
        $readme      = $this->remote_get($this->raw_url('readme.txt'));

        if (!$plugin_file) {
            set_site_transient(self::CACHE_KEY, $defaults, $this->cache_ttl());
            return $defaults;
        }

        $data = array_merge($defaults, [
            'version'      => $this->read_header($plugin_file, 'Version'),
            'requires'     => $this->read_readme_value($readme, 'Requires at least'),
            'tested'       => $this->read_readme_value($readme, 'Tested up to'),
            'requires_php' => $this->read_readme_value($readme, 'Requires PHP'),
            'description'  => $this->read_readme_section($readme, 'Description'),
            'changelog'    => $this->read_readme_section($readme, 'Changelog'),
        ]);

        set_site_transient(self::CACHE_KEY, $data, $this->cache_ttl());

        return $data;
    }

    private function update_payload(array $remote): object {
        return (object) [
            'id'           => $this->github_url(),
            'slug'         => dirname(plugin_basename(AHS_FILE)),
            'plugin'       => plugin_basename(AHS_FILE),
            'new_version'  => $remote['version'],
            'url'          => $this->github_url(),
            'package'      => $this->zip_url(),
            'tested'       => $remote['tested'],
            'requires'     => $remote['requires'],
            'requires_php' => $remote['requires_php'],
        ];
    }

    private function mark_as_current($transient, string $remote_version) {
        $plugin = plugin_basename(AHS_FILE);

        unset($transient->response[$plugin]);

        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = [];
        }

        $transient->no_update[$plugin] = $this->update_payload([
            'version'      => $remote_version ?: $this->installed_version(),
            'tested'       => '',
            'requires'     => '',
            'requires_php' => '',
        ]);

        return $transient;
    }

    private function installed_version(): string {
        if (function_exists('get_file_data')) {
            $data = get_file_data(AHS_FILE, ['Version' => 'Version'], 'plugin');

            if (!empty($data['Version'])) {
                return (string) $data['Version'];
            }
        }

        return AHS_VERSION;
    }

    private function remote_get(string $url): string {
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => $this->github_headers('application/vnd.github.raw'),
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return '';
        }

        return (string) wp_remote_retrieve_body($response);
    }

    private function read_header(string $contents, string $header): string {
        if (!preg_match('/^[ \t\/*#@]*' . preg_quote($header, '/') . ':(.*)$/mi', $contents, $matches)) {
            return '';
        }

        return trim($matches[1]);
    }

    private function read_readme_value(string $readme, string $label): string {
        if (!$readme || !preg_match('/^' . preg_quote($label, '/') . ':\s*(.+)$/mi', $readme, $matches)) {
            return '';
        }

        return trim($matches[1]);
    }

    private function read_readme_section(string $readme, string $section): string {
        if (!$readme || !preg_match('/==\s*' . preg_quote($section, '/') . '\s*==\s*(.*?)(?=\n==\s*.+?\s*==|\z)/is', $readme, $matches)) {
            return '';
        }

        return wp_kses_post(wpautop(trim($matches[1])));
    }

    private function raw_url(string $path): string {
        return sprintf(
            'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
            rawurlencode((string) apply_filters('ahs_github_owner', AHS_GITHUB_OWNER)),
            rawurlencode((string) apply_filters('ahs_github_repo', AHS_GITHUB_REPO)),
            str_replace('%2F', '/', rawurlencode(ltrim($path, '/'))),
            rawurlencode((string) apply_filters('ahs_github_branch', AHS_GITHUB_BRANCH))
        );
    }

    private function zip_url(): string {
        return sprintf(
            'https://api.github.com/repos/%s/%s/zipball/%s',
            rawurlencode((string) apply_filters('ahs_github_owner', AHS_GITHUB_OWNER)),
            rawurlencode((string) apply_filters('ahs_github_repo', AHS_GITHUB_REPO)),
            rawurlencode((string) apply_filters('ahs_github_branch', AHS_GITHUB_BRANCH))
        );
    }

    private function github_url(): string {
        return sprintf(
            'https://github.com/%s/%s',
            rawurlencode((string) apply_filters('ahs_github_owner', AHS_GITHUB_OWNER)),
            rawurlencode((string) apply_filters('ahs_github_repo', AHS_GITHUB_REPO))
        );
    }

    private function cache_ttl(): int {
        return (int) apply_filters('ahs_github_update_cache_ttl', HOUR_IN_SECONDS);
    }

    private function github_headers(string $accept): array {
        $headers = [
            'Accept'               => $accept,
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        $token = $this->github_token();

        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    private function github_token(): string {
        $token = defined('AHS_GITHUB_TOKEN') ? AHS_GITHUB_TOKEN : '';

        return trim((string) apply_filters('ahs_github_token', $token));
    }
}
