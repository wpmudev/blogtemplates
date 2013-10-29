<?php

function nbt_load_core() {
	$plugin_dir = plugin_dir_path( __FILE__ );
	require_once( $plugin_dir . '/blogtemplatesfiles/model.php' );
	require_once( $plugin_dir . '/blogtemplatesfiles/helpers.php' );
	require_once( $plugin_dir . '/blogtemplatesfiles/copier.php' );
	require_once( $plugin_dir . '/blogtemplatesfiles/settings-handler.php' );
}