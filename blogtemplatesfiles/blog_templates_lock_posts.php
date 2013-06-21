<?php

class NBT_Lock_Posts {

	/**
	 * Post types that support locking.
	 */
	private $_lock_types = array(
		'post',
		'page',
	);

	/**
	 * Locked out capabilities.
	 */
	private $_lock_capabilities = array();

	/**
	 * PHP5 constructor
	 *
	 */
	function __construct() {
		add_action( 'init', array( &$this, 'check' ) );

		// Lock the posts good.
		add_filter('user_has_cap', array($this, 'kill_edit_cap'), 10, 3);

	}

	/**
	 * Properly filtering out forbidden capabilities for non-super admin users.
	 */
	function kill_edit_cap ($all, $caps, $args) {
		global $post;

		if (!is_object($post) || !isset($post->post_type)) return $all; // Only proceed for pages with known post types

		if (!$args) return $all; // Something is wrong here.
		if (count($args) < 3) return $all; // Only proceed for individual items.
		if (!isset($args[0])) return $all; // Something is still wrong here.

		$post_id = isset($args[2]) ? $args[2] : false;
		if (!$post_id) return $all; // Can't obtain post ID


		$post_lock_status = (get_post_meta($post_id, 'nbt_block_post', true));

		if ( $post_lock_status )
			$post->post_title .= __( ' - Locked by Super Admin ' );

		return $post_lock_status ? (is_super_admin() ? $all : false) : $all;
	}



	/**
	 * Check post status and redirect if the user is not super admin and post is locked
	 *
	 */
	function check() {
		if ( ! is_super_admin() && ! empty( $_GET['action'] ) && 'edit' == $_GET['action'] && ! empty( $_GET['post'] ) ) {
			$post_lock_status = get_post_meta( $_GET['post'], 'nbt_block_post', true );

			if ( $post_lock_status )
				wp_redirect( admin_url( 'edit.php?page=post-locked&post=' . $_GET['post'] ) );
		}
	}

}

$lock_posts = new NBT_Lock_Posts();