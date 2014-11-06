<?php

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