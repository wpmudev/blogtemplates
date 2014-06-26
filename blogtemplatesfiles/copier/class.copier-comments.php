<?php

class NBT_Template_Copier_Comments extends NBT_Template_Copier {

	public function __construct( $source_blog_id, $template, $args = array(), $user_id = 0 ) {
		parent::__construct( $source_blog_id, $template, $user_id );
	}

	public function get_default_args() {
		return array( 'update_date' => false );
	}

	public function copy() {
        global $wpdb;
        
        $current_comments = get_comments();
        foreach ( $current_comments as $comment ) {
            wp_delete_comment( $comment->comment_ID, true );
        }

        switch_to_blog( $this->source_blog_id );
        $_source_comments = get_comments();
        $source_comments = array();
        foreach ( $_source_comments as $source_comment ) {
            $item = $source_comment;
            $item->meta = get_comment_meta( $source_comment->comment_ID );
            $source_comments[] = $item;
        }
        restore_current_blog();

        $source_comments = apply_filters( 'blog_templates_source_comments', $source_comments, $this->source_blog_id, $this->user_id );

        $comments_remap = array();
        foreach ( $source_comments as $source_comment ) {
            $comment = (array)$source_comment;

            $source_comment_id = $comment['comment_ID'];
            unset( $comment['comment_ID'] );
            $new_comment_id = wp_insert_comment( $comment );

            if ( $new_comment_id )
                $comments_remap[ $source_comment_id ] = $new_comment_id;
        }

        // Now, let's remap the parent comments
        $comments = get_comments();
        foreach ( $comments as $_comment ) {
            $comment = (array)$_comment;

            if ( $comment['comment_parent'] && isset( $comments_remap[ $comment['comment_parent'] ] ) ) {
                $comment['comment_parent'] = $comments_remap[ $comment['comment_parent'] ];
                wp_update_comment( $comment );
            }
        }

        return true;
    }
		




	

}