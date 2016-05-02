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

    $default_tables = array( 'posts', 'comments', 'links', 'options', 'postmeta', 'terms', 'term_taxonomy', 'termmeta', 'term_relationships', 'commentmeta' );

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

    if (!empty($results)) {

        foreach($results as $result) {
            if ( ! in_array( str_replace( $wpdb->prefix, '', $result['0'] ), $default_tables ) ) {

                if (class_exists("m_wpdb")) {
                    $db = $wpdb->analyze_query("SHOW TABLES LIKE '{$pfx}%'");
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
                //echo "<input type='checkbox' name='additional_template_tables[]' value='$result[0]'";
                echo "<input type='checkbox' name='additional_template_tables[]' value='{$val}'";
                if ( isset( $template['additional_tables'] ) && is_array( $template['additional_tables'] ) )
                    //if ( in_array( $result[0], $template['additional_tables']'] ) )
                    if ( in_array( $val, $template['additional_tables'] ) )
                        echo ' checked="CHECKED"';
                echo " id='nbt-{$val}'>&nbsp;<label for='nbt-{$val}'>{$result[0]}</label><br/>";
            }
        }
    } else {
        _e('There are no additional tables to display for this blog','blog_templates');
    }
    // End changed
}
