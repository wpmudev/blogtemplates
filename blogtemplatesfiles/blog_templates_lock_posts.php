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

		if ( ! apply_filters( 'nbt_activate_block_posts_feature', true ) )
			return;
		
		add_action( 'init', array( &$this, 'check' ) );

		// Lock the posts good.
		add_filter('user_has_cap', array($this, 'kill_edit_cap'), 10, 3);

		add_action('add_meta_boxes', array($this, 'add_meta_box'));

		add_action( 'save_post', array( &$this, 'update' ) );

	}

	function add_meta_box () {
		if ( ! is_super_admin() ) 
			return;
        
        $types = apply_filters('nbt_custom_lock_types', $this->_lock_types);

		foreach ( $types as $type ) {
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

		if ( ! $post_lock_status )
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
			$locked = $_POST['nbt_post_lock_status'] == 'locked' ? true : false;
			if ( $locked ) {
				delete_post_meta( $post_id, 'nbt_block_post' );
				add_post_meta( $post_id, 'nbt_block_post', true );
			}
			else
				delete_post_meta( $post_id, 'nbt_block_post' );
		}
	}

	/**
	 * Properly filtering out forbidden capabilities for non-super admin users.
	 */
	function kill_edit_cap ( $all, $cap, $args ) {
		global $post;

		if ( ! $args ) 
			return $all; // Something is wrong here.

		// Bail out if we're not asking about a post:
		if ( 'edit_post' != $args[0] )
			return $all;

		// Bail out for users who can't publish posts:
		if ( ! isset( $all['publish_posts'] ) or ! $all['publish_posts'] )
			return $all;

		if ( is_super_admin() )
			return $all;

		if ( ! isset( $post->ID ) )
			return $all;

		$blocked = get_post_meta( $post->ID, 'nbt_block_post', true );

		if ( $blocked ) {
			$post = get_post( $args[2] );
			$post->post_title = $post->post_title . ' ' . __( '[Blocked by Super Admin]', 'blog_templates' );
			$all[ $cap[0] ] = false;
		}

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