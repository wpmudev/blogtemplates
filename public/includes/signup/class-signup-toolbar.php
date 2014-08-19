<?php

class Blog_Templates_Signup_Toolbar {

	public $categories;
	public $default_category_id;

	public function __construct( $type = '' ) {
		$this->current_tab = -1;
		$this->type = $type;
		
		$model = nbt_get_model();
		$this->categories = $model->get_templates_categories( array( 'hide_empty' => true ) );

		$this->tabs_count = count( $this->get_tabs() );

		add_action( 'wp_footer', array( &$this, 'add_javascript' ) );
		add_action( 'wp_footer', array( &$this, 'add_styles' ) );

	}

	public function add_javascript() {
		wp_enqueue_script( 'nbt-toolbar-scripts', NBT_PLUGIN_URL . 'public/assets/js/toolbar.js', array( 'jquery' ) );
		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'imagesurl' => NBT_PLUGIN_URL . 'public/assets/images/',
			'toolbar_id' => nbt_toolbar_get_toolbar_id()
		);
		wp_localize_script( 'nbt-toolbar-scripts', 'nbt_domain', $params );
	}

	public function add_styles() {
		
		wp_enqueue_style( 'nbt-toolbar-basic-styles', NBT_PLUGIN_URL . 'public/assets/css/toolbar.css' );

		// Is there a custom style for the toolbar?
		if ( is_file( get_stylesheet_directory() . '/blogtemplates/toolbar.css' ) ) {
			$custom_style_file = get_stylesheet_directory_uri() . '/blogtemplates/toolbar.css';
			wp_enqueue_style( 'nbt-toolbar-styles', $custom_style_file );
		}
		
	}

	public function get_the_type() {
		return $this->type;
	}

	public function get_default_tab() {
		$tabs = $this->get_tabs();
		return apply_filters( 'nbt_selection_toolbar_default_tab', $tabs [ key( $tabs ) ] );

	}

	public function get_tabs() {
		$tabs = array();
		$tabs[0] = array(
			'name' => __( 'ALL', 'blog_templates' ),
			'cat_id' => 0
		);

		foreach ( $this->categories as $category ) {
			$tabs[] = array(
				'name' => $category['name'],
				'cat_id' => $category['ID']
			);
		}

		return apply_filters( 'nbt_selection_toolbar_tabs', $tabs );
	}

	public function display() {
		// Setup theme file
        $theme_file = locate_template( array( 'blogtemplates/toolbar.php' ) );
        $theme_file = $theme_file ? $theme_file : NBT_PLUGIN_DIR . 'public/views/toolbar.php';

		include_once( $theme_file );
	}

	function have_tabs() {
		if ( $this->current_tab + 1 < $this->tabs_count ) {
			return true;
		}

		return false;
	}

	function the_tab() {
		global $nbt_toolbar_tab;

		$nbt_toolbar_tab = $this->next_tab();
	}

	function next_tab() {
		$this->current_tab++;
		
		$tabs = $this->get_tabs();
		$this->tab = $tabs[ $this->current_tab ];
		return $this->tab;
	}

}


