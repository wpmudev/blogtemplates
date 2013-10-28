<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

$plugin_dir = plugin_dir_path( __FILE__ );

require_once( $plugin_dir . 'blogtemplates.php' );

$model = nbt_get_model();
$model->delete_tables();

delete_site_option( 'nbt_plugin_version' );
delete_site_option( 'blog_templates_options' );
