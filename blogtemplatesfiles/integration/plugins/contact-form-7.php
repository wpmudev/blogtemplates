<?php 


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
