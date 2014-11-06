<?php

/** EASY GOOGLE FONTS **/
add_action( 'blog_templates-copy-after_copying', 'nbt_copy_easy_google_fonts_controls', 10, 2 );
function nbt_copy_easy_google_fonts_controls( $template, $destination_blog_id ) {
	global $wpdb;

	if ( ! is_plugin_active( 'easy-google-fonts/easy-google-fonts.php' ) )
		return;

	$source_blog_id = $template['blog_id'];

	if ( ! isset( $template['to_copy']['posts'] ) && get_blog_details( $source_blog_id ) && get_blog_details( $destination_blog_id ) ) {
		switch_to_blog( $source_blog_id );

		$post_query = "SELECT t1.* FROM {$wpdb->posts} t1 ";
		$post_query .= "WHERE t1.post_type = 'tt_font_control'";
		$posts_results = $wpdb->get_results( $post_query );

		$postmeta_query = "SELECT t1.* FROM {$wpdb->postmeta} t1 ";
		$postmeta_query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'tt_font_control'";
		$postmeta_results = $wpdb->get_results( $postmeta_query );
		
		restore_current_blog();

		switch_to_blog( $destination_blog_id );
		foreach ( $posts_results as $row ) {
            $row = (array)$row;
            $wpdb->insert( $wpdb->posts, $row );
        }

        foreach ( $postmeta_results as $row ) {
            $row = (array)$row;
            $wpdb->insert( $wpdb->postmeta, $row );
        }

        restore_current_blog();

	}
}