<?php
/*
Plugin Name: Bricks Dynamic Template Control
Description: Applies a fallback Bricks template to selected post types not built with Bricks. Includes admin settings.
Version: 1.1
Author: Luis Ameglio
*/

add_action( 'admin_menu', 'bdtc_add_admin_menu' );
add_action( 'admin_init', 'bdtc_settings_init' );

function bdtc_add_admin_menu() {
    add_options_page(
        'Bricks Template Control',
        'Bricks Template Control',
        'manage_options',
        'bricks_template_control',
        'bdtc_options_page'
    );
}

function bdtc_settings_init() {
    register_setting( 'bdtc_settings_group', 'bdtc_template_id' );
    register_setting( 'bdtc_settings_group', 'bdtc_post_types' );
    register_setting( 'bdtc_settings_group', 'bdtc_user_role' );
    register_setting( 'bdtc_settings_group', 'bdtc_tax_term_ids' );

    add_settings_section(
        'bdtc_settings_section',
        'Template Assignment',
        function() {
            echo '<p>Assign a fallback Bricks template based on post type, user role, and category.</p>';
        },
        'bricks_template_control'
    );

    add_settings_field(
        'bdtc_template_id',
        'Fallback Template',
        'bdtc_template_dropdown',
        'bricks_template_control',
        'bdtc_settings_section'
    );

    add_settings_field(
        'bdtc_post_types',
        'Apply to Post Types',
        'bdtc_post_types_multiselect',
        'bricks_template_control',
        'bdtc_settings_section'
    );

    add_settings_field(
        'bdtc_user_role',
        'User Role Condition',
        'bdtc_user_role_select',
        'bricks_template_control',
        'bdtc_settings_section'
    );

    add_settings_field(
        'bdtc_tax_term_ids',
        'Apply to Categories / Terms',
        'bdtc_tax_terms_multiselect',
        'bricks_template_control',
        'bdtc_settings_section'
    );
}

function bdtc_template_dropdown() {
    $selected = get_option( 'bdtc_template_id' );
    $templates = get_posts([
        'post_type' => 'bricks_template',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);

    echo '<select name="bdtc_template_id">';
    echo '<option value="">— Select a template —</option>';
    foreach ( $templates as $template ) {
        printf(
            '<option value="%d"%s>%s (ID: %d)</option>',
            $template->ID,
            selected( $selected, $template->ID, false ),
            esc_html( $template->post_title ),
            $template->ID
        );
    }
    echo '</select>';
}

function bdtc_post_types_multiselect() {
    $selected = (array) get_option( 'bdtc_post_types', [] );
    $post_types = get_post_types( [ 'public' => true ], 'objects' );

    echo '<select name="bdtc_post_types[]" multiple style="height: 100px;">';
    foreach ( $post_types as $pt ) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr( $pt->name ),
            in_array( $pt->name, $selected ) ? ' selected' : '',
            esc_html( $pt->label )
        );
    }
    echo '</select><br><small>Hold CTRL (or CMD) to select multiple.</small>';
}

function bdtc_user_role_select() {
    $selected = get_option( 'bdtc_user_role', '' );
    global $wp_roles;

    echo '<select name="bdtc_user_role">';
    echo '<option value="">— All Users —</option>';
    foreach ( $wp_roles->roles as $role => $details ) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr( $role ),
            selected( $selected, $role, false ),
            esc_html( $details['name'] )
        );
    }
    echo '</select>';
}

function bdtc_tax_terms_multiselect() {
    $selected = (array) get_option( 'bdtc_tax_term_ids', [] );
    $terms = get_terms( [
        'taxonomy' => 'category',
        'hide_empty' => false,
    ] );

    echo '<select name="bdtc_tax_term_ids[]" multiple style="height: 100px;">';
    foreach ( $terms as $term ) {
        printf(
            '<option value="%d"%s>%s</option>',
            $term->term_id,
            in_array( $term->term_id, $selected ) ? ' selected' : '',
            esc_html( $term->name )
        );
    }
    echo '</select><br><small>Select categories the fallback should apply to (posts only).</small>';
}

add_filter( 'bricks/active_templates', 'bdtc_set_active_templates', 10, 3 );

function bdtc_set_active_templates( $active_templates, $post_id, $content_type ) {
    if ( $content_type !== 'content' ) return $active_templates;

    $template_id = get_option( 'bdtc_template_id' );
    $enabled_post_types = (array) get_option( 'bdtc_post_types', [] );
    $required_role = get_option( 'bdtc_user_role', '' );
    $required_terms = (array) get_option( 'bdtc_tax_term_ids', [] );

    if ( ! $template_id || ! in_array( get_post_type( $post_id ), $enabled_post_types ) ) {
        return $active_templates;
    }

    if ( $required_role && ( ! is_user_logged_in() || ! current_user_can( $required_role ) ) ) {
        return $active_templates;
    }

    if ( ! empty( $required_terms ) && get_post_type( $post_id ) === 'post' ) {
        $post_terms = wp_get_post_terms( $post_id, 'category', [ 'fields' => 'ids' ] );
        if ( empty( array_intersect( $post_terms, $required_terms ) ) ) {
            return $active_templates;
        }
    }

    $is_bricks_editor = function_exists('bricks_is_builder') && bricks_is_builder();
    $bricks_data = \Bricks\Database::get_data( $post_id, 'content' );

    if ( count( $bricks_data ) === 0 && ! $is_bricks_editor ) {
        $active_templates['content'] = intval( $template_id );
    }

    return $active_templates;
}

function bdtc_options_page() {
    ?>
    <div class="wrap">
        <h1>Bricks Dynamic Template Control</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'bdtc_settings_group' );
            do_settings_sections( 'bricks_template_control' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
