<?php

class Blog_Templates_Categories_Menu {


	/**
	 * Slug of the plugin screen.
	 */
	protected $plugin_screen_hook_suffix = null;

	public $menu_slug = 'blog_templates_categories';

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 */
	public function __construct() {

		$plugin = Blog_Templates::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Add the options page and menu item.
		add_action( 'network_admin_menu', array( $this, 'add_admin_menu' ) );

	}


	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {

		$this->plugin_screen_hook_suffix = add_submenu_page( 
			'blog_templates_main', 
			__( 'Template Categories', 'blog_templates' ), 
			__( 'Template Categories', 'blog_templates' ), 
			'manage_network', 
			$this->menu_slug, 
			array( $this,'display' ) 
		);

		add_action( 'load-' . $this->plugin_screen_hook_suffix, array( $this, 'process_form' ) );

	}



	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display() {
		if ( ! empty( $this->errors ) ) {
    		?>
				<div class="error"><p><?php echo $this->errors; ?></p></div>
    		<?php
    	}
    	elseif ( isset( $_GET['updated'] ) ) {
    		?>
				<div class="updated">
					<p><?php _e( 'Changes have been applied', 'blog_templates' ); ?></p>
				</div>
    		<?php
    	}

    	$errors = wp_list_pluck( get_settings_errors( 'nbt-categories' ), 'message' );

    	$cat_id = false;
    	if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] && isset( $_GET['category'] ) )
    		$cat_id = absint( $_GET['category'] );

    	if ( $cat_id ) {
    		$model = nbt_get_model();
			$category = $model->get_template_category( $cat_id );

			if ( ! $category )
				wp_die( __( 'The category does not exist', 'blog_templates' ) );

    		include_once( 'views/edit-category.php' );
    	}
    	else {
    		include_once( 'includes/class-categories-table.php' );
    		$cats_table = new Blog_Templates_Categories_Table();
			$cats_table->prepare_items();

			$posted_name = ! empty( $_POST['cat_name'] ) ? stripslashes( $_POST['cat_name'] ) : '';
			$posted_desc = ! empty( $_POST['cat_description'] ) ? stripslashes( $_POST['cat_description'] ) : '';

    		include_once( 'views/categories.php' );
    	}

	}

    public function process_form() {
		if ( isset( $_POST['submit-edit-nbt-category'] ) ) {
			check_admin_referer( 'edit-nbt-category' );

			if ( isset( $_POST['cat_name'] ) && ! empty( $_POST['cat_name'] ) && isset( $_POST['cat_id'] ) ) {
				$model = nbt_get_model();

				$description = stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['cat_description'] ) );
				$name = sanitize_text_field( stripslashes_deep( $_POST['cat_name'] ) );
				$model->update_template_category( absint( $_POST['cat_id'] ), $name, $description );

				$link = remove_query_arg( array( 'action', 'category' ) );
				$link = add_query_arg( 'updated', 'true', $link );
				wp_redirect( $link );
			}
			else {
				$this->errors = __( 'Name cannot be empty', 'blog_templates' );
			}
		}

		if ( isset( $_POST['submit-nbt-new-category'] ) ) {
			check_admin_referer( 'add-nbt-category' );

			$model = nbt_get_model();

			$description = stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['cat_description'] ) );
			$name = sanitize_text_field( stripslashes_deep( $_POST['cat_name'] ) );

			if ( ! empty( $name ) ) {
				$model->add_template_category( $name, $description );
				$link = remove_query_arg( array( 'action', 'category' ) );
				$link = add_query_arg( 'updated', 'true', $link );
				wp_redirect( $link );
			}
			else {
				add_settings_error( 'nbt-categories', 'name-empty', __( 'Name cannot be empty', 'blog_templates' ) );
			}
		}	
	}

}