<?php
/*
Plugin Name: Bricks Dynamic Template Control
Description: Applies fallback Bricks templates to selected post types not built with Bricks. Includes admin settings.
Version: 1.2
Author: <a href="https://luisameglio.com">Luis Ameglio</a>
Text Domain: bricks-dynamic-template-control
Domain Path: /languages
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Check if Bricks theme is active (either directly or as parent theme)
function bdtc_check_theme_dependency() {
    // Check if Bricks functions exist
    if (!function_exists('bricks_is_builder')) {
        // Check if current theme is a child theme of Bricks
        $current_theme = wp_get_theme();
        $parent_theme = $current_theme->parent();
        
        if (!$parent_theme || $parent_theme->get('Name') !== 'Bricks') {
            add_action('admin_notices', 'bdtc_theme_missing_notice');
            return false;
        }
    }
    return true;
}

function bdtc_theme_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Bricks Dynamic Template Control requires the Bricks Builder theme to be installed and activated (either directly or as a parent theme).', 'bricks-dynamic-template-control'); ?></p>
    </div>
    <?php
}

// Only proceed if Bricks theme is active
if (!bdtc_check_theme_dependency()) {
    return;
}

add_action( 'admin_menu', 'bdtc_add_admin_menu', 99 );
add_action( 'admin_init', 'bdtc_settings_init' );

function bdtc_add_admin_menu() {
    add_submenu_page(
        'bricks',
        'Bricks DTC',
        'Bricks DTC',
        'manage_options',
        'bricks_template_control',
        'bdtc_options_page'
    );
}

function bdtc_settings_init() {
    // Register template rules setting with proper sanitization
    register_setting(
        'bdtc_settings_group',
        'bdtc_template_rules',
        array(
            'type' => 'array',
            'sanitize_callback' => 'bdtc_sanitize_template_rules',
            'default' => array(
                array(
                    'template_id' => '',
                    'post_types' => array(),
                    'user_role' => '',
                    'tax_term_ids' => array(),
                    'priority' => 0
                )
            )
        )
    );

    // Register template types setting with proper sanitization
    register_setting(
        'bdtc_settings_group',
        'bdtc_enabled_template_types',
        array(
            'type' => 'array',
            'sanitize_callback' => 'bdtc_sanitize_template_types',
            'default' => array('content')
        )
    );

    add_settings_section(
        'bdtc_settings_section',
        __('Template Rules', 'bricks-dynamic-template-control'),
        function() {
            echo '<p>' . __('Configure multiple fallback templates with their own conditions.', 'bricks-dynamic-template-control') . '</p>';
        },
        'bricks_template_control'
    );

    // Add template types settings
    add_settings_section(
        'bdtc_template_types_section',
        __('Template Types', 'bricks-dynamic-template-control'),
        function() {
            echo '<p>' . __('Select which template types should be available in the rules.', 'bricks-dynamic-template-control') . '</p>';
        },
        'bricks_template_control'
    );

    add_settings_field(
        'bdtc_enabled_template_types',
        __('Available Template Types', 'bricks-dynamic-template-control'),
        'bdtc_template_types_callback',
        'bricks_template_control',
        'bdtc_template_types_section'
    );
}

// Add sanitization functions
function bdtc_sanitize_template_rules($rules) {
    if (!is_array($rules)) {
        return array();
    }

    $sanitized_rules = array();
    foreach ($rules as $rule) {
        $sanitized_rule = array(
            'template_id' => isset($rule['template_id']) ? absint($rule['template_id']) : '',
            'post_types' => isset($rule['post_types']) ? array_map('sanitize_text_field', (array)$rule['post_types']) : array(),
            'user_role' => isset($rule['user_role']) ? sanitize_text_field($rule['user_role']) : '',
            'tax_term_ids' => isset($rule['tax_term_ids']) ? array_map('absint', (array)$rule['tax_term_ids']) : array(),
            'priority' => isset($rule['priority']) ? absint($rule['priority']) : 0
        );
        $sanitized_rules[] = $sanitized_rule;
    }
    return $sanitized_rules;
}

function bdtc_sanitize_template_types($types) {
    if (!is_array($types)) {
        return array('content');
    }

    $valid_types = array(
        'header',
        'footer',
        'content',
        'section',
        'popup',
        'archive',
        'search',
        'error',
        'single-product',
        'product-archive',
        'password'
    );

    return array_intersect($types, $valid_types);
}

function bdtc_template_types_callback() {
    $enabled_types = get_option('bdtc_enabled_template_types', array('content'));
    $template_types = array(
        'header' => 'Header',
        'footer' => 'Footer',
        'content' => 'Single Post/Page',
        'section' => 'Section',
        'popup' => 'Popup',
        'archive' => 'Archive',
        'search' => 'Search Results',
        'error' => 'Error Page',
        'single-product' => 'Single Product',
        'product-archive' => 'Product Archive',
        'password' => 'Password Protection'
    );

    // Add Select All checkbox
    echo '<div class="bdtc-select-all-wrapper" style="margin-bottom: 15px;">';
    printf(
        '<label style="display: inline-block; margin-right: 15px; padding: 8px 12px; background: #f0f0f1; border-radius: 4px; cursor: pointer;">
            <input type="checkbox" id="bdtc-select-all" %s> Select All
        </label>',
        count($enabled_types) === count($template_types) ? 'checked' : ''
    );
    echo '</div>';

    // Add template type checkboxes
    foreach ($template_types as $type => $label) {
        printf(
            '<label class="bdtc-template-type" style="display: inline-block; margin-right: 15px; margin-bottom: 10px;">
                <input type="checkbox" name="bdtc_enabled_template_types[]" value="%s" %s> %s
            </label>',
            esc_attr($type),
            in_array($type, $enabled_types) ? 'checked' : '',
            esc_html($label)
        );
    }
}

function bdtc_get_template_rules() {
    $rules = get_option('bdtc_template_rules', array());
    if (!is_array($rules)) {
        $rules = array();
    }

    // If no rules exist, create the first rule automatically
    if (empty($rules)) {
        $rules[] = array(
            'template_id' => '',
            'post_types' => array(),
            'user_role' => '',
            'tax_term_ids' => array(),
            'priority' => 0
        );
        update_option('bdtc_template_rules', $rules);
    }

    return $rules;
}

// Add admin scripts and styles
function bdtc_admin_enqueue_scripts($hook) {
    if ('bricks_page_bricks_template_control' !== $hook) {
        return;
    }

    wp_enqueue_style('bdtc-admin-style', plugins_url('css/admin.css', __FILE__));
    wp_enqueue_script('bdtc-admin-script', plugins_url('js/admin.js', __FILE__), array(), '1.0', true);
    
    // Update nonce setup
    wp_localize_script('bdtc-admin-script', 'bdtcAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bdtc-ajax-nonce'),
        'resetNonce' => wp_create_nonce('bdtc-reset-nonce')
    ));

    // Add inline script for template type selection
    wp_add_inline_script('bdtc-admin-script', '
        jQuery(document).ready(function($) {
            // Handle Select All checkbox
            $("#bdtc-select-all").on("change", function() {
                var isChecked = $(this).prop("checked");
                $(".bdtc-template-type input[type=checkbox]").prop("checked", isChecked);
            });

            // Update Select All checkbox state when individual checkboxes change
            $(".bdtc-template-type input[type=checkbox]").on("change", function() {
                var allChecked = $(".bdtc-template-type input[type=checkbox]:checked").length === $(".bdtc-template-type input[type=checkbox]").length;
                $("#bdtc-select-all").prop("checked", allChecked);
            });
        });
    ');
}
add_action('admin_enqueue_scripts', 'bdtc_admin_enqueue_scripts');

// AJAX handlers
function bdtc_add_rule_ajax() {
    check_ajax_referer('bdtc-ajax-nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $rules = bdtc_get_template_rules();
    $rules[] = array(
        'template_id' => '',
        'post_types' => array(),
        'user_role' => '',
        'tax_term_ids' => array(),
        'priority' => count($rules)
    );
    update_option('bdtc_template_rules', $rules);
    
    wp_send_json_success(array(
        'message' => 'New rule added successfully',
        'rules' => $rules
    ));
}
add_action('wp_ajax_bdtc_add_rule', 'bdtc_add_rule_ajax');

function bdtc_delete_rule_ajax() {
    check_ajax_referer('bdtc-ajax-nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $index = intval($_POST['index']);
    $rules = bdtc_get_template_rules();
    
    if (isset($rules[$index])) {
        unset($rules[$index]);
        $rules = array_values($rules);
        update_option('bdtc_template_rules', $rules);
        wp_send_json_success(array(
            'message' => 'Rule deleted successfully',
            'rules' => $rules
        ));
    }
    
    wp_send_json_error('Rule not found');
}
add_action('wp_ajax_bdtc_delete_rule', 'bdtc_delete_rule_ajax');

function bdtc_update_rules_ajax() {
    // Enable error logging
    error_log('BDTC: Starting rule update process');
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bdtc-ajax-nonce')) {
        error_log('BDTC: Nonce verification failed');
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        error_log('BDTC: User capability check failed');
        wp_send_json_error(['message' => 'You do not have permission to perform this action']);
        return;
    }

    // Get and validate rules data
    if (!isset($_POST['rules'])) {
        error_log('BDTC: No rules data received');
        wp_send_json_error(['message' => 'No rules data received']);
        return;
    }

    $rules = json_decode(stripslashes($_POST['rules']), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('BDTC: JSON decode error: ' . json_last_error_msg());
        wp_send_json_error(['message' => 'Invalid data format']);
        return;
    }

    error_log('BDTC: Received rules: ' . print_r($rules, true));

    // Validate each rule
    foreach ($rules as $index => $rule) {
        if (!isset($rule['template']) || !isset($rule['post_type'])) {
            error_log('BDTC: Missing required fields in rule #' . $index . ': ' . print_r($rule, true));
            wp_send_json_error(['message' => 'Each rule must have a template and post type']);
            return;
        }

        // Validate template exists
        $template = get_post($rule['template']);
        if (!$template || $template->post_type !== 'bricks_template') {
            error_log('BDTC: Invalid template ID: ' . $rule['template']);
            wp_send_json_error(['message' => 'Invalid template selected']);
            return;
        }

        // Validate post type exists
        if (!post_type_exists($rule['post_type'])) {
            error_log('BDTC: Invalid post type: ' . $rule['post_type']);
            wp_send_json_error(['message' => 'Invalid post type selected']);
            return;
        }
    }

    // Check for conflicts
    $templates = array_column($rules, 'template');
    $post_types = array_column($rules, 'post_type');
    
    if (count($templates) !== count(array_unique($templates))) {
        error_log('BDTC: Duplicate templates found');
        wp_send_json_error(['message' => 'Each template can only be used once']);
        return;
    }
    
    if (count($post_types) !== count(array_unique($post_types))) {
        error_log('BDTC: Duplicate post types found');
        wp_send_json_error(['message' => 'Each post type can only be used once']);
        return;
    }

    try {
        // Convert rules to the correct format for storage
        $formatted_rules = array();
        foreach ($rules as $rule) {
            $formatted_rules[] = array(
                'template_id' => $rule['template'],
                'post_types' => array($rule['post_type']),
                'user_role' => $rule['user_role'],
                'tax_term_ids' => $rule['tax_term_ids'],
                'priority' => $rule['priority']
            );
        }

        error_log('BDTC: Formatted rules for storage: ' . print_r($formatted_rules, true));

        // Save rules
        $updated = update_option('bdtc_template_rules', $formatted_rules);
        error_log('BDTC: Rules update result: ' . ($updated ? 'success' : 'failed'));
        
        if ($updated) {
            wp_send_json_success(['message' => 'Rules saved successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to save rules. Please try again.']);
        }
    } catch (Exception $e) {
        error_log('BDTC: Unexpected error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_bdtc_update_rules', 'bdtc_update_rules_ajax');

function bdtc_reset_rules_ajax() {
    check_ajax_referer('bdtc-ajax-nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
        return;
    }

    // Reset to default state with one empty rule
    $default_rules = array(
        array(
            'template_id' => '',
            'post_types' => array(),
            'user_role' => '',
            'tax_term_ids' => array(),
            'priority' => 0
        )
    );
    
    // Always update the option, even if it's the same value
    update_option('bdtc_template_rules', $default_rules);
    
    // Send success response
    wp_send_json_success(array(
        'message' => 'All rules have been reset successfully',
        'rules' => $default_rules
    ));
}
add_action('wp_ajax_bdtc_reset_rules', 'bdtc_reset_rules_ajax');

function bdtc_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $rules = bdtc_get_template_rules();
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'rules';
    ?>
    <div class="wrap bdtc-admin">
        <h1><?php esc_html_e('Bricks Dynamic Template Control', 'bricks-dynamic-template-control'); ?></h1>

        <div id="bdtc-notice" class="notice" style="display: none; margin: 20px 0;"></div>

        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url(add_query_arg('tab', 'rules')); ?>" class="nav-tab <?php echo $active_tab === 'rules' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Template Rules', 'bricks-dynamic-template-control'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'settings')); ?>" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Settings', 'bricks-dynamic-template-control'); ?>
            </a>
        </nav>

        <?php if ($active_tab === 'settings'): ?>
            <div class="bdtc-settings-section">
                <form method="post" action="" id="bdtc-settings-form">
                    <?php wp_nonce_field('bdtc_update_settings'); ?>
                    <input type="hidden" name="bdtc_update_settings" value="1">
                    
                    <div class="bdtc-settings-content">
                        <?php bdtc_template_types_callback(); ?>
                    </div>

                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'bricks-dynamic-template-control'); ?>">
                    </p>
                </form>
            </div>
        <?php else: ?>
            <div class="bdtc-rules-section">
                <div id="bdtc-rules" class="bdtc-rules-container">
                    <?php foreach ($rules as $index => $rule): ?>
                        <div class="bdtc-rule" data-index="<?php echo esc_attr($index); ?>">
                            <div class="bdtc-rule-header">
                                <h3><?php printf(esc_html__('Fallback Template Rule #%d', 'bricks-dynamic-template-control'), $index + 1); ?></h3>
                                <button type="button" class="bdtc-delete-rule button" data-index="<?php echo esc_attr($index); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                            
                            <div class="bdtc-rule-content">
                                <div class="bdtc-field">
                                    <label><strong><?php esc_html_e('Template:', 'bricks-dynamic-template-control'); ?></strong></label>
                                    <?php bdtc_template_dropdown('bdtc_rules[' . $index . '][template_id]', $rule['template_id'], $index); ?>
                                </div>

                                <div class="bdtc-field">
                                    <label><strong><?php esc_html_e('Post Types:', 'bricks-dynamic-template-control'); ?></strong></label>
                                    <?php bdtc_post_types_multiselect('bdtc_rules[' . $index . '][post_types]', $rule['post_types']); ?>
                                    <small><?php esc_html_e('Note: Each post type can only be used in one rule.', 'bricks-dynamic-template-control'); ?></small>
                                </div>

                                <div class="bdtc-field">
                                    <label><strong><?php esc_html_e('User Role:', 'bricks-dynamic-template-control'); ?></strong></label>
                                    <?php bdtc_user_role_select('bdtc_rules[' . $index . '][user_role]', $rule['user_role']); ?>
                                </div>

                                <div class="bdtc-field">
                                    <label><strong><?php esc_html_e('Categories/Terms:', 'bricks-dynamic-template-control'); ?></strong></label>
                                    <?php bdtc_tax_terms_multiselect('bdtc_rules[' . $index . '][tax_term_ids]', $rule['tax_term_ids']); ?>
                                </div>

                                <div class="bdtc-field">
                                    <label><strong><?php esc_html_e('Priority:', 'bricks-dynamic-template-control'); ?></strong></label>
                                    <input type="number" name="bdtc_rules[<?php echo esc_attr($index); ?>][priority]" value="<?php echo esc_attr($rule['priority']); ?>" min="0">
                                    <small><?php esc_html_e('Lower number = higher priority', 'bricks-dynamic-template-control'); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bdtc-actions">
                    <button type="button" id="bdtc-save-rules" class="button button-primary"><?php esc_html_e('Save Rules', 'bricks-dynamic-template-control'); ?></button>
                    <button type="button" id="bdtc-add-rule" class="button"><?php esc_html_e('Add New Rule', 'bricks-dynamic-template-control'); ?></button>
                    <button type="button" id="bdtc-reset-rules" class="button" style="color: #dc3232;"><?php esc_html_e('Reset All Rules', 'bricks-dynamic-template-control'); ?></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="bdtc-footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
            <a href="<?php echo esc_url('https://buymeacoffee.com/luisameglio'); ?>" target="_blank" style="display: inline-flex; align-items: center; text-decoration: none; color: #666;">
                <span class="dashicons dashicons-coffee" style="font-size: 20px; width: 20px; height: 20px; margin-right: 5px;"></span>
                <?php esc_html_e('Buy me a coffee', 'bricks-dynamic-template-control'); ?>
            </a>
        </div>
    </div>
    <?php
}

function bdtc_template_dropdown($name, $selected = '', $current_rule_index = null) {
    $enabled_types = get_option('bdtc_enabled_template_types', array('content'));
    
    // Get all templates
    $templates = get_posts([
        'post_type' => 'bricks_template',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);

    // Debug information
    error_log('Enabled template types: ' . print_r($enabled_types, true));
    error_log('Total templates found: ' . count($templates));

    // Filter templates based on enabled types
    $filtered_templates = array();
    foreach ($templates as $template) {
        // Get template type from _bricks_template_type
        $template_type = get_post_meta($template->ID, '_bricks_template_type', true);
        error_log("Template ID: {$template->ID}, Title: " . esc_html($template->post_title) . ", Type: " . esc_html($template_type));
        
        if (in_array($template_type, $enabled_types)) {
            $filtered_templates[] = $template;
        }
    }

    error_log('Filtered templates count: ' . count($filtered_templates));

    // Get all rules to check for used templates
    $rules = bdtc_get_template_rules();
    $used_template_ids = array();
    
    // Collect template IDs used in other rules
    foreach ($rules as $index => $rule) {
        if ($index !== $current_rule_index && !empty($rule['template_id'])) {
            $used_template_ids[] = $rule['template_id'];
        }
    }

    echo '<select name="' . esc_attr($name) . '">';
    echo '<option value="">' . esc_html__('— Select a template —', 'bricks-dynamic-template-control') . '</option>';
    
    if (empty($filtered_templates)) {
        echo '<option value="" disabled>' . esc_html__('No templates available for selected types', 'bricks-dynamic-template-control') . '</option>';
        // Add debug information in the admin
        if (current_user_can('manage_options')) {
            echo '<br><small style="color: #666;">' . esc_html__('Debug: Enabled types:', 'bricks-dynamic-template-control') . ' ' . esc_html(implode(', ', $enabled_types)) . '</small>';
            // Add more debug info
            echo '<br><small style="color: #666;">' . esc_html__('Available templates:', 'bricks-dynamic-template-control') . ' ';
            foreach ($templates as $template) {
                $type = get_post_meta($template->ID, '_bricks_template_type', true);
                echo esc_html($template->post_title) . ' (' . esc_html($type) . '), ';
            }
            echo '</small>';
        }
    } else {
        foreach ($filtered_templates as $template) {
            // Skip if template is used in another rule
            if (in_array($template->ID, $used_template_ids)) {
                continue;
            }
            
            // Get template type for display
            $template_type = get_post_meta($template->ID, '_bricks_template_type', true);

            printf(
                '<option value="%d"%s>%s (%s)</option>',
                $template->ID,
                selected($selected, $template->ID, false),
                esc_html($template->post_title),
                esc_html(ucfirst($template_type))
            );
        }
    }
    echo '</select>';
    
    // If the current rule has a template that would be hidden, show it anyway
    if ($selected && in_array($selected, $used_template_ids)) {
        $template = get_post($selected);
        if ($template) {
            printf(
                '<br><small style="color: #666;">%s</small>',
                esc_html__('Note: This template is also used in another rule.', 'bricks-dynamic-template-control')
            );
        }
    }
}

function bdtc_post_types_multiselect($name, $selected = array()) {
    $post_types = get_post_types(['public' => true], 'objects');
    
    // Exclude Media and Bricks Templates
    $excluded_types = array('attachment', 'bricks_template');
    
    echo '<select name="' . esc_attr($name) . '[]" multiple style="height: 100px;">';
    foreach ($post_types as $pt) {
        // Skip excluded post types
        if (in_array($pt->name, $excluded_types)) {
            continue;
        }
        
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($pt->name),
            in_array($pt->name, $selected) ? ' selected' : '',
            esc_html($pt->label)
        );
    }
    echo '</select><br><small>Hold CTRL (or CMD) to select multiple.</small>';
}

function bdtc_user_role_select($name, $selected = '') {
    global $wp_roles;

    echo '<select name="' . esc_attr($name) . '">';
    echo '<option value="">— All Users —</option>';
    foreach ($wp_roles->roles as $role => $details) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($role),
            selected($selected, $role, false),
            esc_html($details['name'])
        );
    }
    echo '</select>';
}

function bdtc_tax_terms_multiselect($name, $selected = array()) {
    $terms = get_terms([
        'taxonomy' => 'category',
        'hide_empty' => false,
    ]);

    echo '<select name="' . esc_attr($name) . '[]" multiple style="height: 100px;">';
    foreach ($terms as $term) {
        printf(
            '<option value="%d"%s>%s</option>',
            $term->term_id,
            in_array($term->term_id, $selected) ? ' selected' : '',
            esc_html($term->name)
        );
    }
    echo '</select><br><small>Select categories the fallback should apply to (posts only).</small>';
}

add_filter('bricks/active_templates', 'bdtc_set_active_templates', 10, 3);

function bdtc_set_active_templates($active_templates, $post_id, $content_type) {
    if ($content_type !== 'content') {
        return $active_templates;
    }

    $rules = bdtc_get_template_rules();
    if (empty($rules)) {
        return $active_templates;
    }

    // Sort rules by priority
    usort($rules, function($a, $b) {
        return $a['priority'] - $b['priority'];
    });

    $is_bricks_editor = function_exists('bricks_is_builder') && bricks_is_builder();
    $bricks_data = \Bricks\Database::get_data($post_id, 'content');

    if (count($bricks_data) === 0 && !$is_bricks_editor) {
        foreach ($rules as $rule) {
            if (empty($rule['template_id'])) {
                continue;
            }

            if (!empty($rule['post_types']) && !in_array(get_post_type($post_id), $rule['post_types'])) {
                continue;
            }

            if (!empty($rule['user_role']) && (!is_user_logged_in() || !current_user_can($rule['user_role']))) {
                continue;
            }

            if (!empty($rule['tax_term_ids']) && get_post_type($post_id) === 'post') {
                $post_terms = wp_get_post_terms($post_id, 'category', ['fields' => 'ids']);
                if (empty(array_intersect($post_terms, $rule['tax_term_ids']))) {
                    continue;
                }
            }

            // If we get here, all conditions are met
            $active_templates['content'] = intval($rule['template_id']);
            break;
        }
    }

    return $active_templates;
}

// Add AJAX handler for settings
function bdtc_update_settings_ajax() {
    check_ajax_referer('bdtc_update_settings', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
        return;
    }

    $enabled_types = isset($_POST['enabled_types']) ? json_decode(stripslashes($_POST['enabled_types']), true) : array('content');
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(array('message' => 'Invalid data format'));
        return;
    }

    // Always update the option
    update_option('bdtc_enabled_template_types', $enabled_types);
    
    // Send success response
    wp_send_json_success(array(
        'message' => 'Settings saved successfully',
        'enabled_types' => $enabled_types
    ));
}
add_action('wp_ajax_bdtc_update_settings', 'bdtc_update_settings_ajax');
