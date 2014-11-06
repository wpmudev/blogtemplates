<?php

/** WORDPRESS HTTPS **/
add_action( 'blog_templates-copy-options', 'nbt_hooks_set_https_settings' );
function nbt_hooks_set_https_settings( $template ) {
	if ( ! function_exists( 'is_plugin_active' ) )
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	if ( is_plugin_active( 'wordpress-https/wordpress-https.php' ) ) {
		if ( get_option( 'wordpress-https_ssl_admin' ) )
			update_option( 'wordpress-https_ssl_host', trailingslashit( get_site_url( get_current_blog_id(), '', 'https' ) ) );
		else
			update_option( 'wordpress-https_ssl_host', trailingslashit( get_site_url( get_current_blog_id(), '', 'http' ) ) );
	}

}