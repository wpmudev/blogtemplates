<?php
/*
Plugin Name: New Blog Templates
Plugin URI: http://premium.wpmudev.org/project/new-blog-template
Description: Allows the site admin to create new blogs based on templates, to speed up the blog creation process
Author: WPMU DEV
Author URI: http://premium.wpmudev.org/
Version: 2.8.7-beta-1
Network: true
Text Domain: blog_templates
Domain Path: lang
WDP ID: 130
*/

/*  Copyright 2010-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'NBT_PLUGIN_VERSION', '2.8.4' );
if ( ! is_multisite() )
	exit( __( 'The New Blog Template plugin is only compatible with WordPress Multisite.', 'blog_templates' ) );

define( 'NBT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NBT_PLUGIN_LANG_DOMAIN', 'blog_templates' );


require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/helpers.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/filters.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/model.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates_theme_selection_toolbar.php' );

require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/ajax.php' );
if ( is_network_admin() ) {
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/admin/main_menu.php' );
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/admin/categories_menu.php' );
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/admin/settings_menu.php' );
}

require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/integration.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates_lock_posts.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/settings-handler.php' );

include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/externals/wpmudev-dash-notification.php' );
global $wpmudev_notices;
$wpmudev_notices[] = array( 'id'=> 130,'name'=> 'New Blog Templates', 'screens' => array( 'toplevel_page_blog_templates_main-network', 'blog-templates_page_blog_templates_categories-network', 'blog-templates_page_blog_templates_settings-network' ) );

if ( is_network_admin() ) {
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/tables/templates_table.php' );
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/tables/categories_table.php' );
	
}

/**
 * Load the plugin text domain and MO files
 * 
 * These can be uploaded to the main WP Languages folder
 * or the plugin one
 */
