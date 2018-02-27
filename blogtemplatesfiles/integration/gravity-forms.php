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

	$user_registration = gf_user_registration();
	$feeds = $user_registration->get_active_feeds( $form['id'] );

	if ( empty( $feeds ) ) {
		return $button_input;
	}

	foreach ( $feeds as $feed_key => $feed ) {
		
		// If at least one feed contains blog_template option, template selector will be included
		if ( isset( $feed['meta']['gf_user_registration_multisite_blog_templates'] ) && 
			 absint( $feed['meta']['gf_user_registration_multisite_blog_templates'] ) ) {

			$form_html = '';
			ob_start();
			
			$blog_templates->registration_template_selection();

			$nbt_selection = ob_get_clean();

			$form_html .= '<div id="gf_nbt_selection" style="display:none">' . $nbt_selection . '</div>';
			$form_id = $form['id'];

			ob_start();
			// Adding some Javascript
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					var submit_button = $( '#gform_submit_button_' + <?php echo $form_id; ?> );

					$('#blog_template-selection').insertBefore( submit_button );
				});
			</script>
			<?php
			$form_html .= ob_get_clean();

			$button_input = $form_html . $button_input;
			break;

		}	
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