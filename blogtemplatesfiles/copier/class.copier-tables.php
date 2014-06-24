<?php


class NBT_Template_Copier_Tables extends NBT_Template_Copier {

	public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );

        $this->args = wp_parse_args( $args, $this->get_default_args() );
	}

	public function get_default_args() {
		return array(
            'tables' => array(),
            'create_tables' => false
        );
	}

	public function copy() {
		global $wpdb;

        // Prefixes
        $new_prefix = $wpdb->prefix;
        $template_prefix = $wpdb->get_blog_prefix( $this->source_blog_id );

        $tables_to_copy = $this->args['tables'];

        // If create_tables = true, we'll need at least to create all the tables
        // Empty or not
        if ( $this->args['create_tables'] )
            $all_source_tables = wp_list_pluck( nbt_get_additional_tables( $this->source_blog_id ), 'prefix.name' );
        else
            $all_source_tables = $tables_to_copy;

        $all_source_tables = apply_filters( 'nbt_copy_additional_tables', $all_source_tables );

        foreach ( $all_source_tables as $table ) {
            // Copy content too?
            $add = in_array( $table, $tables_to_copy );

            $table = esc_sql( $table );

            // MultiDB Hack
            if ( is_a( $wpdb, 'm_wpdb' ) )
                $tablebase = end( explode( '.', $table, 2 ) );
            else
                $tablebase = $table;

            $new_table = $new_prefix . substr( $tablebase, strlen( $template_prefix ) );

            $result = $wpdb->get_results( "SHOW TABLES LIKE '{$new_table}'", ARRAY_N );
            if ( ! empty( $result ) ) {
                // The table is already present in the new blog
                // Clear it
                $this->clear_table( $tablebase );

                if ( $add ) {
                    // And copy the content if needed
                    $this->copy_table( $this->template['blog_id'], $tablebase );
                }
            }
            else {
                // The table does not exist in the new blog
                // Let's create it
                $create_script = current( $wpdb->get_col( 'SHOW CREATE TABLE ' . $table, 1 ) );

                if ( $create_script && preg_match( '/\(.*\)/s', $create_script, $match ) ) {
                    $table_body = $match[0];
                    $wpdb->query( "CREATE TABLE IF NOT EXISTS {$new_table} {$table_body}" );

                    if ( $add ) {
                        // And copy the content if needed
                        if ( is_a( $wpdb, 'm_wpdb' ) ) {
                            $rows = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
                            foreach ( $rows as $row ) {
                                $wpdb->insert( $new_table, $row );
                            }
                        } else {
                            $wpdb->query( "INSERT INTO {$new_table} SELECT * FROM {$table}" );
                        }
                    }

                }

                if ( ! empty( $wpdb->last_error ) ) {
                    $error = new WP_Error( 'insertion_error', sprintf( __( 'Insertion Error: %s', 'blog_templates' ), $wpdb->last_error ) );
                    $wpdb->query("ROLLBACK;");
                    return $error;
                }
            }

        }

        $wpdb->query("COMMIT;");
        return true;
	}




	

}