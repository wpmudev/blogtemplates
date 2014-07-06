<?php

// Other plugins integrations
add_action( 'bp_before_blog_details_fields', 'nbt_bp_add_register_scripts' );
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

add_action( 'plugins_loaded', 'nbt_appplus_unregister_action' );
function nbt_appplus_unregister_action() {
	if ( class_exists('Appointments' ) ) {
		global $appointments;
		remove_action( 'wpmu_new_blog', array( $appointments, 'new_blog' ), 10, 6 );
	}
}

// Framemarket theme
add_filter( 'framemarket_list_shops', 'nbt_framemarket_list_shops' );
function nbt_framemarket_list_shops( $blogs ) {
	$return = array();

	if ( ! empty( $blogs ) ) {
		$model = nbt_get_model();
		foreach ( $blogs as $blog ) {
			if ( ! $model->is_template( $blog->blog_id ) )
				$return[] = $blog;
		}
	}

	return $return;
}

add_filter( 'blogs_directory_blogs_list', 'nbt_remove_blogs_from_directory' );
function nbt_remove_blogs_from_directory( $blogs ) {
	$model = nbt_get_model();
	$new_blogs = array();
	foreach ( $blogs as $blog ) {
		if ( ! $model->is_template( $blog['blog_id'] ) )
			$new_blogs[] = $blog;
	}
	return $new_blogs;
}

/** EASY GOOGLE FONTS **/
function nbt_copy_easy_google_fonts_controls( $template, $source_blog_id ) {
	global $wpdb;

	if ( ! is_plugin_active( 'easy-google-fonts/easy-google-fonts.php' ) )
		return;

	if ( ! isset( $template['to_copy']['posts'] ) && get_blog_details( $source_blog_id ) ) {
		switch_to_blog( $source_blog_id );

		$post_query = "SELECT t1.* FROM {$wpdb->posts} t1 ";
		$post_query .= "WHERE t1.post_type = 'tt_font_control'";
		$posts_results = $wpdb->get_results( $post_query );

		$postmeta_query = "SELECT t1.* FROM {$wpdb->postmeta} t1 ";
		$postmeta_query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'tt_font_control'";
		$postmeta_results = $wpdb->get_results( $postmeta_query );
		
		restore_current_blog();

		foreach ( $posts_results as $row ) {
            $row = (array)$row;
            $wpdb->insert( $wpdb->posts, $row );
        }

        foreach ( $postmeta_results as $row ) {
            $row = (array)$row;
            $wpdb->insert( $wpdb->postmeta, $row );
        }

	}
}
add_action( 'wpmudev_copier-copy-after_copying', 'nbt_copy_easy_google_fonts_controls', 10, 2 );



/** GRAVITY FORMS **/

// Triggered when New Blog Templates class is created
add_action( 'nbt_object_create', 'nbt_set_gravity_forms_hooks' );


/**
 * Set all hooks needed for GF Integration
 * 
 * @param blog_templates $blog_templates Object 
 */
function nbt_set_gravity_forms_hooks( $blog_templates ) {
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
	if ( isset( $_POST['blog_template' ] ) ) {
		$meta['blog_template'] = absint( $_POST['blog_template'] );
	}
	return $meta;
}

/**
 * Display a new option for New Blog Templates
 * in User registration Form Settings Page
 * 
 * @param Array $config Current DForm attributes
 */
function nbt_add_blog_templates_user_registration_option( $config ) {

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

	global $blog_templates;
	
	// Let's check if the option for New Blog Templates is activated in this form
	$config = GFUserData::get_feed_by_form( $form['id'] );

	if ( empty( $config ) )
		return $form_html;

	$config = current( $config );
	
	$multisite_options = rgar( $config['meta'], 'multisite_options' );
	if ( isset( $multisite_options['blog_templates'] ) && absint( $multisite_options['blog_templates'] ) ) {
		ob_start();
		// Display the selector
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

	}

	return $form_html;
}

/**
 * Exclude Gravity Forms option AND Formidable Pro (thank you, wecreateyou/Stephanie).
 **/
function blog_template_exclude_gravity_forms( $exclude ) {
	//$and .= " AND `option_name` != 'rg_forms_version'";
	$exclude[] = 'rg_forms_version';
	$exclude[] = 'frm_db_version';
	$exclude[] = 'frmpro_db_version';
	return $exclude;
}
add_filter('blog_templates_exclude_settings', 'blog_template_exclude_gravity_forms');


/** CONTACT FORM 7 **/

/**
 * Exclude Contact Form 7 postmeta fields 
 */
function blog_template_contact_form7_postmeta ($row, $table) {
	if ("postmeta" != $table) return $row;
	
	$key = @$row['meta_key'];
	$wpcf7 = array('mail', 'mail_2');
	if (defined('NBT_PASSTHROUGH_WPCF7_MAIL_FIELDS') && NBT_PASSTHROUGH_WPCF7_MAIL_FIELDS) return $row;
	if (defined('NBT_CONVERT_WPCF7_MAIL_FIELDS') && NBT_CONVERT_WPCF7_MAIL_FIELDS) return blog_template_convert_wpcf7_mail_fields($row);
	if (in_array($key, $wpcf7)) return false;
	
	return $row;
}
function blog_template_convert_wpcf7_mail_fields ($row) {
	global $_blog_template_current_templated_blog_id;
	if (!$_blog_template_current_templated_blog_id) return $row; // Can't do the replacement

	$value = @$row['meta_value'];
	$wpcf7 = $value ? unserialize($value) : false;
	if (!$wpcf7) return $row; // Something went wrong

	// Get convertable values
	switch_to_blog($_blog_template_current_templated_blog_id);
	$admin_email = get_option("admin_email");
	$site_url = get_bloginfo('url');
	// ... more stuff at some point
	restore_current_blog();

	// Get target values
	$new_site_url = get_bloginfo('url');
	$new_admin_email = get_option('admin_email');
	// ... more stuff at some point
	
	// Do the replace
	foreach ($wpcf7 as $key => $val) {
		$val = preg_replace('/' . preg_quote($site_url, '/') . '/i', $new_site_url, $val);
		$val = preg_replace('/' . preg_quote($admin_email, '/') . '/i', $new_admin_email, $val);
		$wpcf7[$key] = $val;
	}

	// Right, so now we have the replaced array - populate it.
	$row['meta_value'] = serialize($wpcf7);
	return $row;
}
function blog_template_check_postmeta ($tbl, $templated_blog_id) {
	global $_blog_template_current_templated_blog_id;
	$_blog_template_current_templated_blog_id = $templated_blog_id;
	if ("postmeta" == $tbl) add_filter('blog_templates-process_row', 'blog_template_contact_form7_postmeta', 10, 2);
}
add_action('blog_templates-copying_table', 'blog_template_check_postmeta', 10, 2);


// Play nice with Multisite Privacy, if requested so
if (defined('NBT_TO_MULTISITE_PRIVACY_ALLOW_SIGNUP_OVERRIDE') && NBT_TO_MULTISITE_PRIVACY_ALLOW_SIGNUP_OVERRIDE) {
	/**
	 * Keeps user-selected Multisite Privacy settings entered on registration time.
	 * Propagate template settings on admin blog creation time.
	 */
	function blog_template_exclude_multisite_privacy_settings ($exclude) {
		$user = wp_get_current_user();
		if ( is_super_admin( $user->ID ) ) 
			return $exclude;

		$exclude[] = 'spo_settings';
		$exclude[] = 'blog_public';
		return $exclude;
	}
	add_filter('blog_templates_exclude_settings', 'blog_template_exclude_multisite_privacy_settings');
}
