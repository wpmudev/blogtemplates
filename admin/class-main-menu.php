<?php


class Blog_Templates_Main_Menu {


	/**
	 * Slug of the plugin screen.
	 */
	protected $plugin_screen_hook_suffix = null;

	public $menu_slug = 'blog_templates_main';

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 */
	public function __construct() {

		$plugin = Blog_Templates::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'network_admin_menu', array( $this, 'add_admin_menu' ) );


	}



	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		if ( get_current_screen()->id == $this->plugin_screen_hook_suffix . '-network' ) {
    		wp_enqueue_style( 'nbt-settings-css', plugins_url( 'assets/css/settings.css', __FILE__ ), array(), Blog_Templates::VERSION );
    		wp_enqueue_style( 'nbt-jquery-ui-styles', plugins_url( 'assets/css/jquery-ui.css', __FILE__ ), array(), Blog_Templates::VERSION );
		}

	}

	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		if ( get_current_screen()->id == $this->plugin_screen_hook_suffix . '-network' ) {
			wp_enqueue_script( 'nbt-templates-js', plugins_url( 'assets/js/nbt-templates.js', __FILE__ ), array( 'jquery' ), Blog_Templates::VERSION );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			$params = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			);
			wp_localize_script( 'nbt-templates-js', 'export_to_text_js', $params );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {

		$this->plugin_screen_hook_suffix = add_menu_page( 
			__( 'Blog Templates', 'blog_templates' ), 
			__( 'Blog Templates', 'blog_templates' ), 
			'manage_network', 
			$this->menu_slug, 
			array( $this,'display' ), 
			'div'
		);

		add_action( 'load-' . $this->plugin_screen_hook_suffix, array( $this, 'process_form' ) );

	}



	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display() {
		global $pagenow, $wpdb;

		$t = isset( $_GET['t'] ) ? absint( $_GET['t'] ) : '';

	    if ( ! is_numeric( $t ) ) {
	    	include_once( 'includes/class-templates-table.php' );
	    	
	    	$templates_table = new NBT_Templates_Table(); 
            $templates_table->prepare_items();

	    	include_once( 'views/templates.php' );
	    }
	    else {
	    	$model = nbt_get_model();
			$template = $model->get_template( $t );

			$url = $pagenow . '?page=' . $this->menu_slug;

			$options_to_copy = array(
                'settings' => array(
                    'title' => __( 'Wordpress Settings, Current Theme, and Active Plugins', 'blog_templates' ),
                    'content' => false
                ),
                'posts'    => array(
                    'title' => __( 'Posts', 'blog_templates' ),
                    'content' => $this->get_post_categories_list( $template )
                ),
                'pages'    => array(
                    'title' => __( 'Pages', 'blog_templates' ),
                    'content' => $this->get_pages_list( $template )
                ),
                'terms'    => array(
                    'title' => __( 'Categories, Tags, and Links', 'blog_templates' ),
                    'content' => false
                ),
                'users'    => array(
                    'title' => __( 'Users', 'blog_templates' ),
                    'content' => false
                ),
                'menus'    => array(
                    'title' => __( 'Menus', 'blog_templates' ),
                    'content' => false
                ),
                'files'    => array(
                    'title' => __( 'Files', 'blog_templates' ),
                    'content' => false
                )
            );

			if ( empty( $template['screenshot'] ) )
                $img = nbt_get_default_screenshot_url($template['blog_id']);
            else
                $img = $template['screenshot'];

            $additional_tables = copier_get_additional_tables( $template['blog_id'] );

	    	include_once( 'views/edit-template.php' );
	    }
	}

	private function get_post_categories_list( $template ) {
    	ob_start();
    	?>
    		<ul id="nbt-post-categories-checklist">
				<li id="all-categories"><label class="selectit"><input class="all-selector" value="all-categories" type="checkbox" <?php checked( in_array( 'all-categories', $template['post_category'] ) ); ?> name="post_category[]" id="in-all-categories"> <strong><?php _e( 'All categories', 'blog_templates' ); ?></strong></label></li>
				<?php
					switch_to_blog( $template['blog_id'] );
					wp_terms_checklist( 0, array( 'selected_cats' => $template['post_category'], 'checked_ontop' => 0 ) );
					restore_current_blog();
				?>
	 		</ul>
    	<?php
    	return ob_get_clean();
    }

    private function get_pages_list( $template ) {
    	switch_to_blog( $template['blog_id'] );
    	$pages = get_pages();
    	restore_current_blog();

    	ob_start();
    		?>
			<ul id="nbt-pages-checklist">
				<li id="all-nbt-pages"><label class="selectit"><input class="all-selector" value="all-pages" type="checkbox" <?php checked( in_array( 'all-pages', $template['pages_ids'] ) ); ?> name="pages_ids[]" id="in-all-nbt-pages"> <strong><?php _e( 'All pages', 'blog_templates' ); ?></strong></label></li>
				<?php foreach ( $pages as $page ): ?>
					<li id="page-<?php echo $page->ID; ?>">
						<label class="selectit">
							<input type="checkbox" name="pages_ids[]" id="in-page-<?php echo $page->ID; ?>" value="<?php echo $page->ID; ?>" <?php checked( ! in_array( 'all-pages', $template['pages_ids'] ) && in_array( $page->ID, $template['pages_ids'] ) ); ?>> <?php echo $page->post_title; ?>
						</label>
					</li>
				<?php endforeach; ?>
	 		</ul>
	 	<?php
    	return ob_get_clean();
    }

    public function process_form() {
		$model = nbt_get_model();

        $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

        $save_template = ( ! empty( $_POST['reset-screenshot'] ) || ! empty( $_POST['save_updated_template'] ) );
        if( $save_template ) {

            if (! wp_verify_nonce($_POST['_nbtnonce'], 'blog_templates-update-options') )
                die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );
            

            $args = array( 
                'name' => stripslashes($_POST['template_name'] ),
                'description' => stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) ),
                'to_copy' => isset( $_POST['to_copy'] ) ? (array)$_POST['to_copy'] : array(),
                'additional_tables' => isset( $_POST['additional_template_tables'] ) ? $_POST['additional_template_tables'] : array(),
                'copy_status' => isset( $_POST['copy_status'] ) ? true : false,
                'block_posts_pages' => isset( $_POST['block_posts_pages'] ) ? true : false,
                'update_dates' => isset( $_POST['update_dates'] ) ? true: false
            );
            if ( ! empty( $_FILES['screenshot']['tmp_name'] ) ) {
            	$uploaded_file = $_FILES['screenshot'];
            	$wp_filetype = wp_check_filetype_and_ext( $uploaded_file['tmp_name'], $uploaded_file['name'], false );
				if ( ! wp_match_mime_types( 'image', $wp_filetype['type'] ) )
					wp_die( '<div class="error"><p>' . __( 'The uploaded file is not a valid image. Please try again.' ) . '</p></div>' );

				$movefile = wp_handle_upload( $uploaded_file, array( 'test_form' => false ) );

				if ( $movefile ) {
				    $args['screenshot'] = $movefile['url'];
				}
            }
            else {
            	$template = $model->get_template( absint( $_POST['template_id'] ) );
            	$args['screenshot'] = ! empty( $template['screenshot'] ) ? $template['screenshot'] : false;
            }

            if ( ! empty( $_POST['reset-screenshot'] ) ) {
        		$args['screenshot'] = false;
        	}

        	// POST CATEGORIES
            $post_category = array( 'all-categories' );
            if ( isset( $_POST['post_category'] ) ) {
            	$categories = $_POST['post_category'];

            	if ( in_array( 'all-categories', $categories ) ) {
            		$post_category = array( 'all-categories' );
            	}
            	else {

            		$post_category = array();
            		foreach( $categories as $category ) {
            			if ( ! is_numeric( $category ) )
            				continue;

            			$post_category[] = absint( $category );
            		}
            	}
            }
            $args['post_category'] = $post_category; 

            // PAGES IDs
            $pages_ids = array( 'all-pages' );

            if ( isset( $_POST['pages_ids'] ) && is_array( $_POST['pages_ids'] ) ) {
            	if ( in_array( 'all-pages', $_POST['pages_ids'] ) ) {
            		$pages_ids = array( 'all-pages' );
            	}
            	else {
            		$pages_ids = array();
            		foreach( $_POST['pages_ids'] as $page_id ) {
            			if ( ! is_numeric( $page_id ) )
            				continue;

            			$pages_ids[] = absint( $page_id );
            		}
            	}
            }
            $args['pages_ids'] = $pages_ids;

            do_action( 'nbt_update_template', $t );          

            $model->update_template( $t, $args );

            $this->updated_message =  __( 'Your changes were successfully saved!', 'blog_templates' );
            add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

        } elseif( !empty( $_POST['save_new_template'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_nbtnonce'], 'blog_templates-update-options' ) )
                wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );

            if ( ! get_blog_details( (int) $_POST['copy_blog_id'] ) )
                wp_die( __( 'Whoops! The blog ID you posted is incorrect. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );

            if ( is_main_site( (int) $_POST['copy_blog_id'] ) )
                wp_die( __( 'Whoops! The blog ID you posted is incorrect. You cannot template the main site. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );

            $name = ( ! empty( $_POST['template_name'] ) ? stripslashes( $_POST['template_name'] ) : __( 'A template', 'blog_templates' ) );
            $description = ( ! empty( $_POST['template_description'] ) ? stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) ) : '' );
            $blog_id = (int)$_POST['copy_blog_id'];

            $settings = array(
                'to_copy' => array(),
                'post_category' => array( 'all-categories' ),
                'copy_status' => false,
                'block_posts_pages' => false,
                'pages_ids' => array( 'all-pages' ),
                'update_dates' => false
            );

            $template_id = $model->add_template( $blog_id, $name, $description, $settings );
            wp_die( var_dump( $template_id ) );
            $to_url = add_query_arg(
            	array(
            		'page' => $this->menu_slug,
            		't' => $template_id
            	),
            	network_admin_url( 'admin.php' )
            );
            wp_redirect( $to_url );

        }
        elseif ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) {

            if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-delete_template') )
                wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );

            $model->delete_template( absint( $_GET['d'] ) );

            $this->updated_message =  __( 'Success! The template was successfully deleted.', 'blog_templates' );
            add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );
        }

        do_action( 'nbt_main_menu_processed', $this );
	}

	public function show_admin_notice() {
    	?>
			<div class="updated">
				<p><?php echo $this->updated_message; ?></p>
			</div>
    	<?php
    }


}
