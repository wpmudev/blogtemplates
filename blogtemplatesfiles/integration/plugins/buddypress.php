<?php

function nbt_bp_add_register_scripts() {
	?>
	<script>
		jQuery(document).ready(function($) {
			var bt_selector = $('#blog_template-selection').remove();
			bt_selector.appendTo( $('#blog-details') );
		});
	</script>
	<?php
}
add_action( 'bp_before_blog_details_fields', 'nbt_bp_add_register_scripts' );

add_action( 'bp_blog_details_fields', array( 'blog_templates', 'maybe_add_template_hidden_field' ) );
add_action( 'bp_after_blog_details_fields', array( 'blog_templates', 'registration_template_selection' ) );
add_filter( 'bp_signup_usermeta', array( 'blog_templates', 'registration_template_selection_add_meta' ) );