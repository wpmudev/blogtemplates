<?php

// Other plugins integrations

include_once( 'integration/gravity-forms.php' );

function nbt_add_membership_caps( $user_id, $blog_id ) {
	switch_to_blog( $blog_id );
	$user = get_userdata( $user_id );
	$user->add_cap('membershipadmin');
	$user->add_cap('membershipadmindashboard');
	$user->add_cap('membershipadminmembers');
	$user->add_cap('membershipadminlevels');
	$user->add_cap('membershipadminsubscriptions');
	$user->add_cap('membershipadmincoupons');
	$user->add_cap('membershipadminpurchases');
	$user->add_cap('membershipadmincommunications');
	$user->add_cap('membershipadmingroups');
	$user->add_cap('membershipadminpings');
	$user->add_cap('membershipadmingateways');
	$user->add_cap('membershipadminoptions');
	$user->add_cap('membershipadminupdatepermissions');
	update_user_meta( $user_id, 'membership_permissions_updated', 'yes');
	restore_current_blog();
}

function nbt_bp_add_register_scripts() {
	?>
	<script>
		jQuery(document).ready(function($) {
			var bt_selector = $('#blog_template-selection').remove();
			bt_selector.appendTo( $('#blog-details') );
		});
	</script>
	<?php
}

add_action( 'plugins_loaded', 'nbt_appplus_unregister_action' );
function nbt_appplus_unregister_action() {
	if ( class_exists('Appointments' ) ) {
		global $appointments;
		//TODO : Replace `wpmu_new_blog` once updated in App+
		remove_action( 'wpmu_new_blog', array( $appointments, 'new_blog' ), 10, 6 );
	}
}


// Framemarket theme
add_filter( 'framemarket_list_shops', 'nbt_framemarket_list_shops' );
function nbt_framemarket_list_shops( $blogs ) {
	$return = array();

	if ( ! empty( $blogs ) ) {
		$model = nbt_get_model();
		foreach ( $blogs as $blog ) {
			if ( ! $model->is_template( $blog->blog_id ) )
				$return[] = $blog;
		}
	}

	return $return;
}

add_filter( 'blogs_directory_blogs_list', 'nbt_remove_blogs_from_directory' );
function nbt_remove_blogs_from_directory( $blogs ) {
	$model = nbt_get_model();
	$new_blogs = array();
	foreach ( $blogs as $blog ) {
		if ( ! $model->is_template( $blog['blog_id'] ) )
			$new_blogs[] = $blog;
	}
	return $new_blogs;
}

/** AUTOBLOG **/
add_action( 'blog_templates-copy-options', 'nbt_copy_autoblog_feeds' );
function nbt_copy_autoblog_feeds( $template ) {
	global $wpdb;

	// Site ID, blog ID...
	$current_site = get_current_site();
	$current_site_id = $current_site->id;

	if ( ! isset( $template['blog_id'] ) )
		return;
	
	$source_blog_id = $template['blog_id'];
	$autoblog_on = false;

	switch_to_blog( $source_blog_id );
	// Is Autoblog activated?
	if ( ! function_exists( 'is_plugin_active' ) )
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( is_plugin_active( 'autoblog/autoblogpremium.php' ) )
		$autoblog_on = true;

	// We'll need this values later
	$source_url = get_site_url( $source_blog_id );
	$source_url_ssl = get_site_url( $source_blog_id, '', 'https' );

	restore_current_blog();

	if ( ! $autoblog_on )
		return;

	// Getting all the feed data for the source blog ID
	$autoblog_table = $wpdb->base_prefix . 'autoblog';
	$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $autoblog_table WHERE blog_id = %d AND site_id = %d", $source_blog_id, $current_site_id ) );

	if ( ! empty( $results ) ) {
		$current_blog_id = get_current_blog_id();

		$current_url = get_site_url( $current_blog_id );
		$current_url_ssl = get_site_url( $current_blog_id, '', 'https' );

		foreach ( $results as $row ) {
			// Getting the feed metadata
			$feed_meta = maybe_unserialize( $row->feed_meta );

			// We need to replace the source blog URL for the new one
			$feed_meta = str_replace( $source_url, $current_url, $feed_meta );
			$feed_meta = str_replace( $source_url_ssl, $current_url_ssl, $feed_meta );
			$feed_meta['blog'] = $current_blog_id;

			$row->feed_meta = maybe_serialize( $feed_meta );

			// Inserting feed for the new blog
			$wpdb->insert(
				$autoblog_table,
				array(
					'site_id' => $current_site_id,
					'blog_id' => $current_blog_id,
					'feed_meta' => $row->feed_meta,
					'active' => $row->active,
					'nextcheck' => $row->nextcheck,
					'lastupdated' => $row->lastupdated
				),
				array( '%d', '%d', '%s', '%d', '%d', '%d' )
			);
		}
	}

}

