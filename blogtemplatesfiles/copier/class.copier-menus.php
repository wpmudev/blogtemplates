<?php

class NBT_Template_Copier_Menus extends NBT_Template_Copier {

	public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
	}

	public function get_default_args() {
		return array();
	}

	public function copy() {
		global $wpdb;

        do_action( 'blog_templates-copying_menu', $this->source_blog_id, get_current_blog_id() );

        // Get the source menus and their menu items
        switch_to_blog( $this->source_blog_id );
        $source_menus = wp_get_nav_menus();

        
        foreach ( $source_menus as $key => $source_menu )
            $source_menus[ $key ]->items = wp_get_nav_menu_items( $source_menu->term_id );
        
        $source_site_url = home_url();
        $source_menu_locations = get_nav_menu_locations();
        $source_menu_options = get_option( 'nav_menu_options' );
        restore_current_blog();

        // First, let's delete the current menus that the new site has already, just in case
        $current_menus = wp_get_nav_menus();
        foreach ( $current_menus as $menu )
            $deletion = wp_delete_nav_menu( $menu->term_id );

        // Array that saves relationships to remap parents later
        $menu_remap = array();
        $menu_items_remap = array();

        // Now copy menus
        foreach ( $source_menus as $source_menu ) {
            // Create a new menu object
            $menu_args = array(
                'menu-name' => $source_menu->name,
                'description' => $source_menu->description
            );
            $new_menu_id = wp_update_nav_menu_object( 0, $menu_args );

            $menu_remap[ $source_menu->term_id ] = $new_menu_id;

            foreach ( $source_menu->items as $menu_item ) {

                $new_item_args = array(
                    'menu-item-object' => $menu_item->object,
                    'menu-item-type' => $menu_item->type,
                    'menu-item-title' => $menu_item->title,
                    'menu-item-description' => $menu_item->description,
                    'menu-item-attr-title' => $menu_item->attr_title,
                    'menu-item-target' => $menu_item->target,
                    'menu-item-classes' => $menu_item->classes,
                    'menu-item-xfn' => $menu_item->xfn,
                    'menu-item-status' => $menu_item->post_status,
                );


                if ( 'custom' != $menu_item->type ) {
                    // If not custom, try to link the real object (post/page/whatever)
                    if ( 'post_type' == $new_item_args['menu-item-type'] ) {
                        // The posts IDs are the same  than in the source blog if they have been copied
                        $new_item_args['menu-item-object-id'] = $menu_item->object_id;
                    }
                    elseif ( 'taxonomy' == $new_item_args['menu-item-type'] ) {
                        // Let's grab the source term slug. We might have copied it, who knows?
                        switch_to_blog( $this->source_blog_id );
                        $term = get_term( $menu_item->object_id, $menu_item->object );
                        restore_current_blog();

                        if ( ! $term )
                            continue;

                        $new_blog_term = get_term_by( 'slug', $term->slug, $menu_item->object );

                        if ( ! $new_blog_term )
                            continue;

                        // We have found the term in the new blog
                        $new_item_args['menu-item-object-id'] = $new_blog_term->term_id;

                    }
                }
                else {
                    $new_item_args['menu-item-url'] = str_replace( $source_site_url, home_url(), $menu_item->url );
                }

                $new_menu_item_id = wp_update_nav_menu_item( $new_menu_id, 0, $new_item_args );

                $menu_items_remap[ $menu_item->ID ] = $new_menu_item_id;
            }

            // Now remap parents
            foreach ( $source_menus as $source_menu ) {
                if ( ! isset( $menu_remap[ $source_menu->term_id ] ) )
                    continue;

                $items = wp_get_nav_menu_items( $menu_remap[ $source_menu->term_id ], 'nav_menu' );

                foreach ( $source_menu->items as $source_menu_item ) {
                    

                    if ( empty( $source_menu_item->menu_item_parent ) )
                        continue;

                    // Search the new menu item that is mapped to the source menu item
                    $item_correspondence = false;
                    foreach ( $items as $item ) {
                        if ( $item->ID == $menu_items_remap[ $source_menu_item->ID ] )
                            $item_correspondence = $item;
                    }
                    
                    if ( ! $item_correspondence )
                        continue;

                    if ( ! isset( $menu_items_remap[ $source_menu_item->menu_item_parent ] ) )
                        continue;

                    $item_args = array(
                        'menu-item-object-id' => $item_correspondence->object_id,
                        'menu-item-object' => $item_correspondence->object,
                        'menu-item-type' => $item_correspondence->type,
                        'menu-item-title' => $item_correspondence->title,
                        'menu-item-description' => $item_correspondence->description,
                        'menu-item-attr-title' => $item_correspondence->attr_title,
                        'menu-item-target' => $item_correspondence->target,
                        'menu-item-classes' => $item_correspondence->classes,
                        'menu-item-xfn' => $item_correspondence->xfn,
                        'menu-item-status' => $item_correspondence->post_status,
                        'menu-item-parent-id' => $menu_items_remap[ $source_menu_item->menu_item_parent ],
                    );

                    wp_update_nav_menu_item( $menu_remap[ $source_menu->term_id ], $item_correspondence->db_id, $item_args );
                }
            }

            // Set menu locations
            $new_menu_locations = array();
            foreach ( $source_menu_locations as $menu => $menu_id ) {
                if ( ! isset( $menu_remap[ $menu_id ] ) )
                    continue;

                $new_menu_locations[ $menu ] = $menu_remap[ $menu_id ];
            }

            set_theme_mod( 'nav_menu_locations', $new_menu_locations );

            // Set menu options
            $menu_options = $source_menu_options;
            if ( isset( $source_menu_options['auto_add'] ) ) {
                foreach ( $source_menu_options['auto_add'] as $key => $menu_id ) {
                    if ( ! isset( $menu_remap[ $menu_id ] ) )
                        continue;

                    $menu_options['auto_add'][ $key ] = $menu_remap[ $menu_id ];
                }
            }

            update_option( 'nav_menu_options', $menu_options );


        }

        return true;
	}




	

}