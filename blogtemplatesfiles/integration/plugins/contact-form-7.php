<?php 

add_action( 'blog_templates-copy-after_copying', 'nbt_convert_cf7_email_fields', 10, 3 );
function nbt_convert_cf7_email_fields( $template, $new_blog_id, $user_id ) {
	$source_blog_id = absint( $template['blog_id'] );
	if ( ! get_blog_details( $source_blog_id ) )
		return;

	if ( defined( 'NBT_PASSTHROUGH_WPCF7_MAIL_FIELDS' ) && NBT_PASSTHROUGH_WPCF7_MAIL_FIELDS ) 
		return;

	// Get all CF7 Posts
	$cf7_posts = get_posts( array(
		'posts_per_page' => -1,
		'ignore_sticky_posts' => true,
		'post_type' => 'wpcf7_contact_form',
		'post_status' => 'any'
	) );

	foreach ( $cf7_posts as $post ) {

		switch_to_blog($source_blog_id);
		$admin_email = get_option( "admin_email" );
		$site_url = get_bloginfo( 'url' );
		restore_current_blog();

		$new_site_url = get_bloginfo('url');
		$new_admin_email = get_option('admin_email');

		$metas = array( '_mail', '_mail_2' );
		foreach ( $metas as $meta_key ) {
			$meta_value = get_post_meta( $post->ID, $meta_key, true );
			if ( ! is_array( $meta_value ) )
				continue;

			$new_meta_value = $meta_value;
			foreach ( $meta_value as $key => $value ) {
				$new_value = preg_replace( '/' . preg_quote( $site_url, '/' ) . '/i', $new_site_url, $value );
				$new_value = preg_replace( '/' . preg_quote( $admin_email, '/' ) . '/i', $new_admin_email, $new_value );

				$new_meta_value[ $key ] = $new_value;

			}

			update_post_meta( $post->ID, $new_meta_value );
		}		

	}
}
