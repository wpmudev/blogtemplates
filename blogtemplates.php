<?php
/*
Plugin Name: New Blog Templates
Plugin URI: http://premium.wpmudev.org/project/new-blog-template
Description: Allows the site admin to create new blogs based on templates, to speed up the blog creation process
Author: WPMU DEV
Author URI: http://premium.wpmudev.org/
Version: 3.0
Network: true
Text Domain: blog_templates
Domain Path: lang
WDP ID: 130
WordPress-Plugin-Boilerplate: v2.6.1
*/

/*  Copyright 2010-2014 Incsub (http://incsub.com)

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

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


require_once( plugin_dir_path( __FILE__ ) . 'public/class-blogtemplates.php' );

register_activation_hook( __FILE__, array( 'Blog_Templates', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Blog_Templates', 'deactivate' ) );


add_action( 'plugins_loaded', array( 'Blog_Templates', 'get_instance' ) );


if ( is_network_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-blogtemplates-admin.php' );
	add_action( 'plugins_loaded', array( 'Blog_Templates_Admin', 'get_instance' ) );
}
