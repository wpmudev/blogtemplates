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

	add_filter( 'gform_get_form_filter', 'nbt_render_user_registration_form', 15, 2 );
	add_action( 'gform_user_registration_add_option_section', 'nbt_add_blog_templates_user_registration_option', 15 );
	add_filter( "gform_user_registration_save_config", "nbt_save_multisite_user_registration_config" );

	add_filter( 'gform_user_registration_new_site_meta', 'nbt_save_new_blog_meta' );
	add_filter( 'gform_user_registration_signup_meta', 'nbt_save_new_blog_meta' );
}

/**
 * Save the blog template meta when signing up/cerating a new blog
 * @param Array $meta Current meta
 * @return Array
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

/**
 * Display a new option for New Blog Templates
 * in User registration Form Settings Page
 * 
 * @param Array $config Current DForm attributes
 */
function nbt_add_blog_templates_user_registration_option( $config ) {

	if ( ! function_exists ( 'rgar' ) )
		return;


	$multisite_options = rgar($config['meta'], 'multisite_options');

	?>
		<div id="nbt-integration">
			<h3><?php _e( "New Blog Templates", 'blog_templates' ); ?></h3>
			<div class="margin_vertical_10">
                <label class="left_header"><?php _e( 'Display Templates Selector', 'blog_templates' ); ?></label>
                <input type="checkbox" id="gf_user_registration_multisite_blog_templates" name="gf_user_registration_multisite_blog_templates" value="1" <?php echo rgar( $multisite_options, 'blog_templates' ) ? "checked='checked'" : "" ?> />
            </div>
		</div>
	<?php
}

/**
 * Save the option for New Blog Templates
 * in User Registration Form Settings Page
 * 
 * @param Array $config Current Form attributes
 * @return Array
 */
function nbt_save_multisite_user_registration_config( $config ) {	
	if ( ! class_exists( 'RGForms' ) )
		return $config;

	$config['meta']['multisite_options']['blog_templates'] = RGForms::post("gf_user_registration_multisite_blog_templates");
	return $config;
}

/**
 * Display the templates selector form in the GF Form
 * 
 * @param String $form_html 
 * @param Array $form  Form attributes
 * @return String HTML Form content
 */
function nbt_render_user_registration_form( $form_html, $form ) {
	
	if ( ! class_exists( 'GFUserData' ) )
		return $form_html;
	
	// Let's check if the option for New Blog Templates is activated in this form
	$config = GFUserData::get_feed_by_form( $form['id'] );

	if ( empty( $config ) )
		return $form_html;

	$config = current( $config );
	
	$multisite_options = rgar( $config['meta'], 'multisite_options' );
	if ( isset( $multisite_options['blog_templates'] ) && absint( $multisite_options['blog_templates'] ) ) {
		ob_start();
		// Display the selector
		blog_templates::registration_template_selection();

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

	}

	return $form_html;
}

/**
 * Exclude Gravity Forms option AND Formidable Pro (thank you, wecreateyou/Stephanie).
 **/
function nbt_exclude_gravity_forms( $and ) {
	//$and .= " AND `option_name` != 'rg_forms_version'";
	$and .= " AND `option_name` != 'rg_forms_version'";
	return $and;
}
add_filter('blog_template_exclude_settings', 'nbt_exclude_gravity_forms');