<?php

/** GRAVITY FORMS **/

// Triggered when New Blog Templates class is created
add_action( 'nbt_object_create', 'set_gravity_forms_hooks' );

/**
 * Set all hooks needed for GF Integration
 *
 * @param blog_templates $blog_templates Object
 */
function set_gravity_forms_hooks( $blog_templates ) {
	if ( ! function_exists( 'is_plugin_active' ) )
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( ! is_plugin_active( 'gravityformsuserregistration/userregistration.php' ) || ! is_plugin_active( 'gravityforms/gravityforms.php' ) )
		return;

	add_filter( 'gform_user_registration_new_site_meta', 'nbt_save_new_blog_meta' );
	add_filter( 'gform_user_registration_signup_meta', 'nbt_save_new_blog_meta' );

	add_filter( 'gform_userregistration_feed_settings_fields', 'nbt_gf_userregistration_feed_settings' );
	add_filter( 'gform_submit_button', 'nbt_gf_form_render', 10, 2 );
}

/**
 * Display the templates selector form in the GF Form
 *
 * @param string $button_input
 * @param array $form  Form attributes
 *
 * @return string
 */
function nbt_gf_form_render( $button_input, $form ) {
	global $blog_templates;

	if ( ! class_exists( 'GFUserData' ) ) {
		return $button_input;
	}

	$config = GFUserData::get_feed_by_form( $form['id'] );

	if ( empty( $config ) ) {
		return $button_input;
	}

	$config = $config[0];

	if ( isset( $config['meta']['gf_user_registration_multisite_blog_templates'] ) && absint( $config['meta']['gf_user_registration_multisite_blog_templates'] ) ) {
		$form_html = '';
		ob_start();

		// Display the selector
		$blog_templates->registration_template_selection();

		$nbt_selection = ob_get_contents();
        ob_end_clean();

		//Show for each field
		return $nbt_selection .'<br/>'. $button_input;

	}

	return $button_input;
}

function nbt_gf_userregistration_feed_settings( $settings ) {
	$settings['nbt'] = array(
		'title' => __( 'New Blog Templates', 'blog_templates' ),
		'description' => '',
		'dependency' => array(),
		'fields' => array(
			array(
				'name' => 'gf_user_registration_multisite_blog_templates',
				'label' => __( 'Display Templates Selector', 'blog_templates' ),
				'type' => 'checkbox',
				'choices' => array(
					array(
						'label' => __( 'Display Templates Selector', 'blog_templates' ),
						'value' => 0,
						'name' => 'gf_user_registration_multisite_blog_templates',
						'default_value' => 0
					)
				)
			)
		)
	);

	// Move the save section to the end
	$save = $settings['save'];
	unset( $settings['save'] );
	$settings['save'] = $save;
	return $settings;
}
/**
 * Save the blog template meta when signing up/cerating a new blog
 * @param array $meta Current meta
 * @return array
 */
function nbt_save_new_blog_meta( $meta ) {

	$model = nbt_get_model();

	if ( isset( $_POST['blog_template' ] ) && $model->get_template( absint( $_POST['blog_template'] ) ) )
		$meta['blog_template'] = absint( $_POST['blog_template'] );

	// Maybe GF is activating a signup instead
	if ( empty( $meta['blog_template'] ) && isset( $_REQUEST['key'] ) && class_exists( 'GFSignup' ) ) {
		$signup = GFSignup::get( $_REQUEST['key'] );
		if ( ! is_wp_error( $signup ) && ! empty( $signup->meta['blog_template'] ) ) {
			$meta['blog_template'] = $signup->meta['blog_template'];
		}
		elseif ( ! empty( $signup->error_data['already_active']->meta ) ) {
			// A little hack for GF
			$_meta = maybe_unserialize( $signup->error_data['already_active']->meta );
			if ( ! empty( $_meta['blog_template'] ) )
				$meta['blog_template'] = $_meta['blog_template'];
		}

	}


	$default_template_id = $model->get_default_template_id();

	if ( empty( $meta['blog_template'] ) && $default_template_id )
		$meta['blog_template'] = $default_template_id;

	return $meta;
}




