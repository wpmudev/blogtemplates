<div id="blog_template-selection">
	<h3><?php _e('Select a template', 'blog_templates') ?></h3>
	<?php
		if ( class_exists( 'BuddyPress' ) ) {
			$sign_up_url = bp_get_signup_page();
		}
		else {
			$sign_up_url = network_site_url( 'wp-signup.php' );
			$sign_up_url = apply_filters( 'wp_signup_location', $sign_up_url );
		}
		$sign_up_url = add_query_arg( 'blog_template', 'just_user', $sign_up_url );
	?>
	<p><a href="<?php echo esc_url( $sign_up_url ); ?>"><?php _e('Just a username, please.') ?></a></p>
	<?php
		if ( $settings['show-categories-selection'] ) {
			$toolbar = new blog_templates_theme_selection_toolbar( $settings['registration-templates-appearance'] );
		    $toolbar->display();
		}
    ?>
    
	<div class="blog_template-option">
		
	<?php 
	foreach ( $templates as $tkey => $template ) {
		nbt_render_theme_selection_item( 'page-showcase', $tkey, $template, $settings );
	}
	?>
	<div style="clear:both;"></div>
	</div>
</div>