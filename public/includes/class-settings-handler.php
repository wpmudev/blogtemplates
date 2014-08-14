<?php


function get_settings_handler() {
	return Blog_Templates_Settings_Handler::get_instance();
}

function nbt_get_settings() {
	$handler = get_settings_handler();
	return $handler->get_settings();
}

function nbt_update_settings( $new_settings ) {
	$handler = get_settings_handler();
	$handler->update_settings( $new_settings );
}

function nbt_get_default_settings() {
	$handler = get_settings_handler();
	return $handler->get_default_settings();
}

function nbt_get_default_screenshot_url( $blog_id ) {
    switch_to_blog($blog_id);
    $img = untrailingslashit(dirname(get_stylesheet_uri())) . '/screenshot.png';
    restore_current_blog(); 
    return $img;
}


class Blog_Templates_Settings_Handler {

	static $instance;

	// Settings slug for DB
	// TODO: Change slug
	private $settings_slug = 'blog_templates_options';

	// Settings for the plugin
	private $settings = array();

	/**
	 * Get the default settings
	 * 
	 * @return Array of settings
	 */
	public function get_default_settings() {
    	return array(
    		'templates' => array(),
    		'show-registration-templates' => false,
    		'registration-templates-appearance' => '',
    		'page-showcase-id' => false,
    		'default' => '',
    		'previewer_button_text' => __( 'Select this theme', 'blog_templates' ),
    		'screenshots_width' => 200,
            'show-categories-selection' => false,
            'unselected-background-color' => '#DFD9D9',
            'selected-background-color' => '#333333',
            'overlay_color' => '#333333',
            'toolbar-color' => '#d86565',
            'toolbar-text-color' => '#ffffff',
    		'toolbar-border-color' => '#898989',
    		'categories_selection' => false
    	);
	}

	/**
	 * Return an instance of the class
	 * 
	 * @return Object
	 */
	public static function get_instance() {
		if ( self::$instance === null )
			self::$instance = new self();
            
        return self::$instance;
	}

	/**
	 * Get the plugin settings
	 * 
	 * @return Array of settings
	 */
	public function get_settings() {
		if ( empty( $this->settings ) )
			$this->init_settings();

		return $this->settings;
	}

	/**
	 * Update the settings
	 * 
	 * @param Array $new_settings
	 */
	public function update_settings( $new_settings ) {
		$this->settings = $new_settings;

		// We are not saving the templates here
		$new_settings['templates'] = array();

		update_site_option( $this->settings_slug, $new_settings );
	}

	/**
	 * Initializes the plugin settings
	 * 
	 * @since 0.1
	 */
	private function init_settings() {
		$current_settings = get_site_option( $this->settings_slug );
		
		$model = nbt_get_model();
		$current_settings['templates'] = $model->get_templates();

		foreach( $current_settings['templates'] as $key => $template ) {
            $options = $template['options'];
            unset( $current_settings['templates'][ $key ]['options'] );
            $current_settings['templates'][ $key ] = array_merge( $current_settings['templates'][ $key ], $options );
        }

        $this->settings = wp_parse_args( $current_settings, $this->get_default_settings() );

	}


	/**
	 * Get the settings slug used on DB
	 * 
	 * @return Array Plugin Settings
	 */
	public function get_settings_slug() {
		return $this->settings_slug;
	}




}