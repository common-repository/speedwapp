<?php

/*
 * This file is part of the Speedwapp Wordpress plugin.
 *
 * (c) Akambi Fagbohoun <contact@akambi-fagbohoun.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

class Util
{
    /**
     * @return mixed|void Are we currently viewing the home page
     */
    public static function is_home()
    {
        $home = (
            is_front_page()
            && is_page()
            && get_option('show_on_front') == 'page'
            && get_option('page_on_front') == get_the_ID()
            && get_post_meta(get_the_ID(), 'page_data')
        );

        return apply_filters('wp_speedwapp_is_home', $home);
    }

    /**
     * Check if we manage the current page.
     *
     * @param bool $can_edit Also check if the user can edit this page
     *
     * @return bool
     */
    public static function is_page($can_edit = false)
    {
        // Check if this is a page
        $is_page = (self::is_home()
            || (is_singular() && get_post_meta(get_the_ID(), 'speedwapp_page_data', false))
        );

        return $is_page && (!$can_edit || ((is_singular() && current_user_can('edit_post', get_the_ID())) || (wp_speedwapp_is_home() && current_user_can('edit_theme_options'))));
    }

    /**
     * Check if we should load the WP_Speedwapp scripts and styles.
     *
     * @return mixed|void
     */
    public static function is_admin_page()
    {
        $screen = get_current_screen();
        $is_speedwapp_page = ($screen->base == 'post' && in_array($screen->id, array('post', 'page')))
            || $screen->base == 'appearance_page_so_page_home_page'
            || $screen->base == 'widgets'
            || $screen->base == 'customize'
            || $screen->base == 'dashboard'
            || $screen->base == 'plugins';

        return apply_filters('wp_speedwapp_is_admin_page', $is_speedwapp_page);
    }

    /**
     * Is this a preview.
     * test si lediteur en previsualisation.
     *
     * @return bool
     */
    public static function is_preview()
    {
        global $wp_speedwapp_is_preview;

        return (bool) $wp_speedwapp_is_preview;
    }

    /**
     * check if block editor (gutenberg) is active
     *
     * @return bool
     */
    public static function is_gutenberg_active()
    {
        if ( function_exists( 'is_gutenberg_page' ) &&
            is_gutenberg_page()
        ) {
            // The Gutenberg plugin is on.
            return true;
        }
        $current_screen = get_current_screen();
        if ( method_exists( $current_screen, 'is_block_editor' ) &&
            $current_screen->is_block_editor()
        ) {
            // Gutenberg page on 5+.
            return true;
        }
        return false;
    }

    public static function get_the_main_ID($id = 0) {
        if ($id) {
            $post = get_post($id);
        } else {
            $post = get_post();
        }

        if ( ! $post ) {
            return false;
        }

        if ( 'revision' === $post->post_type ) {
            $post_id = (int) $post->post_parent;
        } else {
            $post_id = $post->ID;
        }

		return $post_id;
    }

    public static function init_global_post($id = 0) {
        global $post;
        $post = get_post($id, OBJECT);
        if (!$post) {
            return;
        }

        setup_postdata($post);
    }

    public static function is_current_user_can_edit_post_type( $post_type ) {

        if ( ! post_type_exists( $post_type ) ) {
			return false;
		}

        $post_type_object = get_post_type_object( $post_type );

        if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
			return false;
		}

		return true;
    }

    public static function createNewPost( $post_type, $postarr = [], $metas = [] ) {

        $should_update_title = false;
        if ( ! array_key_exists( 'post_title', $postarr ) ) {
            $postarr[ 'post_title' ] = __( 'Speedwapp', 'speedwapp');
            $should_update_title = true;
        }

        $postarr[ 'post_content' ] = '<p>'
            . __("I'm a paragraph. Just double click me to add your own text.", 'speedwapp')
            . '</p>';

        $metas['_is_speedwapp_editor_active'] = true;

        $postarr['meta_input'] = $metas;

        $post_id = wp_insert_post( $postarr, false );

		if ( $should_update_title ) {
			$postarr['ID'] = $post_id;
			$postarr['post_title'] .= " # $post_id";

            // There is no need to update the meta values
            unset( $postarr['meta_input'] );

			wp_update_post( $postarr );
		}

        return $post_id;
    }

    public static function set_speedwapp_editor_active($post_id, $is_speedwapp_editor_active = false) {
        if (!$post_id) {
            return;
        }

        update_post_meta($post_id, '_is_speedwapp_editor_active', $is_speedwapp_editor_active);
    }

    public static function is_speedwapp_editor_active($post_id = 0) {
        return !!get_post_meta( $post_id, '_is_speedwapp_editor_active', true );
    }

    public static function get_new_post_link($post_type = 'page') {
        $link = add_query_arg( array(
            'action' => 'new_with_speedwapp',
            'post_type' => $post_type,
            'new_nonce' => wp_create_nonce( 'new_with_speedwapp' ),
        ), admin_url( 'edit.php' ) );

        return apply_filters( 'new_with_speedwapp_link', $link );
    }

    public static function get_edit_post_link($post_id = 0) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return;
        }

        $action = '&action=edit_with_speedwapp';

        $post_type_object = get_post_type_object( $post->post_type );
        if ( ! $post_type_object ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return;
        }

        if ( $post_type_object->_edit_link ) {
            $link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
        } else {
            $link = '';
        }

        /**
         * Filters the post edit link.
         *
         * @param string $link    The edit link.
         * @param int    $post_id Post ID.
         * @param string $context The link context. If set to 'display' then ampersands
         *                        are encoded.
         */
        return apply_filters( 'speedwapp_get_edit_post_link', $link, $post->ID );
    }
}
