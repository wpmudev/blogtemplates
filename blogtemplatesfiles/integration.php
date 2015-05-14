<?php

$integration_files = array(
	// Plugins
	'plugins/appointments-plus.php',
	'plugins/autoblog.php',
	'plugins/blogs-directory.php',
	'plugins/buddypress.php',
	'plugins/contact-form-7.php',
	'plugins/easy-google-fonts.php',
	'plugins/formidable-pro.php',
	'plugins/gravity-forms.php',
	'plugins/membership.php',
	'plugins/multisite-privacy.php',
	'plugins/popover.php',
	'plugins/wp-https.php',

	// Themes
	'themes/epanel.php',
	'themes/framemarket.php'
);

$integration_files = apply_filters( 'blog_templates_integration_files', $integration_files );
foreach ( $integration_files as $file ) {
	if ( is_file( NBT_PLUGIN_DIR . 'blogtemplatesfiles/integration/' . $file ) )
		include_once( NBT_PLUGIN_DIR . 'blogtemplatesfiles/integration/' . $file );
}