/** EASY GOOGLE FONTS **/
add_action( 'blog_templates-copy-after_copying', 'nbt_copy_easy_google_fonts_controls', 10, 2 );
function nbt_copy_easy_google_fonts_controls( $template, $destination_blog_id ) {
	global $wpdb;

	if ( ! is_plugin_active( 'easy-google-fonts/easy-google-fonts.php' ) )
		return;

	$source_blog_id = $template['blog_id'];

	if ( ! isset( $template['to_copy']['posts'] ) && get_blog_details( $source_blog_id ) && get_blog_details( $destination_blog_id ) ) {
		switch_to_blog( $source_blog_id );

		$post_query = "SELECT t1.* FROM {$wpdb->posts} t1 ";
		$post_query .= "WHERE t1.post_type = 'tt_font_control'";
		$posts_results = $wpdb->get_results( $post_query );

		$postmeta_query = "SELECT t1.* FROM {$wpdb->postmeta} t1 ";
		$postmeta_query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'tt_font_control'";
		$postmeta_results = $wpdb->get_results( $postmeta_query );
		
		restore_current_blog();

		switch_to_blog( $destination_blog_id );
		foreach ( $posts_results as $row ) {
            $row = (array)$row;
            $wpdb->replace( $wpdb->posts, $row );
        }

        foreach ( $postmeta_results as $row ) {
            $row = (array)$row;
            $wpdb->replace( $wpdb->postmeta, $row );
        }

        restore_current_blog();

	}
}



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

/** WOOCOMMERCE */

add_filter( 'nbt_copy_files_skip_list', 'nbt_woo_copy_files_skip_list', 10, 2 );
function nbt_woo_copy_files_skip_list( $skip_list, $dir_to_copy ) {
	if ( is_file( $dir_to_copy . '/woocommerce_uploads/.htaccess' ) )
		$skip_list[] = 'woocommerce_uploads/.htaccess';

	return $skip_list;
}

add_action( "blog_templates-copy-after_copying", 'nbt_woo_after_copy' );
function nbt_woo_after_copy() {
	
	if ( is_file( WP_CONTENT_DIR . '/plugins/woocommerce/includes/admin/class-wc-admin-settings.php' ) )
		include_once( WP_CONTENT_DIR . '/plugins/woocommerce/includes/admin/class-wc-admin-settings.php' );

	if ( class_exists( 'WC_Admin_Settings' ) )
		WC_Admin_Settings::check_download_folder_protection();
}

/**
 * UPFRONT
 */
add_action( "blog_templates-copy-after_copying", 'nbt_upfront_copy_options', 10, 2 );
function nbt_upfront_copy_options( $template, $destination_blog_id ) {
	global $wpdb;

	$source_blog_id = absint( $template['blog_id'] );

	switch_to_blog( $destination_blog_id );
	$theme_name = wp_get_theme();
	restore_current_blog();

	if ( $theme_name->Template === 'upfront' ) {
		$source_url = get_site_url( $source_blog_id );
		$destination_url = get_site_url( $destination_blog_id );
		$source_url = preg_replace( '/^https?\:\/\//', '', $source_url );
		$destination_url = preg_replace( '/^https?\:\/\//', '', $destination_url );
		
		$results = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '$theme_name%'");	

		foreach ( $results as $option_name ) {
			$json_value = get_option( $option_name );
			if ( ! is_string( $json_value ) )
				continue;

			$value = json_decode( $json_value );

			
			if ( is_object( $value ) || is_array( $value ) ) {
				$json_value = str_replace( $source_url, $destination_url, $json_value );
				update_option( $option_name, $json_value );
			}
		}
	}
}


//POPUP PRO
add_filter( 'nbt_copier_settings', 'nbt_popover_template_settings', 10, 3 );
function nbt_popover_template_settings( $settings, $src_blog_id, $new_blog_id ) {
	global $wpdb;

	switch_to_blog( $src_blog_id );
	if ( ! function_exists( 'is_plugin_active' ) )
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( in_array( 'settings', $settings['to_copy'] ) && is_plugin_active( 'popover/popover.php' ) ) {
		if ( ! in_array( $wpdb->prefix . 'popover_ip_cache', $settings['additional_tables'] ) )
			$settings['additional_tables'][] = $wpdb->prefix . 'popover_ip_cache';
	}

	restore_current_blog();
	return $settings;
}
//add_action( 'blog_templates-copy-after_copying', 'nbt_popover_copy_settings', 10, 2 );
function nbt_popover_copy_settings( $template, $new_blog_id ) {
	if ( in_array( 'settings', $template['to_copy'] ) ) {
		$popup_options = get_blog_option( $template['blog_id'], 'inc_popup-config' );
		if ( ! $popup_options )
			return;

		update_option( 'inc_popup-config', $popup_options );
	}
}


/**
 * PRO SITES
 */
add_filter( 'psts_setting_checkout_url', 'nbt_pro_sites_checkout_url_setting' );
function nbt_pro_sites_checkout_url_setting( $value ) {
	global $pagenow, $psts;

	if ( ! is_object( $psts ) )
		return $value;

	$show_signup = $psts->get_setting( 'show_signup' );

	if ( ! is_admin() && 'wp-signup.php' == $pagenow && $show_signup && isset( $_REQUEST['blog_template'] ) ) {
		$value = add_query_arg( 'blog_template', $_REQUEST['blog_template'], $value );
	}

	return $value;
}

add_filter( 'psts_redirect_signup_page_url', 'nbt_pro_sites_checkout_url' );
function nbt_pro_sites_checkout_url( $url ) {
	if ( isset( $_REQUEST['blog_template'] ) ) {
		$url = add_query_arg( 'blog_template', $_REQUEST['blog_template'], $url );
	}

	return $url;
}
