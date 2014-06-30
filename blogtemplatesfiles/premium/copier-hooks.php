<?php

add_action( 'nbt_row_processed', 'nbt_maybe_block_posts', 10, 4 );
function nbt_maybe_block_posts( $row, $table, $source_blog_id, $args ) {
	if ( $table == 'posts' && ! empty( $args['block'] ) ) {
		update_post_meta( $row['ID'], 'nbt_block_post', true );
	}
}

add_filter( 'nbt_set_copier_args', 'nbt_copier_hooks_set_copier_args' );
function nbt_copier_hooks_set_copier_args( $option, $destination_blog_id, $template ) {
	$block = isset( $template['block_posts_pages'] ) && $template['block_posts_pages'] === true ? true : false;

	if ( $block ) {
		if ( array_key_exists( 'posts', $option['to_copy'] ) ) {
			$option['to_copy']['posts']['block'] = true;
		}
		if ( array_key_exists( 'pages', $option['to_copy'] ) ) {
			$option['to_copy']['pages']['block'] = true;
		}
	}

	return $option;
}