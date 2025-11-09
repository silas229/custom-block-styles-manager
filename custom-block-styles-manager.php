<?php

/**
 * Plugin Name: Custom Block Styles Manager
 * Description: Add custom block style variations with your own CSS.
 * Version: 1.0.0
 * Author: Silas Meyer
 * Author URI: https://github.com/silas229
 * License: GPL-2.0-or-later
 * Text Domain: custom-block-styles-manager
 *
 * @package CustomBlockStylesManager
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Custom_Block_Styles_Manager' ) ) {
    /**
     * Main plugin class for handling Block Style management.
     */
    class Custom_Block_Styles_Manager {
        const CPT = 'block_style';
        const META_BLOCK = '_cbsm_block_name';
        const META_STYLE_NAME = '_cbsm_style_name';
        const META_STYLE_SLUG = '_cbsm_style_slug';
        const META_CSS = '_cbsm_custom_css';
        const NONCE_FIELD = '_cbsm_meta_nonce';
        const CODE_EDITOR_HANDLE = 'cbsm-css-code-editor';
        const CAPABILITY = 'switch_themes';

        /**
         * Bootstrap hooks.
         */
        public static function init(): void {
            add_action( 'init', array( __CLASS__, 'register_post_type' ) );
            add_action( 'admin_menu', array( __CLASS__, 'register_admin_submenu' ) );
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
                    __CLASS__,
                    'add_plugin_action_link'
            ) );
            add_filter( 'manage_edit-' . self::CPT . '_columns', array( __CLASS__, 'add_block_type_columns' ) );
            add_action( 'manage_' . self::CPT . '_posts_custom_column', array(
                    __CLASS__,
                    'render_block_type_columns'
            ), 10, 2 );
            add_action( 'restrict_manage_posts', array( __CLASS__, 'render_block_type_filter' ) );
            add_action( 'pre_get_posts', array( __CLASS__, 'apply_block_filter' ) );
            add_action( 'quick_edit_custom_box', array( __CLASS__, 'render_quick_edit_box' ), 10, 2 );
            add_action( 'bulk_edit_custom_box', array( __CLASS__, 'render_quick_edit_box' ), 10, 2 );
            add_action( 'wp_ajax_cbsm_bulk_update_block', array( __CLASS__, 'ajax_bulk_update_block' ) );
            add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
            add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_code_editor' ) );
            add_action( 'init', array( __CLASS__, 'register_block_styles_from_cpt' ), 20 );
            add_filter( 'default_hidden_meta_boxes', array( __CLASS__, 'expose_slug_meta_box' ), 10, 2 );
        }

        /**
         * Register an admin submenu under Appearance that links to the Block Styles CPT list.
         *
         * This provides a more discoverable place for editors to manage block styles from the
         * Appearance (Design) menu.
         */
        public static function register_admin_submenu(): void {
            $parent_slug = 'themes.php';
            $menu_title  = __( 'Block Styles', 'custom-block-styles-manager' );
            $menu_slug   = 'edit.php?post_type=' . self::CPT;

            add_submenu_page( $parent_slug, $menu_title, $menu_title, self::CAPABILITY, $menu_slug );
        }

        /**
         * Add a direct action link on the Plugins page to the Block Styles overview.
         *
         * @param array $links Existing plugin action links.
         *
         * @return array Modified links.
         */
        public static function add_plugin_action_link( array $links ): array {
            // Only show to users who can edit posts (adjust capability if desired).
            if ( ! current_user_can( self::CAPABILITY ) ) {
                return $links;
            }

            $url   = admin_url( 'edit.php?post_type=' . self::CPT );
            $label = __( 'Block Styles', 'custom-block-styles-manager' );

            $link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );

            // Prepend so it's the first action link
            array_unshift( $links, $link );

            return $links;
        }

        /**
         * Register the custom post type for Block Styles.
         */
        public static function register_post_type(): void {
            $labels = array(
                    'name'               => __( 'Block Styles', 'custom-block-styles-manager' ),
                    'singular_name'      => __( 'Block Style', 'custom-block-styles-manager' ),
                    'add_new'            => __( 'Add New', 'custom-block-styles-manager' ),
                    'add_new_item'       => __( 'Add New Block Style', 'custom-block-styles-manager' ),
                    'edit_item'          => __( 'Edit Block Style', 'custom-block-styles-manager' ),
                    'new_item'           => __( 'New Block Style', 'custom-block-styles-manager' ),
                    'all_items'          => __( 'All Block Styles', 'custom-block-styles-manager' ),
                    'attributes'         => __( 'Block Style Attributes', 'custom-block-styles-manager' ),
                    'not_found'          => __( 'No block styles found.', 'custom-block-styles-manager' ),
                    'not_found_in_trash' => __( 'No block styles found in Trash.', 'custom-block-styles-manager' ),
                    'menu_name'          => __( 'Block Styles', 'custom-block-styles-manager' ),
            );

            $args = array(
                    'labels'              => $labels,
                    'public'              => false,
                    'show_ui'             => true,
                    'show_in_menu'        => false,
                    'show_in_rest'        => false,
                    'supports'            => array( 'title' ),
                    'capability_type'     => 'post',
                    'has_archive'         => false,
                    'hierarchical'        => false,
                    'menu_icon'           => 'dashicons-art',
                    'publicly_queryable'  => false,
                    'exclude_from_search' => true,
                    'rewrite'             => false,
            );

            register_post_type( self::CPT, $args );
        }

        /**
         * Register meta boxes for selecting block, defining style labels and custom CSS.
         *
         * @param string $post_type Current admin post type.
         */
        public static function register_meta_boxes( string $post_type ): void {
            if ( self::CPT !== $post_type ) {
                return;
            }

            add_meta_box(
                    'cbsm-block-style-details',
                    __( 'Block Style Settings', 'custom-block-styles-manager' ),
                    array( __CLASS__, 'render_details_meta_box' ),
                    $post_type,
                    'normal',
                    'high'
            );

            /** @noinspection PhpRedundantOptionalArgumentInspection */
            add_meta_box(
                    'cbsm-block-style-css',
                    __( 'Custom CSS', 'custom-block-styles-manager' ),
                    array( __CLASS__, 'render_css_meta_box' ),
                    $post_type,
                    'normal',
                    'default'
            );
        }

        /**
         * Render the settings meta box.
         *
         * @param WP_Post $post Current post object.
         */
        public static function render_details_meta_box( \WP_Post $post ): void {
            wp_nonce_field( 'cbsm_save_meta', self::NONCE_FIELD );

            $current_block     = get_post_meta( $post->ID, self::META_BLOCK, true );
            $registered_blocks = self::get_registered_blocks();
            ?>
            <p>
                <label
                        for="cbsm-block-selector"><strong><?php esc_html_e( 'Target Block', 'custom-block-styles-manager' ); ?></strong></label><br/>
                <select name="cbsm_block_name" id="cbsm-block-selector" class="widefat">
                    <option
                            value=""><?php esc_html_e( 'Select a block', 'custom-block-styles-manager' ); ?></option>
                    <?php foreach ( $registered_blocks as $block_name => $label ) : ?>
                        <option
                                value="<?php echo esc_attr( $block_name ); ?>" <?php selected( $current_block, $block_name ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <?php
        }

        /**
         * Render the CSS meta box with a CodeMirror editor.
         *
         * @param WP_Post $post Current post object.
         */
        public static function render_css_meta_box( \WP_Post $post ): void {
            $css        = get_post_meta( $post->ID, self::META_CSS, true );
            $style_slug = self::resolve_style_slug_from_post( $post );
            $css        = self::maybe_prefill_css( $style_slug, $css );
            ?>
            <p>
                <?php
                $code_tag = '<code id="cbsm-style-class-preview">' . ( $style_slug ? 'is-style-' . sanitize_html_class( $style_slug ) : 'is-style-{slug}' ) . '</code>';

                echo wp_kses( sprintf(
                /* translators: %1$s is replaced with the code tag (renders the actual class in the editor, e.g. <code>is-style-{slug}</code>) */
                        __( 'Add CSS that will be applied when this style is selected. While editing, the block receives the class %1$s.', 'custom-block-styles-manager' ),
                        $code_tag
                ), array(
                        'code' => array(
                                'id' => true,
                        ),
                ) );
                ?>
            </p>
            <label for="cbsm-custom-css"
                   class="screen-reader-text"><?php esc_html_e( 'Custom CSS for this style', 'custom-block-styles-manager' ); ?></label>
            <textarea name="cbsm_custom_css" id="cbsm-custom-css" rows="12" class="widefat"
                      style="font-family: monospace;"><?php echo esc_textarea( $css ); ?></textarea>
            <?php
        }

        /**
         * Save meta values for the custom post type.
         *
         * @param int $post_id Saved post ID.
         * @param WP_Post $post Post object.
         */
        public static function save_meta( int $post_id, \WP_Post $post ): void {
            if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), 'cbsm_save_meta' ) ) {
                return;
            }

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( self::CAPABILITY ) ) {
                return;
            }

            $block_name = isset( $_POST['cbsm_block_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cbsm_block_name'] ) ) : '';
            $css_raw    = isset( $_POST['cbsm_custom_css'] ) ? wp_unslash( $_POST['cbsm_custom_css'] ) : '';

            $registered_blocks = self::get_registered_blocks();
            if ( $block_name && ! array_key_exists( $block_name, $registered_blocks ) ) {
                $block_name = '';
            }

            $style_name  = trim( self::resolve_style_name_from_post( $post ) );
            $style_slug  = self::resolve_style_slug_from_post( $post );
            $css_prefill = self::maybe_prefill_css( $style_slug, $css_raw );
            $css         = self::sanitize_css( $css_prefill );

            update_post_meta( $post_id, self::META_BLOCK, $block_name );
            update_post_meta( $post_id, self::META_STYLE_NAME, $style_name );
            update_post_meta( $post_id, self::META_STYLE_SLUG, $style_slug );
            update_post_meta( $post_id, self::META_CSS, $css );
        }

        /**
         * Enqueue the CodeMirror editor for CSS editing when editing Block Style CPT.
         *
         * @param string $hook Current admin page hook suffix.
         */
        public static function enqueue_code_editor( string $hook ): void {
            global $typenow;
            // Code editor for single-post edit screens (post.php, post-new.php)
            if ( in_array( $hook, array( 'post-new.php', 'post.php' ), true ) && self::CPT === $typenow ) {
                $settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

                if ( false !== $settings ) {
                    wp_enqueue_script( 'code-editor' );
                    wp_enqueue_style( 'code-editor' );
                    $script_path = plugin_dir_path( __FILE__ ) . 'assets/js/admin-block-styles.js';
                    $script_url  = plugins_url( 'assets/js/admin-block-styles.js', __FILE__ );
                    $ver         = file_exists( $script_path ) ? filemtime( $script_path ) : false;

                    wp_register_script(
                            self::CODE_EDITOR_HANDLE,
                            $script_url,
                            array( 'code-editor', 'jquery' ),
                            $ver,
                            true
                    );

                    // Pass the code editor settings to the external script
                    wp_localize_script( self::CODE_EDITOR_HANDLE, 'cbsmCodeEditorSettings', array( 'settings' => $settings ) );
                    wp_enqueue_script( self::CODE_EDITOR_HANDLE );
                }
            }

            // Enqueue admin scripts for the post list screen (quick / bulk edit support).
            if ( 'edit.php' === $hook && self::CPT === $typenow ) {
                $script_path = plugin_dir_path( __FILE__ ) . 'assets/js/admin-block-styles.js';
                $script_url  = plugins_url( 'assets/js/admin-block-styles.js', __FILE__ );
                $ver         = file_exists( $script_path ) ? filemtime( $script_path ) : false;

                wp_register_script(
                        'cbsm-admin-list',
                        $script_url,
                        array( 'jquery' ),
                        $ver,
                        true
                );

                // Provide registered blocks and a nonce for bulk actions.
                $registered = self::get_registered_blocks();
                wp_localize_script( 'cbsm-admin-list', 'cbsmAdminListData', array(
                        'blocks'    => $registered,
                        'bulkNonce' => wp_create_nonce( 'cbsm_bulk_nonce' ),
                ) );

                wp_enqueue_script( 'cbsm-admin-list' );
            }
        }

        /**
         * Add "Block" and "CSS class" columns to the block styles list table.
         */
        public static function add_block_type_columns( $columns ): array {
            $new = array();
            foreach ( $columns as $key => $label ) {
                $new[ $key ] = $label;
                if ( 'title' === $key ) {
                    $new['cbsm_block_type'] = __( 'Block', 'custom-block-styles-manager' );
                    // Add a column that displays the generated CSS class for the style (is-style-{slug}).
                    $new['cbsm_style_class'] = __( 'CSS class', 'custom-block-styles-manager' );
                }
            }

            // Fallback: ensure columns exist
            if ( ! isset( $new['cbsm_block_type'] ) ) {
                $new['cbsm_block_type'] = __( 'Block', 'custom-block-styles-manager' );
            }
            if ( ! isset( $new['cbsm_style_class'] ) ) {
                $new['cbsm_style_class'] = __( 'CSS class', 'custom-block-styles-manager' );
            }

            return $new;
        }

        /**
         * Render the content for custom columns (both block type and style class).
         *
         * Handles the following column identifiers:
         * - cbsm_block_type: shows the human-readable target block
         * - cbsm_style_class: shows the generated CSS class (is-style-{slug}) for the style
         *
         * @param string $column Column identifier.
         * @param int $post_id Current post ID.
         */
        public static function render_block_type_columns( string $column, int $post_id ): void {
            $post = get_post( $post_id );

            if ( 'cbsm_block_type' === $column ) {
                $block_name = get_post_meta( $post_id, self::META_BLOCK, true );
                $blocks     = self::get_registered_blocks();
                $label      = $block_name && isset( $blocks[ $block_name ] ) ? $blocks[ $block_name ] : $block_name;

                if ( ! $label ) {
                    echo '—';
                } else {
                    echo esc_html( $label );
                }

                // Include inline data for quick edit population
                echo '<span class="cbsm-inline-block-data" style="display:none" data-block="' . esc_attr( $block_name ) . '"></span>';
            }

            if ( 'cbsm_style_class' === $column ) {
                $style_slug = '';
                if ( $post instanceof WP_Post ) {
                    $style_slug = self::resolve_style_slug_from_post( $post );
                }

                if ( ! $style_slug ) {
                    echo '—';
                } else {
                    $class = 'is-style-' . sanitize_html_class( $style_slug );
                    echo '<code class="cbsm-style-class-preview">' . esc_html( $class ) . '</code>';
                }

                // Provide inline data for potential JS (quick-edit / bulk-edit integrations).
                echo '<span class="cbsm-inline-style-data" style="display:none" data-slug="' . esc_attr( $style_slug ) . '"></span>';
            }
        }

        /**
         * Render a filter dropdown above the list table to filter by block type.
         */
        public static function render_block_type_filter(): void {
            global $typenow;
            if ( self::CPT !== $typenow ) {
                return;
            }
            global $wpdb;
            $meta_key  = self::META_BLOCK;
            $post_type = self::CPT;

            $used_raw = $wpdb->get_col( $wpdb->prepare(
                    "SELECT DISTINCT pm.meta_value
         FROM $wpdb->postmeta pm
         JOIN $wpdb->posts p ON pm.post_id = p.ID
         WHERE pm.meta_key = %s
           AND p.post_type = %s
           AND p.post_status = 'publish'",
                    $meta_key,
                    $post_type
            ) );

            $registered = self::get_registered_blocks();
            $used       = array();
            if ( ! empty( $used_raw ) ) {
                foreach ( $used_raw as $val ) {
                    if ( ! $val ) {
                        continue;
                    }
                    if ( isset( $registered[ $val ] ) ) {
                        $used[ $val ] = $registered[ $val ];
                    } else {
                        // If the block is no longer registered, still show its raw value
                        $used[ $val ] = $val;
                    }
                }
            }

            if ( empty( $used ) ) {
                return;
            }

            natcasesort( $used );

            $current = isset( $_GET['cbsm_block_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['cbsm_block_filter'] ) ) : '';

            echo '<select name="cbsm_block_filter" id="cbsm-block-filter">';
            echo '<option value="">' . esc_html__( 'All blocks', 'custom-block-styles-manager' ) . '</option>';
            foreach ( $used as $name => $label ) {
                /** @noinspection HtmlUnknownAttribute */
                printf( '<option value="%s" %s>%s</option>', esc_attr( $name ), selected( $current, $name, false ), esc_html( $label ) );
            }
            echo '</select>';
        }

        /**
         * Apply the block filter to the list query.
         */
        public static function apply_block_filter( $query ): void {
            global $pagenow;

            if ( ! is_admin() || 'edit.php' !== $pagenow ) {
                return;
            }

            $post_type = $query->get( 'post_type' );
            if ( $post_type !== self::CPT ) {
                return;
            }

            $filter = isset( $_GET['cbsm_block_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['cbsm_block_filter'] ) ) : '';
            if ( $filter ) {
                $query->set( 'meta_key', self::META_BLOCK );
                $query->set( 'meta_value', $filter );
            }
        }

        /**
         * Output quick edit or bulk edit form bits for block selection.
         */
        public static function render_quick_edit_box( $column_name, $post_type ): void {
            if ( $post_type !== self::CPT || $column_name !== 'cbsm_block_type' ) {
                return;
            }

            $blocks = self::get_registered_blocks();
            ?>
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label>
                        <span class="title"><?php esc_html_e( 'Block', 'custom-block-styles-manager' ); ?></span>
                        <select name="cbsm_block_name">
                            <option value=""><?php /* translators: No change option in bulk edit */
                                esc_html_e( '— No change —', 'custom-block-styles-manager' ); ?></option>
                            <?php foreach ( $blocks as $name => $label ) : ?>
                                <option
                                        value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </fieldset>
            <?php
        }

        /**
         * Handle AJAX bulk update to set the block meta on multiple posts.
         */
        public static function ajax_bulk_update_block(): void {
            if ( ! current_user_can( self::CAPABILITY ) ) {
                wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
            }

            check_ajax_referer( 'cbsm_bulk_nonce', 'nonce' );

            $ids   = isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ? array_map( 'intval', wp_unslash( $_POST['post_ids'] ) ) : array();
            $block = isset( $_POST['block'] ) ? sanitize_text_field( wp_unslash( $_POST['block'] ) ) : '';

            $registered = self::get_registered_blocks();
            if ( $block && ! array_key_exists( $block, $registered ) ) {
                wp_send_json_error( array( 'message' => 'invalid_block' ), 400 );
            }

            foreach ( $ids as $id ) {
                if ( ! current_user_can( 'edit_post', $id ) ) {
                    continue;
                }
                update_post_meta( $id, self::META_BLOCK, $block );
            }

            wp_send_json_success();
        }

        /**
         * Register block styles for each published Block Style CPT.
         */
        public static function register_block_styles_from_cpt(): void {
            if ( ! function_exists( 'register_block_style' ) ) {
                return;
            }

            $styles = get_posts(
                    array(
                            'post_type'      => self::CPT,
                            'post_status'    => 'publish',
                            'posts_per_page' => - 1,
                    )
            );

            if ( empty( $styles ) ) {
                return;
            }

            $block_registry = WP_Block_Type_Registry::get_instance();

            foreach ( $styles as $style_post ) {
                $block_name = get_post_meta( $style_post->ID, self::META_BLOCK, true );

                if ( empty( $block_name ) ) {
                    continue;
                }

                if ( ! $block_registry->is_registered( $block_name ) ) {
                    continue;
                }

                $style_slug = self::resolve_style_slug_from_post( $style_post );

                if ( empty( $style_slug ) ) {
                    continue;
                }

                $style_name = self::resolve_style_name_from_post( $style_post );
                $css_raw    = get_post_meta( $style_post->ID, self::META_CSS, true );
                $css        = self::sanitize_css( self::maybe_prefill_css( $style_slug, $css_raw ) );

                $style_args = array(
                        'name'         => $style_slug,
                        'label'        => $style_name ?: ( $style_post->post_title ?: $style_slug ),
                        'inline_style' => $css,
                );

                register_block_style( $block_name, $style_args );
            }
        }

        /**
         * Retrieve registered blocks with human-readable labels.
         *
         * @return array<string, string> Array of block name => label.
         */
        protected static function get_registered_blocks(): array {
            $registry = WP_Block_Type_Registry::get_instance();
            $blocks   = $registry->get_all_registered();
            $options  = array();

            foreach ( $blocks as $block_name => $block_type ) {
                if ( ! $block_type instanceof WP_Block_Type ) {
                    continue;
                }

                $label                  = $block_type->title ?: $block_name;
                $options[ $block_name ] = $label;
            }

            natcasesort( $options );

            return $options;
        }

        /**
         * Resolve the style name from the post title.
         *
         * @param WP_Post $post Post object.
         *
         * @return string
         */
        protected static function resolve_style_name_from_post( \WP_Post $post ): string {
            if ( $post->post_title ) {
                return $post->post_title;
            }

            $meta_name = get_post_meta( $post->ID, self::META_STYLE_NAME, true );
            if ( $meta_name ) {
                return $meta_name;
            }

            return '';
        }

        /**
         * Resolve the style slug from the post slug.
         *
         * @param WP_Post $post Post object.
         *
         * @return string
         */
        protected static function resolve_style_slug_from_post( \WP_Post $post ): string {
            $meta_slug = get_post_meta( $post->ID, self::META_STYLE_SLUG, true );
            $slug      = $post->post_name ?: $meta_slug ?: ( $post->post_title ? sanitize_title( $post->post_title ) : '' );

            return sanitize_title( (string) $slug );
        }

        /**
         * Insert a default selector when CSS content is empty.
         *
         * @param string $style_slug Style slug.
         * @param string $css Raw CSS input.
         *
         * @return string
         */
        protected static function maybe_prefill_css( string $style_slug, string $css ): string {
            if ( $style_slug && '' === trim( $css ) ) {
                $slug_class = sanitize_html_class( $style_slug );
                if ( $slug_class ) {
                    return ".is-style-$slug_class {\n\n}\n";
                }
            }

            return $css;
        }

        /**
         * Ensure the slug meta box is visible by default for the CPT.
         *
         * @param array $hidden Array of hidden meta boxes.
         * @param WP_Screen|mixed $screen Current screen instance or identifier.
         *
         * @return array
         */
        public static function expose_slug_meta_box( array $hidden, mixed $screen ): array {
            if ( $screen instanceof WP_Screen && self::CPT === $screen->post_type ) {
                $hidden = array_diff( $hidden, array( 'slugdiv' ) );
            }

            return $hidden;
        }

        /**
         * Basic CSS sanitization to strip disallowed markup.
         *
         * @param string $css Raw CSS.
         *
         * @return string Sanitized CSS.
         */
        protected static function sanitize_css( string $css ): string {
            return trim( wp_kses( $css, array() ) );
        }
    }

    Custom_Block_Styles_Manager::init();
}
