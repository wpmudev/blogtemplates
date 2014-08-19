<?php

function nbt_get_sites_search() {
	global $wpdb, $current_site;

	if ( ! empty( $_POST['term'] ) ) 
		$term = $_REQUEST['term'];
	else
		echo json_encode( array() );

	$s = isset( $_REQUEST['term'] ) ? stripslashes( trim( $_REQUEST[ 'term' ] ) ) : '';
	$wild = '%';
	if ( false !== strpos($s, '*') ) {
		$wild = '%';
		$s = trim($s, '*');
	}

	$like_s = esc_sql( like_escape( $s ) );
	$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

	if ( is_subdomain_install() ) {
		$blog_s = $wild . $like_s . $wild;
		$query .= " AND  ( {$wpdb->blogs}.domain LIKE '$blog_s' ) LIMIT 10";
	}
	else {
		if ( $like_s != trim('/', $current_site->path) )
			$blog_s = $current_site->path . $like_s . $wild . '/';
		else
			$blog_s = $like_s;	

		$query .= " AND  ( {$wpdb->blogs}.path LIKE '$blog_s' ) LIMIT 10";
	}
	

	
	$results = $wpdb->get_results( $query );

	$returning = array();
	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			$details = get_blog_details( $row->blog_id );
			$returning[] = array( 
				'blog_name' => $details->blogname,
				'path' => is_subdomain_install() ? $row->domain : $row->path, 
				'blog_id' => $row->blog_id 
			);
		}
	}

	echo json_encode( $returning );

	die();
}
add_action( 'wp_ajax_nbt_get_sites_search', 'nbt_get_sites_search' );

function nbt_toolbar_filter_categories() {
	echo "HHHH";
	exit();
	$cat_id = absint( $_POST['category_id'] );
	$type = $_POST['type'];

	$model = nbt_get_model();
	$templates = $model->get_templates_by_category( $cat_id );

	$options = nbt_get_settings();
	$checked = isset( $options['default'] ) ? $options['default'] : '';

	if ( '' === $type ) {
		echo '<select name="blog_template">';
		if ( empty( $checked ) ) {
   			echo '<option value="none">' . __( 'None', 'blog_templates' ) . '</option>';
   		}
	}


	foreach( $templates as $tkey => $template ) {
		nbt_render_theme_selection_item( $type, $template['ID'], $template, $options );
	}

	if ( '' === $type )
		echo '</select>';
	else
		echo '<div style="clear:both"></div>';

	die();
}
add_action( 'wp_ajax_nbt_toolbar_filter_categories', 'nbt_toolbar_filter_categories' );
add_action( 'wp_ajax_nopriv_nbt_toolbar_filter_categories', 'nbt_toolbar_filter_categories' );