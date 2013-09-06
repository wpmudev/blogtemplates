<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

$plugin_dir = plugin_dir_path( __FILE__ );

require_once( $plugin_dir . 'blogtemplates.php' );

delete_site_option( 'nbt_plugin_version' );

$model = blog_templates_model::get_instance();
$model->delete_tables();

delete_site_option( 'blog_templates_options' );
