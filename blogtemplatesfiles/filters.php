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

	$tag = 'wpmu_new_blog';
	$method = 'set_blog_defaults';
	$action_order = false;

	$bt_callback = array($blog_templates, $method);
	if (!is_callable($bt_callback)) return false;

	if (has_action($tag, $bt_callback)) { 
		// This is all provided it's even bound
		$actions = !empty($wp_filter[$tag]) ? $wp_filter[$tag] : false;
		if (!$actions) return false;

		$highest = max(array_keys($actions));
		if (!$idx = _wp_filter_build_unique_id($tag, $bt_callback, false)) return false; // Taken from core (`has_filter()`)

		foreach ($actions as $priority => $callbacks) {
			if (!isset($actions[$priority][$idx])) continue;
			$action_order = $priority;
			break;
		}

		if ($action_order >= $highest) return true; // We're the on the bottom, all good.

		// If we reached here, this is not good - we need to re-bind to highest position
		remove_action($tag, $bt_callback, $action_order, 6);
		$action_order = $highest + 10;
	} else {
		// No action bound, let's do our thing
		$action_order = defined('NBT_APPLY_TEMPLATE_ACTION_ORDER') && NBT_APPLY_TEMPLATE_ACTION_ORDER ? NBT_APPLY_TEMPLATE_ACTION_ORDER : 9999;
		$action_order = apply_filters('blog_templates-actions-action_order', $action_order);
	}

	add_action($tag, $bt_callback, $action_order, 6);
	return true;
}
if (defined('NBT_ENSURE_LAST_PLACE') && NBT_ENSURE_LAST_PLACE) add_action('init', 'blog_template_ensure_last_place', 99);




/**
 * Ensure the newly registered user is added to new blog.
 */
function blog_template_add_user_as_admin ($template, $blog_id, $user_id) {
	if ( is_super_admin( $user_id ) ) 
		return false;

	if ( ! in_array( 'users', $template['to_copy'] ) ) 
		return false; // Only apply this if we're trumping over users	

	return add_user_to_blog( $blog_id, $user_id, 'administrator' );
}
add_action('blog_templates-copy-after_copying', 'blog_template_add_user_as_admin', 10, 3);


/* ----- Optional (switchable) filters ----- */


/**
 * Optionally transfer post ownership to the new or predefined user ID.
 */
function blog_template_reassign_post_authors ( $template, $blog_id, $user_id ) {
	if ( ! in_array( 'users', $template['to_copy'] ) ) {
		global $wpdb;
		$wpdb->query($wpdb->prepare( "UPDATE {$wpdb->posts} SET post_author=%d", $user_id ) );
	}
}
add_action('blog_templates-copy-posts', 'blog_template_reassign_post_authors', 10, 3);
add_action('blog_templates-copy-pages', 'blog_template_reassign_post_authors', 10, 3);


