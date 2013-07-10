<?php
/*
Plugin Name: New Blog Templates
Plugin URI: http://premium.wpmudev.org/project/new-blog-template
Description: Allows the site admin to create new blogs based on templates, to speed up the blog creation process
Author: Jason DeVelvis, Ulrich Sossou (Incsub), Ignacio Cruz (Incsub)
Author URI: http://premium.wpmudev.org/
Version: 1.8
Network: true
Text Domain: blog_templates
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

define( 'NBT_PLUGIN_VERSION', '1.9' );
if ( !is_multisite() )
	exit( __( 'The New Blog Template plugin is only compatible with WordPress Multisite.', 'blog_templates' ) );

define( 'NBT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/filters.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/model.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/upgrade.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/admin/main_menu.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/admin/categories_menu.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/admin/settings_menu.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/blog_templates.php' );
require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/blog_templates_lock_posts.php' );


if ( is_network_admin() ) {
	require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/tables/templates_table.php' );
	require_once( NBT_PLUGIN_DIR . '/blogtemplatesfiles/tables/categories_table.php' );
}


function nbt_get_default_screenshot_url( $blog_id ) {
	switch_to_blog($blog_id);
	$img = untrailingslashit(dirname(get_stylesheet_uri())) . '/screenshot.png';
	restore_current_blog();	
	return $img;
}

/**
 * Show notification if WPMUDEV Update Notifications plugin is not installed
 **/
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );

	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wds') . '</a></p></div>';
	}
}





