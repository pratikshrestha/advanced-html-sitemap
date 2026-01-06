<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('ahs_cache_keys');
