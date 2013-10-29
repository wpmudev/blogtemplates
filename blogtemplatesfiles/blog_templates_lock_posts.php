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

		add_action('add_meta_boxes', array($this, 'add_meta_box'));

		add_action( 'save_post', array( &$this, 'update' ) );

	}

	function add_meta_box () {
		if ( ! is_super_admin() ) 
			return;

		foreach ( $this->_lock_types as $type ) {
			add_meta_box( 'postlock', __( 'Post Status', 'blog_templates' ), array( $this, 'meta_box_output' ), $type, 'advanced', 'high' );
		}
	}

	/**
	 * Post status metabox
	 *
	 */
	function meta_box_output( $post ) {
		if ( ! is_super_admin() )
			return;

		$post_lock_status = get_post_meta( $post->ID, 'nbt_block_post', true );

		if( empty( $post_lock_status ) )
			$post_lock_status = false;
		?>
		<div id="nbtpostlockstatus">
			<label class="hidden" for="excerpt">Post Status</label>
			<select name="nbt_post_lock_status">
				<option value="locked" <?php selected( $post_lock_status === true ) ?>><?php _e( 'Locked', 'blog_templates' ) ?></option>
				<option value="unlocked" <?php selected( $post_lock_status === false ) ?>><?php _e( 'Unlocked', 'blog_templates' ) ?></option>
			</select>
			<p><?php _e( 'Locked posts cannot be edited by anyone other than Super admins.', 'blog_templates' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Update post status
	 *
	 */
	function update( $post_id ) {
		if ( ! empty( $_POST['nbt_post_lock_status'] ) && is_super_admin() ) {
			if ( 'locked' == $_POST['nbt_post_lock_status'] )
				update_post_meta( $post_id, 'nbt_block_post', true );
			else
				update_post_meta( $post_id, 'nbt_block_post', false );
		}
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
			$post->post_title .= __( ' - Locked by Super Admin ', 'blog_templates' );

		if ( ! $post_lock_status || is_super_admin() )
			return $all;

		unset( $all['edit_posts'] );
		unset( $all['edit_others_posts'] );
		unset( $all['edit_published_posts'] );
		unset( $all['delete_posts'] );
		unset( $all['delete_private_posts'] );
		unset( $all['edit_private_posts'] );
		unset( $all['delete_others_posts'] );
		unset( $all['delete_published_posts'] );

		unset( $all['edit_pages'] );
		unset( $all['edit_others_pages'] );
		unset( $all['edit_published_pages'] );
		unset( $all['delete_pages'] );
		unset( $all['delete_private_pages'] );
		unset( $all['edit_private_pages'] );
		unset( $all['delete_others_pages'] );
		unset( $all['delete_published_pages'] );

		return $all;
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