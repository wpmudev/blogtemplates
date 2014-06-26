<?php

function nbt_get_model() {
	return blog_templates_model::get_instance();
}

function get_settings_handler() {
	return NBT_Plugin_Settings_Handler::get_instance();
}

function nbt_get_settings() {
	$handler = get_settings_handler();
	return $handler->get_settings();
}

function nbt_update_settings( $new_settings ) {
	$handler = get_settings_handler();
	$handler->update_settings( $new_settings );
}

function nbt_get_default_settings() {
	$handler = get_settings_handler();
	return $handler->get_default_settings();
}

function nbt_theme_selection_toolbar( $templates ) {
    require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates_theme_selection_toolbar.php' );
	$settings = nbt_get_settings();
	$toolbar = new blog_templates_theme_selection_toolbar( $settings['registration-templates-appearance'] );
	$toolbar->display();
	$category_id = $toolbar->default_category_id; 

	if ( $category_id !== 0 ) {
		$model = nbt_get_model();
		$templates = $model->get_templates_by_category( $category_id );
	}

	return $templates;
}

function nbt_get_template_selection_types() {
	return apply_filters( 'nbt_get_template_selection_types', array(
        0 => __('As simple selection box', 'blog_templates'),
        'description' => __('As radio-box selection with descriptions', 'blog_templates'),
        'screenshot' => __('As theme screenshot selection', 'blog_templates'),
        'screenshot_plus' => __('As theme screenshot selection with titles and description', 'blog_templates'),
        'previewer' => __('As a theme previewer', 'blog_templates'),
        'page_showcase' => __('As a showcase inside a page', 'blog_templates')
    ) );
}

/**
 * Get a list of WordPress DB tables in a blog (not the default ones)
 * 
 * @param Integer $blog_id 
 * @return Array of tables attributes:
 		Array(
			'name' => Table name
			'prefix.name' => Table name and Database if MultiDB is activated. Same than 'name' in other case.
 		)
 */
function nbt_get_additional_tables( $blog_id ) {
	global $wpdb;

	$blog_id = absint( $blog_id );
	$blog_details = get_blog_details( $blog_id );

	if ( ! $blog_details )
		return array();


	switch_to_blog( $blog_id );
	
	// MultiDB Plugin hack
    $pfx = class_exists( "m_wpdb" ) ? $wpdb->prefix : str_replace( '_', '\_', $wpdb->prefix );

    // Get all the tables for that blog
    $results = $wpdb->get_results("SHOW TABLES LIKE '{$pfx}%'", ARRAY_N);

    $default_tables = array( 'posts', 'comments', 'links', 'options', 'postmeta', 'terms', 'term_taxonomy', 'term_relationships', 'commentmeta' );

    $tables = array();
    if ( ! empty( $results ) ) {
    	foreach ( $results as $result ) {
    		if ( ! in_array( str_replace( $wpdb->prefix, '', $result['0'] ), $default_tables ) ) {
    			if ( class_exists( 'm_wpdb' ) ) {
    				// MultiDB Plugin
                    $db = $wpdb->analyze_query( "SHOW TABLES LIKE '{$pfx}%'" );
                    $dataset = $db['dataset'];
                    $current_db = '';

                    foreach ( $wpdb->dbh_connections as $connection ) {
                    	if ( $connection['ds'] == $dataset ) {
                    		$current_db = $connection['name'];
                    		break;
                    	}
                    }

                    $val = $current_db . '.' . $result[0];

                } else {
                    $val =  $result[0];
                }

                if ( stripslashes_deep( $pfx ) == $wpdb->base_prefix ) {
                    // If we are on the main blog, we'll have to avoid those tables from other blogs
                    $pattern = '/^' . stripslashes_deep( $pfx ) . '[0-9]/';
                    if ( preg_match( $pattern, $result[0] ) )
                        continue;
                }

                $tables[] = array( 
                	'name' => $result[0] ,
                	'prefix.name' => $val
                );
    		}
    	}
    }

    restore_current_blog();

    return $tables;
    // End changed
}

/**
 * Get a copier class and return the instance
 * 
 * @param String $type Type of content to copy 
 * @param Array $variables Array of variables to pass to the copier class
 * 
 * @return Object/False
 */
