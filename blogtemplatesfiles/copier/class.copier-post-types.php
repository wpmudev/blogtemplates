<?php

include_once('class.copier2.php' );
class NBT_Template_Copier_Post_Types extends NBT_Template_Copier {

	protected $type;

	public function __construct( $source_blog_id, $template, $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id = 0 );
	}

	public function get_default_args() {}

	public function copy() {
		global $wpdb;

		$wpdb->query( "BEGIN;" );

		$result = $this->copy_posts();

		if ( is_wp_error( $result ) ) {
        	$wpdb->query("ROLLBACK;");
        	return $result;
        }

        do_action( 'blog_templates-copy-posts', $this->template, get_current_blog_id(), $this->user_id );

        $result = $this->copy_postmeta();
        if ( is_wp_error( $result ) ) {
        	$wpdb->query("ROLLBACK;");
        	return $result;
        }

        do_action( 'blog_templates-copy-postmeta', $this->template, get_current_blog_id(), $this->user_id );

        $wpdb->query("COMMIT;");

        return true;
	}

	public function copy_posts() {
		global $wpdb;

		do_action( 'blog_templates-copying_table', $this->type, $this->source_blog_id );

		$result = $this->clear_table( $wpdb->posts );

		if ( is_wp_error( $result ) )
			return $result;

		switch_to_blog( $this->source_blog_id );

		// Posts
		$select = "SELECT p.* FROM {$wpdb->posts} p";

		if ( $this->type == 'page' )
			$where = "WHERE p.post_type = 'page'";
		else
			$where = "WHERE p.post_type NOT IN ( 'page', 'attachment', 'revision', 'nav_menu_item' )";

		$join = "";
		if ( is_array( $this->args['categories'] ) && count( $this->args['categories'] ) > 0 ) {
            $join = "INNER JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID ";

            $categories_list = '(' . implode( ',', $this->args['categories'] ) . ')';
            $where .= " AND tr.term_taxonomy_id IN $categories_list";
            $group = "GROUP BY p.ID";
        }

        $where = apply_filters( 'nbt_copy_posts_table_where', $where, $this->type );

        $query = "$select $join $where $group";
        $results = $wpdb->get_results( $query );    

        restore_current_blog();

        $table = $wpdb->posts;
        return $this->insert_table( $table, $results );    
	}

	public function copy_postmeta() {
		global $wpdb;

		$result = $this->clear_table( $wpdb->postmeta );

		if ( is_wp_error( $result ) )
			return $result;

		switch_to_blog( $this->source_blog_id );

		$select = "SELECT pm.* FROM {$wpdb->postmeta} pm";

		$join = "INNER JOIN $wpdb->posts p ON pm.post_id = p.ID";

		if ( $this->type == 'page' )
			$where = "WHERE p.post_type = 'page'";
		else
			$where = "WHERE p.post_type NOT IN ( 'page', 'attachment', 'revision', 'nav_menu_item' )";

        $where = apply_filters( 'nbt_copy_postmeta_table_where', $where, $this->type );

        $query = "$select $join $where";
        $results = $wpdb->get_results( $query );        

        restore_current_blog();

        $table = $wpdb->postmeta;
        return $this->insert_table( $table, $results );  
	}

	public function insert_table( $table, $rows ) {
		global $wpdb;
		
		$to_remove = $this->get_fields_to_remove( $table, $rows[0] );

		foreach ( $rows as $row ) {

			$row = (array)$row;

            $process = apply_filters( 'blog_templates-process_row', $row, str_replace( $wpdb->prefix, '', $table ), $this->source_blog_id );
            
            if ( ! $process )
            	continue;

            foreach ( $row as $key => $value ) {
                if ( in_array( $key, $to_remove ) )
                    unset( $row[$key] );
            }

            if ( $table == $wpdb->posts && $this->args['update_date'] ) {
            	$current_time = current_time( 'mysql', false );
        		$current_gmt_time = current_time( 'mysql', false );

            	$process['post_date'] = $current_time;
            	$process['post_modified'] = $current_time;
            	$process['post_date_gmt'] = $current_gmt_time;
            	$process['post_modified_gmt'] = $current_gmt_time;
            }

            $wpdb->insert( $table, $process );

            if ( $table == $wpdb->posts && $this->args['block'] ) {
            	update_post_meta( $process['ID'], 'nbt_block_post', true );
            }


            if ( ! empty( $wpdb->last_error ) )
            	return new WP_Error( 'insertion_error', sprintf( __( 'Insertion Error: %1$s - Posts have not been copied. (While copying %2$s)', 'blog_templates' ), $wpdb->last_error, $table ) );

        }

        return true;
	}

}