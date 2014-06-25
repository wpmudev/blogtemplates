<?php

class NBT_Template_Copier_Terms extends NBT_Template_Copier {

	public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
		$this->type = 'post';
		$this->args = wp_parse_args( $args, $this->get_default_args() );
	}

	public function get_default_args() {
		return array(
			'update_relationships' => false // all or array with post, page or any post_type
		);
	}

	public function copy() {
		global $wpdb;

		$tables = array( $wpdb->terms, $wpdb->term_taxonomy, $wpdb->links );

		foreach ( $tables as $table ) {
			$result = $this->clear_table( $wpdb->terms );
			if ( is_wp_error( $result ) )
				return $result;
		}

		switch_to_blog( $this->source_blog_id );
		$_taxonomies = $wpdb->get_col( "SELECT DISTINCT taxonomy FROM $wpdb->term_taxonomy" );
        $taxonomies = array();
        foreach ( $_taxonomies as $taxonomy )
            $taxonomies[ $taxonomy ] = $taxonomy;
		unset( $taxonomies['nav_menu'] );
		$source_terms = get_terms( $taxonomies, array( 'get' => 'all' ) );
		restore_current_blog();

		$mapped_terms = array();

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

		unset( $source_terms );

		// Update posts term relationships
		if ( $this->args['update_relationships'] ) {

			$query = "SELECT ID FROM $wpdb->posts";
			if ( is_array( $this->args['update_relationships'] ) ) {
				$query .= " WHERE post_type IN ( '" . implode( "','", $this->args['update_relationships'] ) . "')";
			}

			$posts_ids = $wpdb->get_col( $query );

			if ( ! empty( $posts_ids ) ) {
				$posts_ids = array_map( 'absint', $posts_ids );

				$source_objects_terms = array();
				switch_to_blog( $this->source_blog_id );
				foreach ( $posts_ids as $post_id ) {
					$object_terms = $this->get_object_terms( $post_id, $taxonomies );
					if ( ! empty( $object_terms ) && ! is_wp_error( $object_terms ) )
						$source_objects_terms[ $post_id ] = $object_terms;
				}
				restore_current_blog();

				if ( ! empty( $source_objects_terms ) ) {
					// We just need to set the object terms with the remapped terms IDs
					foreach ( $source_objects_terms as $post_id => $source_object_terms ) {
						$taxonomies = array_unique( wp_list_pluck( $source_object_terms, 'taxonomy' ) );
						foreach ( $taxonomies as $taxonomy ) {
							$source_terms_ids = wp_list_pluck( wp_list_filter( $source_object_terms, array( 'taxonomy' => $taxonomy ) ), 'term_id' );
							$new_terms_ids = array();
							foreach ( $source_terms_ids as $source_term_id ) {
								if ( isset( $mapped_terms[ $source_term_id ] ) )
									$new_terms_ids[] = $mapped_terms[ $source_term_id ];
							}

							// Set post terms
							$this->set_object_terms( $post_id, $new_terms_ids, $taxonomy );
						}
						
					}
				}


			}

			
		}

        return true;
	}

	/**
	 * We need this function because WP checks if the taxonomy exist
	 * When switching between blogs, this will not work
	 * 
	 * @param type $object_id 
	 * @param type $terms_ids 
	 * @return array|WP_Error Affected Term IDs
	 */
	private function get_object_terms( $object_ids, $taxonomies ) {
		global $wpdb;

		if ( empty( $object_ids ) || empty( $taxonomies ) )
			return array();

		if ( !is_array($taxonomies) )
			$taxonomies = array($taxonomies);

		if ( !is_array($object_ids) )
			$object_ids = array($object_ids);
		$object_ids = array_map('intval', $object_ids);

		$defaults = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'all');
		$args = wp_parse_args( $args, $defaults );

		$terms = array();
		if ( count($taxonomies) > 1 ) {
			foreach ( $taxonomies as $index => $taxonomy ) {
				$t = get_taxonomy($taxonomy);
				if ( isset($t->args) && is_array($t->args) && $args != array_merge($args, $t->args) ) {
					unset($taxonomies[$index]);
					$terms = array_merge($terms, wp_get_object_terms($object_ids, $taxonomy, array_merge($args, $t->args)));
				}
			}
		} else {
			$t = get_taxonomy($taxonomies[0]);
			if ( isset($t->args) && is_array($t->args) )
				$args = array_merge($args, $t->args);
		}

		extract($args, EXTR_SKIP);

		if ( 'count' == $orderby )
			$orderby = 'tt.count';
		else if ( 'name' == $orderby )
			$orderby = 't.name';
		else if ( 'slug' == $orderby )
			$orderby = 't.slug';
		else if ( 'term_group' == $orderby )
			$orderby = 't.term_group';
		else if ( 'term_order' == $orderby )
			$orderby = 'tr.term_order';
		else if ( 'none' == $orderby ) {
			$orderby = '';
			$order = '';
		} else {
			$orderby = 't.term_id';
		}

		// tt_ids queries can only be none or tr.term_taxonomy_id
		if ( ('tt_ids' == $fields) && !empty($orderby) )
			$orderby = 'tr.term_taxonomy_id';

		if ( !empty($orderby) )
			$orderby = "ORDER BY $orderby";

		$order = strtoupper( $order );
		if ( '' !== $order && ! in_array( $order, array( 'ASC', 'DESC' ) ) )
			$order = 'ASC';

		$taxonomies = "'" . implode("', '", $taxonomies) . "'";
		$object_ids = implode(', ', $object_ids);

		$select_this = '';
		if ( 'all' == $fields )
			$select_this = 't.*, tt.*';
		else if ( 'ids' == $fields )
			$select_this = 't.term_id';
		else if ( 'names' == $fields )
			$select_this = 't.name';
		else if ( 'slugs' == $fields )
			$select_this = 't.slug';
		else if ( 'all_with_object_id' == $fields )
			$select_this = 't.*, tt.*, tr.object_id';

		$query = "SELECT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tr.object_id IN ($object_ids) $orderby $order";

		if ( 'all' == $fields || 'all_with_object_id' == $fields ) {
			$_terms = $wpdb->get_results( $query );
			foreach ( $_terms as $key => $term ) {
				$_terms[$key] = sanitize_term( $term, $taxonomy, 'raw' );
			}
			$terms = array_merge( $terms, $_terms );
			update_term_cache( $terms );
		} else if ( 'ids' == $fields || 'names' == $fields || 'slugs' == $fields ) {
			$_terms = $wpdb->get_col( $query );
			$_field = ( 'ids' == $fields ) ? 'term_id' : 'name';
			foreach ( $_terms as $key => $term ) {
				$_terms[$key] = sanitize_term_field( $_field, $term, $term, $taxonomy, 'raw' );
			}
			$terms = array_merge( $terms, $_terms );
		} else if ( 'tt_ids' == $fields ) {
			$terms = $wpdb->get_col("SELECT tr.term_taxonomy_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tr.object_id IN ($object_ids) AND tt.taxonomy IN ($taxonomies) $orderby $order");
			foreach ( $terms as $key => $tt_id ) {
				$terms[$key] = sanitize_term_field( 'term_taxonomy_id', $tt_id, 0, $taxonomy, 'raw' ); // 0 should be the term id, however is not needed when using raw context.
			}
		}

		if ( ! $terms )
			$terms = array();

		/**
		 * Filter the terms for a given object or objects.
		 *
		 * @since 2.8.0
		 *
		 * @param array        $terms      An array of terms for the given object or objects.
		 * @param array|int    $object_ids Object ID or array of IDs.
		 * @param array|string $taxonomies A taxonomy or array of taxonomies.
		 * @param array        $args       An array of arguments for retrieving terms for
		 *                                 the given object(s).
		 */
		return apply_filters( 'wp_get_object_terms', $terms, $object_ids, $taxonomies, $args );
	}

	/**
	 * We need this function because WP checks if the taxonomy exist
	 * We are going to force the insertion
	 * 
	 * @param type $object_id 
	 * @param type $terms_ids 
	 * @return array|WP_Error Affected Term IDs
	 */
	private function set_object_terms( $object_id, $terms, $taxonomy ) {
		global $wpdb;

		$object_id = (int) $object_id;

		$old_tt_ids = array();

		$tt_ids = array();
		$term_ids = array();
		$new_tt_ids = array();

		foreach ( (array) $terms as $term ) {
			
			if ( ! $term_info = term_exists( $term, $taxonomy ) )
				continue;

			if ( is_wp_error( $term_info ) )
				continue;

			$term_ids[] = $term_info['term_id'];
			$tt_id = $term_info['term_taxonomy_id'];
			$tt_ids[] = $tt_id;

			if ( $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d", $object_id, $tt_id ) ) )
				continue;

			/**
			 * Fires immediately before an object-term relationship is added.
			 *
			 * @since 2.9.0
			 *
			 * @param int $object_id Object ID.
			 * @param int $tt_id     Term taxonomy ID.
			 */
			do_action( 'add_term_relationship', $object_id, $tt_id );
			$wpdb->insert( $wpdb->term_relationships, array( 'object_id' => $object_id, 'term_taxonomy_id' => $tt_id ) );

			/**
			 * Fires immediately after an object-term relationship is added.
			 *
			 * @since 2.9.0
			 *
			 * @param int $object_id Object ID.
			 * @param int $tt_id     Term taxonomy ID.
			 */
			do_action( 'added_term_relationship', $object_id, $tt_id );
			$new_tt_ids[] = $tt_id;
		}

		if ( $new_tt_ids )
			wp_update_term_count( $new_tt_ids, $taxonomy );


		wp_cache_delete( $object_id, $taxonomy . '_relationships' );

		/**
		 * Fires after an object's terms have been set.
		 *
		 * @since 2.8.0
		 *
		 * @param int    $object_id  Object ID.
		 * @param array  $terms      An array of object terms.
		 * @param array  $tt_ids     An array of term taxonomy IDs.
		 * @param string $taxonomy   Taxonomy slug.
		 * @param bool   $append     Whether to append new terms to the old terms.
		 * @param array  $old_tt_ids Old array of term taxonomy IDs.
		 */
		do_action( 'set_object_terms', $object_id, $terms, $tt_ids, $taxonomy, false, $old_tt_ids );
		return $tt_ids;
	}

	

}