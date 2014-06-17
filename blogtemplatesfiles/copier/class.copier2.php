<?php

abstract class NBT_Template_Copier {
	public function __construct( $source_blog_id, $template = array(), $user_id = 0 ) {
		$this->source_blog_id = $source_blog_id;
		$this->template = $template;
		$this->user_id = $user_id;
	}

	public abstract function get_default_args();
	public abstract function copy();

	public function clear_table( $table ) {
		global $wpdb;

        do_action( 'blog_templates-clearing_table', $table );

        $where = apply_filters( 'blog_templates-clear_table_where', "", $table );

        $wpdb->query( "DELETE FROM $table $where" );

        if ( $wpdb->last_error )
            return new WP_Error( 'deletion_error', sprintf( __( 'Deletion Error: %1$s - The template was not applied. (New Blog Templates - While clearing %2$s)', 'blog_templates' ), $wpdb->last_error, $table ) );

        return true;
	}

	/**
    * Added to automate comparing the two tables, and making sure no old fields that have been removed get copied to the new table
    *
    * @param mixed $new_table_name
    * @param mixed $old_table_row
    *
    * @since 1.0
    */
    function get_fields_to_remove( $new_table_name, $old_table_row ) {
        //make sure we have something to compare it to
        if ( empty( $old_table_row ) )
        	return false;

        //We need the old table row to be in array format, so we can use in_array()
        $old_table_row = (array)$old_table_row;

        global $wpdb;

        //Get the new table structure
        $new_table = (array)$wpdb->get_results( "SHOW COLUMNS FROM {$new_table_name}" );

        $new_fields = array();
        foreach( $new_table as $row ) {
            $new_fields[] = $row->Field;
        }

        $results = array();

        //Now, go through the columns in the old table, and check if there are any that don't show up in the new table
        foreach ( $old_table_row as $key => $value ) {
            if ( ! in_array( $key,$new_fields ) ) { //If the new table doesn't have this field
                //There's a column that isn't in the new one, make note of that
                $results[] = $key;
            }
        }

        //Return the results array, which should contain all of the fields that don't appear in the new table
        return $results;
    }
}