function nbt_load_text_domain() {

	$locale = apply_filters( 'plugin_locale', get_locale(), NBT_PLUGIN_LANG_DOMAIN );

	load_textdomain( NBT_PLUGIN_LANG_DOMAIN, WP_LANG_DIR . '/' . NBT_PLUGIN_LANG_DOMAIN . '/' . NBT_PLUGIN_LANG_DOMAIN . '-' . $locale . '.mo' );
	load_plugin_textdomain( NBT_PLUGIN_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'nbt_load_text_domain' );


function nbt_get_default_screenshot_url( $blog_id ) {
	switch_to_blog($blog_id);
	$img = untrailingslashit(dirname(get_stylesheet_uri())) . '/screenshot.png';
	restore_current_blog();	
	return $img;
}

function nbt_display_page_showcase( $content ) {
	if ( is_page() ) {
		$settings = nbt_get_settings();
		if ( 'page_showcase' == $settings['registration-templates-appearance'] && is_page( $settings['page-showcase-id'] ) && is_main_site() ) {
			
            $tpl_file = "blog_templates-registration-page-showcase.php";
            $templates = $settings['templates'];

            // Setup theme file
            ob_start();
            $theme_file = locate_template( array( $tpl_file ) );
            $theme_file = $theme_file ? $theme_file : NBT_PLUGIN_DIR . 'blogtemplatesfiles/template/' . $tpl_file;
            if ( ! file_exists( $theme_file ) ) 
                return false;

            nbt_render_theme_selection_scripts( $settings );

            
            @include $theme_file;
			
			$content .= ob_get_clean();
			
		}
	}

	return $content;
}
add_filter( 'the_content', 'nbt_display_page_showcase' );

function nbt_get_showcase_redirection_location( $location = false ) {
	$settings = nbt_get_settings();

	if ( ! $settings['show-registration-templates'] ) {
		return false;
	}

	if ( 'page_showcase' !== $settings['registration-templates-appearance'] )
		return $location;

	$redirect_to = get_permalink( $settings['page-showcase-id'] );
	if ( ! $redirect_to )
		return $location;

	if ( isset( $_REQUEST['blog_template'] ) && 'just_user' == $_REQUEST['blog_template'] )
		return $location;

	$model = nbt_get_model();
	$default_template_id = $model->get_default_template_id();

	if ( empty( $_REQUEST['blog_template'] ) && ! $default_template_id ) {
		return $redirect_to;
	}

	$_REQUEST['blog_template'] = ! empty( $_REQUEST['blog_template'] ) ? absint( $_REQUEST['blog_template'] ) : $default_template_id;

	$model = nbt_get_model();
	$template = $model->get_template( $_REQUEST['blog_template'] );

	if ( ! $template ) {
		return $redirect_to;
	}

	return $location;
}

function nbt_redirect_signup() {
	global $pagenow;

	if ( 'wp-signup.php' == $pagenow ) {

		$redirect_to = nbt_get_showcase_redirection_location();

		if ( $redirect_to ) {
			wp_redirect( $redirect_to );
			exit();
		}

	}	
}
add_action( 'init', 'nbt_redirect_signup', 5 );

function nbt_bp_redirect_signup_location() {
	if ( ! class_exists( 'BuddyPress' ) )
		return;

	if ( is_admin() || ! bp_has_custom_signup_page() )
		return;

	// If the user has selected a template, do not redirect.
	if ( isset( $_GET['blog_template'] ) )
		return;

	$signup_slug = bp_get_signup_slug();
	if ( ! $signup_slug )
		return;

	$page = get_posts( 
		array(
			'name' => $signup_slug,
			'post_type' => 'page'
		)
	);

	if ( empty( $page ) )
		return;

	$page = $page[0];
	$is_bp_signup_page = is_page( $page->ID );

	if ( $is_bp_signup_page ) {
		$redirect_to = nbt_get_showcase_redirection_location();
		if ( $redirect_to ) {
			wp_redirect( $redirect_to );
			exit();
		}
	}
	
}
add_action( 'template_redirect', 'nbt_bp_redirect_signup_location', 15 );

function nbt_render_theme_selection_item( $type, $tkey, $template, $options = array() ) {

	// First try to get selected template from $_POST.
	$selected = isset( $_POST['blog_template'] ) ? absint( $_POST['blog_template'] ) : '';
	// If not found, try the whole $_REQUEST.
	$selected = empty( $selected ) && isset( $_REQUEST['blog_template'] ) ? absint( $_REQUEST['blog_template'] ) : $selected;

	if ( $selected == $tkey ) {
		$default = "blog_template-default_item";
	}
	else {
		$default = @$options['default'] == $tkey ? "blog_template-default_item" : "";
	}

	// If in case template is in wrong format.
	if ( isset( $template['options'] ) ) {
		$template = array_merge( $template, $template['options'] );
		unset( $template['options'] );
	}

	if ( 'previewer' == $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbt_get_default_screenshot_url( $template['blog_id'] );
		$tplid = $template['name'];
		$blog_url = get_site_url( $template['blog_id'], '', 'http' );
		?>
			<div class="template-signup-item theme-previewer-wrap <?php echo $default; ?>" data-tkey="<?php echo $tkey; ?>" id="theme-previewer-wrap-<?php echo $tkey;?>">
				
				<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector">
					<img src="<?php echo $img;?>" />
					<input type="radio" name="blog_template" id="blog-template-radio-<?php echo $tkey;?>" <?php checked( ! empty( $default ) ); ?> value="<?php echo $tkey;?>" style="display: none" />
				</a>
				<div class="theme-previewer-overlay">
					<div class="template-name"><?php echo $tplid; ?></div><br/>
					<button rel="nofollow" class="view-demo-button" data-blog-url="<?php echo $blog_url;?>"><?php _e( 'View demo', 'blog_templates' ); ?></button><br/><br/>
					<button class="select-theme-button" data-theme-key="<?php echo $tkey;?>"><?php echo $options['previewer_button_text']; ?></button>
				</div>
				
				<?php if ( ! empty( $template['description'] ) ): ?>
					<div id="nbt-desc-pointer-<?php echo $tkey; ?>" class="nbt-desc-pointer">
						<?php echo nl2br($template['description']); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php
	}
	elseif ( 'page-showcase' == $type || 'page_showcase' == $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbt_get_default_screenshot_url( $template['blog_id'] );
		$tplid = $template['name'];
		$blog_url = get_site_url( $template['blog_id'], '', 'http' );

		if ( class_exists( 'BuddyPress' ) ) {
			$sign_up_url = bp_get_signup_page();
		}
		else {
			$sign_up_url = network_site_url( 'wp-signup.php' );
			$sign_up_url = apply_filters( 'wp_signup_location', $sign_up_url );
		}
		$sign_up_url = add_query_arg( 'blog_template', $tkey, $sign_up_url );
		?>
			<div class="template-signup-item theme-page-showcase-wrap <?php echo $default; ?>" data-tkey="<?php echo $tkey; ?>" id="theme-page-showcase-wrap-<?php echo $tkey;?>">
				
				<a href="#<?php echo $tplid; ?>" class="blog_template-item_selector">
					<img src="<?php echo $img;?>" />
				</a>
				<div class="theme-page-showcase-overlay">
					<div class="template-name"><?php echo $tplid; ?></div><br/>
					<button rel="nofollow" class="view-demo-button" data-blog-url="<?php echo $blog_url;?>"><?php _e( 'View demo', 'blog_templates' ); ?></button><br/><br/>
					<button class="select-theme-button" data-signup-url="<?php echo esc_url( $sign_up_url );?>"><?php echo $options['previewer_button_text']; ?></button>
				</div>
				
				<?php if ( ! empty( $template['description'] ) ): ?>
					<div id="nbt-desc-pointer-<?php echo $tkey; ?>" class="nbt-desc-pointer">
						<?php echo nl2br($template['description']); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php
	}
	elseif ( 'screenshot' === $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbt_get_default_screenshot_url( $template['blog_id'] );
		$tplid = preg_replace('/[^a-z0-9]/i', '', strtolower($template['name'])) . "-{$tkey}";
		?>
			<div class="template-signup-item theme-screenshot-wrap <?php echo $default; ?>" data-tkey="<?php echo $tkey; ?>" id="theme-screenshot-wrap-<?php echo $tkey;?>">
				<a href="#<?php echo $tplid; ?>" data-theme-key="<?php echo $tkey;?>" class="blog_template-item_selector <?php echo $default; ?>">
					<img src="<?php echo $img;?>" />
					<input type="radio" id="blog-template-radio-<?php echo $tkey;?>" <?php checked( ! empty( $default ) ); ?> name="blog_template" value="<?php echo $tkey;?>" style="display: none" />
				</a>
				
				<?php if ( ! empty( $template['description'] ) ): ?>
					<div id="nbt-desc-pointer-<?php echo $tkey; ?>" class="nbt-desc-pointer">
						<?php echo nl2br($template['description']); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php 
	}
	elseif ( 'screenshot_plus' === $type ) {
		$img = ( ! empty( $template['screenshot'] ) ) ? $template['screenshot'] : nbt_get_default_screenshot_url( $template['blog_id'] );
		$tplid = preg_replace('/[^a-z0-9]/i', '', strtolower($template['name'])) . "-{$tkey}";
		?>
			<div class="template-signup-item theme-screenshot-plus-wrap" id="theme-screenshot-plus-wrap-<?php echo $tkey;?>">
				<h4><?php echo strip_tags( $template['name'] );?></h4>
				<div class="theme-screenshot-plus-image-wrap <?php echo $default; ?>" id="theme-screenshot-plus-image-wrap-<?php echo $tkey;?>">
					<a href="#<?php echo $tplid; ?>" data-theme-key="<?php echo $tkey;?>" class="blog_template-item_selector">
						<img src="<?php echo $img;?>" />
						<input type="radio" id="blog-template-radio-<?php echo $tkey;?>" <?php checked( ! empty( $default ) ); ?> name="blog_template" value="<?php echo $tkey;?>" style="display: none" />
					</a>
				</div>
				<p class="blog_template-description">
					<?php echo nl2br($template['description']); ?>
				</p>
			</div>
		<?php

	}
	elseif ( 'description' === $type ) {
		?>
			<div class="template-signup-item theme-radio-wrap" id="theme-screenshot-radio-<?php echo $tkey;?>">
				<label for="blog_template-<?php echo $tkey; ?>">
					<input type="radio" id="blog_template-<?php echo $tkey; ?>" name="blog_template" <?php checked( ! empty( $default ) ); ?> value="<?php echo $tkey;?>" />
					<strong><?php echo strip_tags($template['name']);?></strong>
				</label>
				<div class="blog_template-description">
					<?php echo nl2br( $template['description'] ); ?>
				</div>
			</div>
		<?php
	}
	else {
		?>
			<option value="<?php echo esc_attr( $tkey );?>" <?php selected( ! empty( $default ) ); ?>><?php echo strip_tags($template['name']);?></option>
		<?php	
	}
}

function nbt_render_theme_selection_scripts( $options ) {
	$type = $options['registration-templates-appearance'];
	$selected_color = $options['selected-background-color'];
	$unselected_color = $options['unselected-background-color'];
	$overlay_color = $options['overlay_color'];
	$unselected_overlay_color = $options['unselected-overlay_color'];
	$screenshots_width = $options['screenshots_width'];

	wp_enqueue_script( 'nbt-template-selector', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-template-selector.js', array( 'jquery' ) );
	wp_enqueue_style( 'nbt-template-selector', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/nbt-template-selector.css' );
	?>
		<style>
			.theme-previewer-wrap,
			.theme-page-showcase-wrap,
			.theme-screenshot-wrap {
				background:<?php echo $unselected_color; ?>;
			}
			.blog_template-default_item {
				background:<?php echo $selected_color; ?> !important;
			}
		</style>
	<?php
	if ( 'previewer' == $type ) {
		wp_enqueue_script( 'nbt-template-selector-previewer', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-template-selector-previewer.js', array( 'jquery' ) );
		wp_enqueue_style( 'nbt-template-selector-previewer', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/nbt-template-selector-previewer.css' );
	}
	elseif ( 'page_showcase' == $type ) {
		wp_enqueue_script( 'nbt-template-selector-page_showcase', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-template-selector-page_showcase.js', array( 'jquery' ) );
		wp_enqueue_style( 'nbt-template-selector-page_showcase', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/nbt-template-selector-page_showcase.css' );
		?>
			<style>
				.theme-page-showcase-wrap {
					background:<?php echo $unselected_overlay_color; ?>;
					width:<?php echo $screenshots_width; ?>px;
				}
				.blog_template-default_item {
					background: <?php echo $overlay_color; ?>;
				}
			</style>
		<?php
	}
	elseif ( 'screenshot' === $type ) {
		wp_enqueue_script( 'nbt-template-selector-screenshot', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-template-selector-screenshot.js', array( 'jquery' ) );
	}
	elseif ( 'screenshot_plus' === $type ) {
		wp_enqueue_script( 'nbt-template-selector-screenshot_plus', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-template-selector-screenshot_plus.js', array( 'jquery' ) );
		wp_enqueue_style( 'nbt-template-selector-screenshot_plus', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/nbt-template-selector-screenshot_plus.css' );
		?>
			<style>
				.theme-screenshot-plus-image-wrap {
					background:<?php echo $unselected_color; ?>;
				}
			</style>
		<?php
	}
	elseif ( 'description' === $type ) {
		wp_enqueue_style( 'nbt-template-selector-description', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/nbt-template-selector-description.css' );
	}
}

register_activation_hook( __FILE__, 'nbt_activate_plugin' );
function nbt_activate_plugin() {
	$model = nbt_get_model();
	$model->create_tables();
	update_site_option( 'nbt_plugin_version', NBT_PLUGIN_VERSION );
}
