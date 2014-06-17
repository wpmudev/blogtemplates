<?php

include_once( 'class.copier-post-types.php' );
class NBT_Template_Copier_Posts extends NBT_Template_Copier_Post_Types {

	public function __construct( $source_blog_id, $args, $template, $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id = 0 );
		$this->type = 'post';
		$this->args = wp_parse_args( $args, $this->get_default_args() );

		add_action( 'blog_templates-copying_table', array( $this, 'set_actions' ) );
	}

	public function get_default_args() {
		return array(
			'categories' => 'all',
			'block' => false,
			'update_date' => false
		);
	}

	public function set_actions() {
		add_filter( 'blog_templates-clear_table_where', array( $this, 'set_clear_table_where' ), 10, 2 );
	}

	public function set_clear_table_where( $where, $table ) {
		global $wpdb;

		if ( $wpdb->posts == $table ) {
			return "WHERE post_type NOT IN ( 'page', 'attachment', 'revision', 'nav_menu_item' )";
		}
		elseif ( $wpdb->postmeta == $table ) {
			return "WHERE post_id IN ( SELECT ID FROM $wpdb->posts p WHERE p.post_type NOT IN  ( 'page', 'attachment', 'revision', 'nav_menu_item' ) )";
		}

		return $where;
	}
	

}