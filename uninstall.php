<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('bdtc_template_rules');
delete_option('bdtc_enabled_template_types');

// Clear any cached data that has been removed
wp_cache_flush(); 