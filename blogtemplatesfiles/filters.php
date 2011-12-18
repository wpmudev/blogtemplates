<?php
/**
 * Contains specific behavior altering filters.
 */

 

/**
 * Exclude Gravity Forms option AND Formidable Pro (thank you, wecreateyou/Stephanie).
 **/
function blog_template_exclude_gravity_forms( $and ) {
	//$and .= " AND `option_name` != 'rg_forms_version'";
	$and .= " AND `option_name` NOT IN ('rg_forms_version','frm_db_version','frmpro_db_version')";
	return $and;
}
add_filter( 'blog_template_exclude_settings', 'blog_template_exclude_gravity_forms' );



/**
 * Exclude Contact Form 7 postmeta fields 
 */
function blog_template_contact_form7_postmeta ($row, $table) {
	if ("postmeta" != $table) return $row;
	
	$key = @$row['meta_key'];
	$wpcf7 = array('mail', 'mail_2');
	if (in_array($key, $wpcf7)) return false;
	
	return $row;
}
function blog_template_check_postmeta ($tbl) {
	if ("postmeta" == $tbl) add_filter('blog_templates-process_row', 'blog_template_contact_form7_postmeta', 10, 2);
}
add_action('blog_templates-copying_table', 'blog_template_check_postmeta');