<?php

function blog_templates_upgrade_19() {

	$options = get_site_option( 'blog_templates_options', array( 'templates' => array() ) );
	$model = blog_templates_model::get_instance();

	$default = isset( $options['default'] ) ? absint( $options['default'] ) : false;

	foreach ( $options['templates'] as $key => $template ) {
		$tmp_template = $template;
		
		$blog_id = $tmp_template['blog_id'];
		unset( $tmp_template['blog_id'] );
		$name = $tmp_template['name'];
		unset( $tmp_template['name'] );
		$description = $tmp_template['description'];
		unset( $tmp_template['description'] );

		//TODO: UNCOMMENT
		//$template_id = $model->add_template( $blog_id, $name, $description, $tmp_template );

		if ( $default === $key )
			$model->set_default_template( $template_id );
	}

	$new_options = array();
	$new_options['show-registration-templates'] = isset( $options['show-registration-templates'] ) ? $options['show-registration-templates'] : false;
	$new_options['registration-templates-appearance'] = isset( $options['registration-templates-appearance'] ) ? $options['registration-templates-appearance'] : '';
	$new_options['previewer_button_text'] = isset( $options['previewer_button_text'] ) ? $options['previewer_button_text'] : __( 'Select this theme', 'blog_templates' );

	// TODO: DELETE OLD OPTIONS AND SAVE NEW

}