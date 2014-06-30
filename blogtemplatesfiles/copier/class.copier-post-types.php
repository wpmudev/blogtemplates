<?php

class NBT_Template_Copier_Post_Types extends NBT_Template_Copier {

    protected $type;
    protected $posts_copied = array();

    public function __construct( $source_blog_id, $template, $user_id = 0 ) {
        parent::__construct( $source_blog_id, $template, $user_id );
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

        do_action( 'blog_templates-copy-pages', $this->template, get_current_blog_id(), $this->user_id );
        do_action( 'blog_templates-copy-posts', $this->template, get_current_blog_id(), $this->user_id );

        $result = $this->copy_postmeta();
        if ( is_wp_error( $result ) ) {
            $wpdb->query("ROLLBACK;");
            return $result;
        }

        do_action( 'blog_templates-copy-pagemeta', $this->template, get_current_blog_id(), $this->user_id );
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

        // Posts
        switch_to_blog( $this->source_blog_id );
        $select = "SELECT p.* FROM {$wpdb->posts} p";
        restore_current_blog();

        
        $join = apply_filters( 'blog_templates_copy_post_table_query_join', "" );
        $group = apply_filters( 'blog_templates_copy_post_table_query_group', "" );
        $where = apply_filters( 'blog_templates_copy_post_table_query_where', "" );

        // Deprecated
        $where = apply_filters( 'nbt_copy_posts_table_where', $where, $this->type );

        switch_to_blog( $this->source_blog_id );

        $query = "$select $join $where $group";

        $results = $wpdb->get_results( $query );    

        restore_current_blog();

        foreach ( $results as $result ) {
            $this->posts_copied[] = $result->ID;
        }

        $table = $wpdb->posts;
        return $this->insert_table( $table, $results );    
    }

    public function copy_postmeta() {
        global $wpdb;

        $result = $this->clear_table( $wpdb->postmeta );

        if ( is_wp_error( $result ) )
            return $result;

        switch_to_blog( $this->source_blog_id );

        $posts_meta = array();
        foreach ( $this->posts_copied as $post_id ) {
            $posts_meta[ $post_id ] = get_post_meta( $post_id );
        }        

        restore_current_blog();

        foreach ( $posts_meta as $post_id => $post_meta ) {
            foreach ( $post_meta as $meta_key => $meta_value ) {
                update_post_meta( $post_id, $meta_key, maybe_unserialize( $meta_value[0] ) );
            }
        }

        return true;  
    }

    public function insert_table( $table, $rows ) {
        global $wpdb;

        if ( empty( $rows ) )
            return;

        $to_remove = $this->get_fields_to_remove( $table, $rows[0] );

        foreach ( $rows as $row ) {

            $row = (array)$row;

            $process = apply_filters( 'blog_templates-process_row', $row, str_replace( $wpdb->prefix, '', $table ), $this->source_blog_id, $this->args );
            
            if ( ! $process )
                continue;

            foreach ( $row as $key => $value ) {
                if ( in_array( $key, $to_remove ) )
                    unset( $row[$key] );
            }

            if ( $table == $wpdb->posts && isset( $this->args['update_date'] ) && $this->args['update_date'] ) {
                $current_time = current_time( 'mysql', false );
                $current_gmt_time = current_time( 'mysql', false );

                $process['post_date'] = $current_time;
                $process['post_modified'] = $current_time;
                $process['post_date_gmt'] = $current_gmt_time;
                $process['post_modified_gmt'] = $current_gmt_time;

                // Retrocompatibility
                do_action('blog_templates-update-pages-dates', $this->template, get_current_blog_id(), $this->user_id );
                do_action('blog_templates-update-posts-dates', $this->template, get_current_blog_id(), $this->user_id );
            }

            $wpdb->insert( $table, $process );


            if ( ! empty( $wpdb->last_error ) )
                return new WP_Error( 'insertion_error', sprintf( __( 'Insertion Error: %1$s - Posts have not been copied. (While copying %2$s)', 'blog_templates' ), $wpdb->last_error, $table ) );

            do_action( 'nbt_row_processed', $row, str_replace( $wpdb->prefix, '', $table ), $this->source_blog_id, $args );
        }

        return true;
    }

}