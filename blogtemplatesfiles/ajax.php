<?php

add_action( 'wp_ajax_nbt_get_sites_search', 'nbt_get_sites_search' );
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


add_action( 'wp_ajax_nbt_process_template', 'nbt_process_ajax_template' );
function nbt_process_ajax_template() {

    if ( ! current_user_can( 'manage_options' ) )
        wp_send_json_error( array( 'message' => "Security Error" ) );

    check_ajax_referer( 'nbt_process_template', 'security' );

    $option = get_option( 'nbt-pending-template' );

    $result = blog_templates::process_template( $option );
    
    if ( $result['error'] )
        wp_send_json_error( array( 'message' => $result['message'] ) );
    else
        wp_send_json_success( array( 'message' => $result['message'] ) );

}