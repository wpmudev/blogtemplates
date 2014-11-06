<?php

add_filter( 'blog_template_exclude_settings', 'nbt_popover_remove_install_setting', 10, 1 );
function nbt_popover_remove_install_setting( $query ) {
	$query .= " AND `option_name` != 'popover_installed' ";
	return $query;
}