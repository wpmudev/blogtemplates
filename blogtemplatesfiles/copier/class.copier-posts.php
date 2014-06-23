<?php

include_once( 'class.copier-post-types.php' );
class NBT_Template_Copier_Posts extends NBT_Template_Copier_Post_Types {

	public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
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
		add_filter( 'blog_templates-clear_table_where', array( $this, 'set_clear_table_where' ), 1, 2 );
		add_filter( 'blog_templates_copy_post_table_query_where', array( $this, 'set_copy_table_where' ), 1, 2 );
		add_filter( 'blog_templates_copy_post_table_query_join', array( $this, 'set_copy_table_join' ), 1, 2 );
		add_filter( 'blog_templates_copy_post_table_query_group', array( $this, 'set_copy_table_group' ), 1, 2 );
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

	public function set_copy_table_where( $where ) {
		$where = "WHERE p.post_type NOT IN ( 'page', 'attachment', 'revision', 'nav_menu_item', 'auto-draft' )";

		if ( isset( $this->args['categories'] ) && is_array( $this->args['categories'] ) && count( $this->args['categories'] ) > 0 ) {
			$categories_list = '(' . implode( ',', $this->args['categories'] ) . ')';
            $where .= " AND tr.term_taxonomy_id IN $categories_list";
		}

		return $where;
	}

	public function set_copy_table_join( $join ) {
		global $wpdb;
		if ( isset( $this->args['categories'] ) && is_array( $this->args['categories'] ) && count( $this->args['categories'] ) > 0 ) {
			switch_to_blog( $this->source_blog_id );
            $join = "INNER JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID ";
            restore_current_blog();
        }
		return $join;
	}

	public function set_copy_table_group( $group ) {
		if ( isset( $this->args['categories'] ) && is_array( $this->args['categories'] ) && count( $this->args['categories'] ) > 0 ) {
            $group = "GROUP BY p.ID";
        }
		return $group;
	}

	

}