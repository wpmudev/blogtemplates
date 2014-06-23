<?php

include_once( 'class.copier-post-types.php' );
class NBT_Template_Copier_Pages extends NBT_Template_Copier_Post_Types {

	public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
		$this->type = 'page';
		$this->args = wp_parse_args( $args, $this->get_default_args() );
		
		add_action( 'blog_templates-copying_table', array( $this, 'set_actions' ) );
	}

	public function get_default_args() {
		return array(
			'pages_ids' => 'all',
			'block' => false,
			'update_date' => false
		);
	}

	public function set_actions() {
		add_filter( 'blog_templates-clear_table_where', array( $this, 'set_clear_table_where' ), 1, 2 );
		add_filter( 'blog_templates_copy_post_table_query_where', array( $this, 'set_copy_table_where' ), 1, 2 );
	}

	public function set_clear_table_where( $where, $table ) {
		global $wpdb;

		if ( $wpdb->posts == $table ) {
			return "WHERE post_type = 'page'";
		}
		elseif ( $wpdb->postmeta == $table ) {
			return "WHERE post_id IN ( SELECT ID FROM $wpdb->posts p WHERE p.post_type = 'page' )";
		}

		return $where;
	}

	public function set_copy_table_where( $where ) {
		$where = "WHERE p.post_type = 'page'";

		if ( isset( $this->args['pages_ids'] ) && is_array( $this->args['pages_ids'] ) && count( $this->args['pages_ids'] ) > 0 ) {
            $pages_ids = '(' . implode( ',', $this->args['pages_ids'] ) . ')';
            $where .= " AND p.ID IN $pages_ids";
        }

		return $where;
	}
	

}