<?php
/*
Plugin Name: New Blog Templates
Plugin URI: http://premium.wpmudev.org/project/new-blog-template
Description: Allows the site admin to create new blogs based on templates, to speed up the blog creation process
Author: WPMU DEV
Author URI: http://premium.wpmudev.org/
Version: 2.6.8.2
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

define( 'NBT_PLUGIN_VERSION', '2.6.8.2' );
if ( ! is_multisite() )
	exit( __( 'The New Blog Template plugin is only compatible with WordPress Multisite.', 'blog_templates' ) );

define( 'NBT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NBT_PLUGIN_LANG_DOMAIN', 'blog_templates' );


require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/helpers.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/copier/copier.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/filters.php' );


if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/ajax.php' );

if ( is_network_admin() ) {
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/admin/main_menu.php' );
	require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/tables/templates_table.php' );
	
}

// Load Premium?
if ( file_exists( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/premium.php' ) )
	include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/premium/premium.php' );

require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/model.php' );


require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/integration.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/blog_templates.php' );
require_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/settings-handler.php' );

include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/externals/wpmudev-dash-notification.php' );
global $wpmudev_notices;
$wpmudev_notices[] = array( 'id'=> 130,'name'=> 'New Blog Templates', 'screens' => array( 'toplevel_page_blog_templates_main-network', 'blog-templates_page_blog_templates_categories-network', 'blog-templates_page_blog_templates_settings-network' ) );


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




register_activation_hook( __FILE__, 'nbt_activate_plugin' );
function nbt_activate_plugin() {
	$model = nbt_get_model();
	$model->create_tables();
	update_site_option( 'nbt_plugin_version', NBT_PLUGIN_VERSION );
}
