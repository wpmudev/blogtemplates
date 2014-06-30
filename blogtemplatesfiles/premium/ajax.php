<?php

function nbt_filter_categories() {
	$cat_id = absint( $_POST['category_id'] );
	$type = $_POST['type'];

	$model = nbt_get_model();
	$templates = $model->get_templates_by_category( $cat_id );

	$options = nbt_get_settings();
	$checked = isset( $options['default'] ) ? $options['default'] : '';

	if ( '' === $type ) {
		echo '<select name="blog_template">';
		if ( empty( $checked ) ) {
   			echo '<option value="none">' . __( 'None', 'blog_templates' ) . '</option>';
   		}
	}


	foreach( $templates as $tkey => $template ) {
		nbt_render_theme_selection_item( $type, $template['ID'], $template, $options );
	}

	if ( '' === $type )
		echo '</select>';
	else
		echo '<div style="clear:both"></div>';

	die();
}
add_action( 'wp_ajax_nbt_filter_categories', 'nbt_filter_categories' );
add_action( 'wp_ajax_nopriv_nbt_filter_categories', 'nbt_filter_categories' );