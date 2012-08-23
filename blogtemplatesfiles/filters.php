<?php
/**
 * Contains specific behavior altering filters.
 */

 
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
	if (!in_array('users', $template['to_copy'])) return false; // Only apply this if we're trumping over users
	return add_user_to_blog($blog_id, $user_id, 'administrator');
}
add_action('blog_templates-copy-after_copying', 'blog_template_add_user_as_admin', 10, 3);


/* ----- Optional (switchable) filters ----- */


if (defined('NBT_REASSIGN_POST_AUTHORS_TO_USER') && NBT_REASSIGN_POST_AUTHORS_TO_USER) {
	/**
	 * Optionally transfer post ownership to the new or predefined user ID.
	 */
	function blog_template_reassign_post_authors ($template, $blog_id, $user_id) {
		$new_author = false;
		if ('current_user' == NBT_REASSIGN_POST_AUTHORS_TO_USER) $new_author = $user_id;
		else if (is_numeric(NBT_REASSIGN_POST_AUTHORS_TO_USER)) $new_author = NBT_REASSIGN_POST_AUTHORS_TO_USER;
		if (!$new_author) return false;

		global $wpdb;
		$wpdb->query("UPDATE {$wpdb->posts} SET post_author={$new_author}");
	}
	add_action('blog_templates-copy-posts', 'blog_template_reassign_post_authors', 10, 3);
}