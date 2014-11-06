<?php

/**
 * Exclude EPanel temporary folder paths.
 */
function nbt_exclude_epanel_temp_path ($and) {
	$and .= " AND `option_name` != 'et_images_temp_folder'";
	return $and;
}
add_filter('blog_template_exclude_settings', 'nbt_exclude_epanel_temp_path' );