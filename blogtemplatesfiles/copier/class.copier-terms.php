<?php

class NBT_Template_Copier_Terms extends NBT_Template_Copier {

	public function __construct( $source_blog_id, $args, $template, $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id = 0 );
		$this->type = 'post';
		$this->args = wp_parse_args( $args, $this->get_default_args() );

		add_action( 'blog_templates-copying_table', array( $this, 'set_actions' ) );
	}

	public function get_default_args() {
		return array(
			'taxonomies' => array()
		);
	}

	public function copy() {
		global $wpdb;

		$tables = array($wpdb->terms, $wpdb->term_relationships, $wpdb->term_taxonomy );

		foreach ( $tables as $table ) {
			$result = $this->clear_table( $wpdb->terms );
			if ( is_wp_error( $result ) )
				return $result;
		}

		switch_to_blog( $this->source_blog_id );
		$source_terms = get_terms( $this->args['taxonomies'], array( 'get' => 'all' ) );
		restore_current_blog();

		$mapped_terms = array();

		$wpdb->query( "BEGIN;" );

		foreach ( $source_terms as $term ) {
			//var_dump($term);

			$term_args = array(
				'description' => $term->description,
				'slug' => $term->slug
			);

			$new_term = wp_insert_term( $term->name, $term->taxonomy, $term_args );
			
			if ( is_wp_error( $new_term ) )
				continue;			

			// Check if the term has a parent
			$mapped_terms[ $term->term_id ] = $new_term['term_id'];

		}

		// Now update parents
		foreach ( $source_terms as $term ) {
			if ( ! empty( $term->parent ) && isset( $mapped_terms[ $term->parent ] ) && isset( $mapped_terms[ $term->term_id ] ) ) {
				wp_update_term( $mapped_terms[ $term->term_id ], $term->taxonomy, array( 'parent' => $mapped_terms[ $term->parent ] ) );
			}
		}

		$source_terms = get_terms( $this->args['taxonomies'], array( 'get' => 'all' ) );


        $wpdb->query("COMMIT;");

        return true;
	}

	/**
	 * Thanks to http://ben.lobaugh.net/blog/567/php-recursively-convert-an-object-to-an-array
	 * @param type $obj 
	 * @return type
	 */
	function object_to_array($obj) {
	    if(is_object($obj)) $obj = (array) $obj;
	    if(is_array($obj)) {
	        $new = array();
	        foreach($obj as $key => $val) {
	            $new[$key] = object_to_array($val);
	        }
	    }
	    else $new = $obj;
	    return $new;       
	}
	

}