function nbt_get_copier( $type, $source_blog_id, $template, $args = array(), $user_id = 0 ) {
    $type = strtolower( $type );

    include_once( NBT_PLUGIN_DIR . "blogtemplatesfiles/copier/class.copier2.php" );

    $file = NBT_PLUGIN_DIR . "blogtemplatesfiles/copier/class.copier-$type.php";

    if ( is_file( $file ) )
        include_once( $file );

    $type = ucfirst( $type );
    
    $classname = "NBT_Template_Copier_$type";
    $classname = apply_filters( 'blog_templates_get_copier_class', $classname, $type );

    $variables = compact( 'source_blog_id', 'template', 'args', 'user_id' );
    if ( class_exists( $classname ) ) {
        $r = new ReflectionClass( $classname );
        return $r->newInstanceArgs( $variables );
    }

    return false;
}

function nbt_set_copier_args( $source_blog_id, $destination_blog_id, $template = array(), $user_id = 0 ) {

    if ( ! $user_id )
        $user_id = get_current_user_id();

    $option = array(
        'source_blog_id' => $source_blog_id,
        'user_id' => $user_id,
        'template' => $template
    );
    if ( empty( $template ) ) {
        $to_copy = array(
            'settings' => array(),
            'posts' => array(),
            'pages' => array(),
            'terms' => array( 'update_relationships' => true ),
            'menus' => array(),
            'users' => array(),
            'comments' => array(),
            'attachment' => array(),
            'tables' => array()
        );
        $option['to_copy'] = $to_copy;
        $option['additional_tables'] = nbt_get_additional_tables( $source_blog_id );
    }
    else {
        foreach( $template['to_copy'] as $value ) {
            $to_copy_args = array();

            if ( $value === 'posts' ) {
                $to_copy_args['categories'] = isset( $template['post_category'] ) && in_array( 'all-categories', $template['post_category'] ) ? 'all' : $template['post_category'];
                $to_copy_args['block'] = isset( $template['block_posts_pages'] ) && $template['block_posts_pages'] === true ? true : false;
                $to_copy_args['update_dates'] = isset( $template['update_dates'] ) && $template['update_dates'] === true ? true : false;
            }
            elseif ( $value === 'pages' ) {
                $to_copy_args['pages_ids'] = isset( $template['pages_ids'] ) && in_array( 'all-pages', $template['pages_ids'] ) ? 'all' : $template['pages_ids'];
                $to_copy_args['block'] = isset( $template['block_posts_pages'] ) && $template['block_posts_pages'] === true ? true : false;
                $to_copy_args['update_dates'] = isset( $template['update_dates'] ) && $template['update_dates'] === true ? true : false;
            }
            elseif ( 'terms' === $value ) {
                if ( in_array( 'posts', $template['to_copy'] ) || in_array( 'pages', $template['to_copy'] ) )
                    $to_copy_args['update_relationships'] = true;
            }
            elseif ( 'files' === $value ) {
                $value = 'attachment';
            }

            $option['to_copy'][ $value ] = $to_copy_args;
        }

        if ( array_key_exists( 'posts', $option['to_copy'] ) || array_key_exists( 'pages', $option['to_copy'] ) )
            $option['to_copy']['comments'] = array();

        // Additional tables
        $tables_args = array();
        if ( in_array( 'settings', $template['to_copy'] ) ) {
            $tables_args['create_tables'] = true;
            $option['to_copy']['tables'] = $tables_args;
        }

        if ( isset( $template['additional_tables'] ) && is_array( $template['additional_tables'] ) ) {
            $tables_args['tables'] = $template['additional_tables'];
            $option['to_copy']['tables'] = $tables_args;
        }       
    }

    if ( isset( $option['to_copy']['attachment'] ) )
        $option['attachment_ids'] = nbt_get_blog_attachments( $source_blog_id );

    switch_to_blog( $destination_blog_id );
    delete_option( 'nbt-pending-template' );
    add_option( 'nbt-pending-template', $option, null, 'no' );
    restore_current_blog();

    return true;
}

function nbt_get_blog_attachments( $blog_id ) {
    switch_to_blog( $blog_id );

    $attachment_ids = get_posts( array(
        'posts_per_page' => -1,
        'post_type' => 'attachment',
        'fields' => 'ids',
        'ignore_sticky_posts' => true
    ) );

    $attachments = array();
    foreach ( $attachment_ids as $id ) {
        $item = array(
            'attachment_id' => $id,
            'date' => false
        );
        $attached_file = get_post_meta( $id, '_wp_attached_file', true );
        if ( $attached_file ) {
            if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $attached_file, $matches ) )
                $item['date'] = $matches[0];
        }
        $attachments[] = $item;
    }
    restore_current_blog();

    return $attachments;
}
