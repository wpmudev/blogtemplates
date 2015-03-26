<?php

/**
 * Exclude Formidable Pro option (thank you, wecreateyou/Stephanie).
 **/
function nbt_exclude_formidable_pro( $and ) {
	$and .= " AND `option_name` NOT IN ('frm_db_version','frmpro_db_version')";
	return $and;
}
add_filter('blog_template_exclude_settings', 'nbt_exclude_formidable_pro');
