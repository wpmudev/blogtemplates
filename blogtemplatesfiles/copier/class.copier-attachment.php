<?php

class NBT_Template_Copier_Attachment extends NBT_Template_Copier {

    public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );

        $this->args = wp_parse_args( $args, $this->get_default_args() );
	}

	public function get_default_args() {
        return array(
            'attachment_id' => false,
            'date' => null
        );
    }

	public function copy() {
        global $wpdb;

        $thumbnail_id = absint( $this->args['attachment_id'] );

        if ( ! $thumbnail_id )
            return new WP_Error( 'attachment_error', __( 'Wrong attachment specified', 'blog_templates') );

        $source_attachment = get_blog_post( $this->source_blog_id, $thumbnail_id );

        if ( ! $source_attachment )
            return new WP_Error( 'attachment_error', sprintf( __( 'Attachment ( ID= %d ) does not exist in the source blog', 'blog_templates'), $thumbnail_id ) );

        // Setting the new attachment properties
        $new_attachment = (array)$source_attachment;

        switch_to_blog( $this->source_blog_id );

        // Thanks to WordPress Importer plugin
		$url = wp_get_attachment_url( $thumbnail_id );

        if ( preg_match( '|^/[\w\W]+$|', $url ) )
            $url = rtrim( home_url(), '/' ) . $url;
        
        restore_current_blog();

        $upload = $this->fetch_remote_file( $url, $this->args['date'] );

        if ( is_wp_error( $upload ) )
            return $upload;

        if ( $info = wp_check_filetype( $upload['file'] ) )
            $new_attachment['post_mime_type'] = $info['type'];
        else
            return new WP_Error( 'filetype_error', __( 'Filetype error: ' . $url, 'blog_templates' ) );

        $new_attachment['guid'] = $upload['url'];
        unset( $new_attachment['ID'] );

        if ( ! function_exists( 'wp_generate_attachment_metadata' ) )
            include( ABSPATH . 'wp-admin/includes/image.php' );

        // Generate the new attachment
        $new_attachment_id = wp_insert_attachment( $new_attachment, $upload['file'] );
        wp_update_attachment_metadata( $new_attachment_id, wp_generate_attachment_metadata( $new_attachment_id, $upload['file'] ) );

        // Update featured image in posts
        $posts_ids = get_posts(
            array(
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'value' => $thumbnail_id
                    )
                ),
                'fields' => 'ids'
            )
        );

        foreach ( $posts_ids as $post_id )
            update_post_meta( $post_id, '_thumbnail_id', $new_attachment_id );

        return $url;

	}

    /**
     * Fetch an image and download it. Then create a new empty file for  it
     * that can be filled later
     * 
     * Code from WordPress Importer plugin
     * 
     * @return WP_Error/Array Image properties/Error
     */
    private function fetch_remote_file( $url, $date )  {
        $file_name = basename( $url );

        $upload = wp_upload_bits( $file_name, null, 0, $date );

        if ( $upload['error'] )
            return new WP_Error( 'upload_dir_error', $upload['error'] );

        $headers = wp_get_http( $url, $upload['file'] );

        // request failed
        if ( ! $headers ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf( __('Remote server did not respond for file: %s', 'blog_templates' ), $url ) );
        }

        // make sure the fetch was successful
        if ( $headers['response'] != '200' ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf( __('Remote server returned error response %1$d %2$s', 'blog_templates' ), esc_html($headers['response']), get_status_header_desc($headers['response']) ) );
        }

        $filesize = filesize( $upload['file'] );

        if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __('Remote file is incorrect size', 'blog_templates' ) );
        }

        if ( 0 == $filesize ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', __('Zero size file downloaded', 'blog_templates' ) );
        }

        $max_size = (int) apply_filters( 'blog_templates__attachment_size_limit', 0 );
        if ( ! empty( $max_size ) && $filesize > $max_size ) {
            @unlink( $upload['file'] );
            return new WP_Error( 'import_file_error', sprintf(__('Remote file is too large, limit is %s', 'blog_templates' ), size_format($max_size) ) );
        }

        return $upload;
    }

}