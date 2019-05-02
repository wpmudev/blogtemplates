<?php
/**
 * Contains specific behavior altering filters.
 */

 
/**
 * Ensure we're the last thing to run on blog creation.
 */
function blog_template_ensure_last_place () {
	global $wp_filter, $blog_templates;
	if (!$wp_filter || !$blog_templates) return false;

	$tag = 'wp_initialize_site';
	$method = 'set_blog_defaults';
	$action_order = $highest = 0;

	$bt_callback = array($blog_templates, $method);
	if (!is_callable($bt_callback)) return false;

	if (has_action($tag, $bt_callback)) { 
		// This is all provided it's even bound
		$actions = !empty($wp_filter[$tag]) ? $wp_filter[$tag] : false;

		if ( ! $actions ) return false;

		$priorities = array();

		
		foreach ( $actions->callbacks as $priority => $callbacks ) {

			$priorities[] = $priority;

			foreach ( $callbacks as $tag_key => $callback ) {
				if ( substr_compare( $tag_key, $method, strlen( $tag_key )-strlen( $method ), strlen( $method ) ) === 0 ) {
		            $action_order = $priority;
		        }
			}
	    }

	    if ( ! empty( $priorities ) ) {
	    	$highest = max( $priorities );
	    }	    

/*
		$highest = max(array_keys($actions));
		if (!$idx = _wp_filter_build_unique_id($tag, $bt_callback, false)) return false; // Taken from core (`has_filter()`)

		foreach ($actions as $priority => $callbacks) {
			if (!isset($actions[$priority][$idx])) continue;
			$action_order = $priority;
			break;
		}
*/
		if ($action_order >= $highest) return true; // We're the on the bottom, all good.

		// If we reached here, this is not good - we need to re-bind to highest position
		remove_action($tag, $bt_callback, $action_order, 2);
		$action_order = $highest + 10;

	} else {
		// No action bound, let's do our thing
		$action_order = defined('NBT_APPLY_TEMPLATE_ACTION_ORDER') && NBT_APPLY_TEMPLATE_ACTION_ORDER ? NBT_APPLY_TEMPLATE_ACTION_ORDER : 9999;
		$action_order = apply_filters('blog_templates-actions-action_order', $action_order);
	}

	add_action($tag, $bt_callback, $action_order, 2);
	return true;
}
if (defined('NBT_ENSURE_LAST_PLACE') && NBT_ENSURE_LAST_PLACE) add_action('init', 'blog_template_ensure_last_place', 99);



/* ----- Default filters ----- */


/**
 * Exclude Gravity Forms option AND Formidable Pro (thank you, wecreateyou/Stephanie).
 **/
function blog_template_exclude_gravity_forms( $and ) {
	//$and .= " AND `option_name` != 'rg_forms_version'";
	$and .= " AND `option_name` NOT IN ('rg_forms_version','frm_db_version','frmpro_db_version')";
	return $and;
}
add_filter('blog_template_exclude_settings', 'blog_template_exclude_gravity_forms');



/**
 * Exclude Contact Form 7 postmeta fields 
 */
function blog_template_contact_form7_postmeta ($row, $table) {
	if ("postmeta" != $table) return $row;
	
	$key = @$row['meta_key'];
	$wpcf7 = array('mail', 'mail_2');
	if (defined('NBT_PASSTHROUGH_WPCF7_MAIL_FIELDS') && NBT_PASSTHROUGH_WPCF7_MAIL_FIELDS) return $row;
	if (defined('NBT_CONVERT_WPCF7_MAIL_FIELDS') && NBT_CONVERT_WPCF7_MAIL_FIELDS) return blog_template_convert_wpcf7_mail_fields($row);
	if (in_array($key, $wpcf7)) return false;
	
	return $row;
}
function blog_template_convert_wpcf7_mail_fields ($row) {
	global $_blog_template_current_templated_blog_id;
	if (!$_blog_template_current_templated_blog_id) return $row; // Can't do the replacement

	$value = @$row['meta_value'];
	$wpcf7 = $value ? unserialize($value) : false;
	if (!$wpcf7) return $row; // Something went wrong

	// Get convertable values
	switch_to_blog($_blog_template_current_templated_blog_id);
	$admin_email = get_option("admin_email");
	$site_url = get_bloginfo('url');
	// ... more stuff at some point
	restore_current_blog();

	// Get target values
	$new_site_url = get_bloginfo('url');
	$new_admin_email = get_option('admin_email');
	// ... more stuff at some point
	
	// Do the replace
	foreach ($wpcf7 as $key => $val) {
		$val = preg_replace('/' . preg_quote($site_url, '/') . '/i', $new_site_url, $val);
		$val = preg_replace('/' . preg_quote($admin_email, '/') . '/i', $new_admin_email, $val);
		$wpcf7[$key] = $val;
	}

	// Right, so now we have the replaced array - populate it.
	$row['meta_value'] = serialize($wpcf7);
	return $row;
}
function blog_template_check_postmeta ($tbl, $templated_blog_id) {
	global $_blog_template_current_templated_blog_id;
	$_blog_template_current_templated_blog_id = $templated_blog_id;
	if ("postmeta" == $tbl) add_filter('blog_templates-process_row', 'blog_template_contact_form7_postmeta', 10, 2);
}
add_action('blog_templates-copying_table', 'blog_template_check_postmeta', 10, 2);



/**
 * Exclude EPanel temporary folder paths.
 */
function blog_template_exclude_epanel_temp_path ($and) {
	$and .= " AND `option_name` != 'et_images_temp_folder'";
	return $and;
}
add_filter('blog_template_exclude_settings', 'blog_template_exclude_epanel_temp_path');


/**
 * Ensure the newly registered user is added to new blog.
 */
function blog_template_add_user_as_admin ($template, $blog_id, $user_id) {
	if (is_super_admin($user_id)) return false;
    if ( empty($template['to_copy']['users']) ) return false; // Only apply this if we're trumping over users
	return add_user_to_blog($blog_id, $user_id, 'administrator');
}
add_action('blog_templates-copy-after_copying', 'blog_template_add_user_as_admin', 10, 3);


/* ----- Optional (switchable) filters ----- */


/**
 * Optionally transfer post ownership to the new or predefined user ID.
 */
function blog_template_reassign_post_authors ( $template, $blog_id, $user_id ) {
    if ( empty($template['to_copy']['users']) ) {
		global $wpdb;
		$wpdb->query($wpdb->prepare( "UPDATE {$wpdb->posts} SET post_author=%d", $user_id ) );
	}
}
add_action('blog_templates-copy-posts', 'blog_template_reassign_post_authors', 10, 3);
add_action('blog_templates-copy-pages', 'blog_template_reassign_post_authors', 10, 3);


// Play nice with Multisite Privacy, if requested so
if (defined('NBT_TO_MULTISITE_PRIVACY_ALLOW_SIGNUP_OVERRIDE') && NBT_TO_MULTISITE_PRIVACY_ALLOW_SIGNUP_OVERRIDE) {
	/**
	 * Keeps user-selected Multisite Privacy settings entered on registration time.
	 * Propagate template settings on admin blog creation time.
	 */
	function blog_template_exclude_multisite_privacy_settings ($and) {
		$user = wp_get_current_user();
		if (is_super_admin($user->ID)) return $and;
		$and .= " AND `option_name` NOT IN ('spo_settings', 'blog_public')";
		return $and;
	}
	add_filter('blog_template_exclude_settings', 'blog_template_exclude_multisite_privacy_settings');
}