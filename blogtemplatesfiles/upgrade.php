<?php

function blog_templates_upgrade_19() {

	global $wpdb;

	$options = get_site_option( 'blog_templates_options', array( 'templates' => array() ) );
	$default = isset( $options['default'] ) ? absint( $options['default'] ) : false;

	foreach ( $options['templates'] as $key => $template ) {
		$tmp_template = $template;
		
		$blog_id = $tmp_template['blog_id'];
		unset( $tmp_template['blog_id'] );
		$name = $tmp_template['name'];
		unset( $tmp_template['name'] );
		$description = $tmp_template['description'];
		unset( $tmp_template['description'] );

		$tmp_template['screenshot'] = false;

		// Inserting templates
		$wpdb->insert( 
			$wpdb->base_prefix . 'nbt_templates',
			array(
				'blog_id' =>  $blog_id,
				'name' => $name,
				'description' => $description,
				'options' => maybe_serialize( $tmp_template )
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s'
			)
		);

		$template_id = $wpdb->insert_id;

		if ( $default === $key ) {
			$wpdb->update(
				$wpdb->base_prefix . 'nbt_templates',
				array( 'is_default' => 1 ),
				array( 'ID' => $id ),
				array( '%d' ),
				array( '%d' )
			);
		}

	}

	$new_options = array();
	$new_options['show-registration-templates'] = isset( $options['show-registration-templates'] ) ? $options['show-registration-templates'] : false;
	$new_options['registration-templates-appearance'] = isset( $options['registration-templates-appearance'] ) ? $options['registration-templates-appearance'] : '';
	$new_options['previewer_button_text'] = isset( $options['previewer_button_text'] ) ? $options['previewer_button_text'] : __( 'Select this theme', 'blog_templates' );
	$new_options['toolbar-color'] = isset( $options['toolbar-color'] ) ? $options['toolbar-color'] : '#8B8B8B';
	$new_options['toolbar-text-color'] = isset( $options['toolbar-text-color'] ) ? $options['toolbar-text-color'] : '#FFFFFF';
	$new_options['toolbar-border-color'] = isset( $options['toolbar-border-color'] ) ? $options['toolbar-border-color'] : '#333333';
	$new_options['show-categories-selection'] = isset($options['show-categories-selection']) ? $options['show-categories-selection'] : 0;

	update_site_option( 'blog_templates_options', $new_options );
}

function blog_templates_upgrade_191() {
	global $wpdb;

	$templates = $wpdb->get_results( "SELECT * FROM " . $wpdb->base_prefix . 'nbt_templates', ARRAY_A );

	if ( ! empty( $templates ) ) {
		$final_results = array();
		foreach ( $templates as $template ) {
			$final_results[$template['ID']] = $template;
			$final_results[$template['ID']]['options'] = maybe_unserialize( $template['options'] );
		}
		$templates = $final_results;
	}
	else {
		$templates = array();
	}
    
    foreach ( $templates as $key => $template ) {
        $options = $template['options'];
        $options['pages_ids'] = array( 'all-pages');
        $wpdb->update(
        	$wpdb->base_prefix . 'nbt_templates',
        	array( 'options' => maybe_serialize( $options ) ),
        	array( 'ID' => $template['ID'] ),
        	array( '%s' ),
        	array( '%d' )
        );
    }
}

function blog_templates_upgrade_20() {
	$model = nbt_get_model();
	$model->upgrade_20();

}

function blog_templates_upgrade_22() {
	global $wpdb;

	$model = nbt_get_model();
	$model->upgrade_22();

	$table = $model->templates_table;
	$results = $wpdb->get_results( "SELECT * FROM $table" );

	if ( ! empty( $results ) ) {
		foreach( $results as $template ) {
			$blog_id = $template->blog_id;
			$blog_details = get_blog_details( $blog_id );

			if ( ! empty( $blog_details ) ) {
				$network_id = $blog_details->site_id;
				$wpdb->update(
					$table,
					array( 'network_id' => $network_id ),
					array( 'ID' => $template->ID ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}
	}
}

function blog_templates_upgrade23() {
	$settings = nbt_get_settings();
	$model = nbt_get_model();

	if ( empty( $settings['templates'] ) ) {
		$settings['default'] = '';
	}
	else {
		foreach ( $settings['templates'] as $template ) {
			if ( $template['is_default'] )
				$settings['default'] = $template['ID'];
		}
	}

	nbt_update_settings( $settings );

}