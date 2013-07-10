<?php


class blog_templates_settings_menu {

	/**
     * @var string The options string name for this plugin
     *
     * @since 1.0
     */
    var $options_name = 'blog_templates_options';

    /**
     * @var string $localization_domain Domain used for localization
     *
     * @since 1.0
     */
    var $localization_domain = 'blog_templates';

    /**
    * @var array $options Stores the options for this plugin
    *
    * @since 1.0
    */
    var $options = array();

    var $menu_slug = 'blog_templates_settings';

    var $page_id;

    var $updated_message = '';


	function __construct() {
		global $wp_version;

		// Initialize the options
        $this->get_options();

        

		// Add the super admin page
        if( version_compare( $wp_version , '3.0.9', '>' ) ) {
            add_action( 'network_admin_menu', array( $this, 'network_admin_page' ) );
        } else {
            add_action( 'admin_menu', array( $this, 'pre_3_1_network_admin_page' ) );
        }

        // Admin notices and data processing
        add_action( 'admin_init', array($this, 'admin_options_page_posted' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'add_javascript' ) );

       
	}


	/**
    * Retrieves the plugin options from the database.
    *
    * @since 1.0
    */
    function get_options() {
        //Don't forget to set up the default options
        if (!$theOptions = get_site_option($this->options_name)) {
            $theOptions = $this->get_default_settings();
            update_site_option($this->options_name, $theOptions);
        }

        $this->options = wp_parse_args( $theOptions, $this->get_default_settings() );
    }

    private function get_default_settings() {
    	return array(
    		'templates' => array(),
    		'show-registration-templates' => false,
    		'registration-templates-appearance' => '',
    		'default' => '',
    		'previewer_button_text' => __( 'Select this theme', $this->localization_domain ),
    		'show-categories-selection' => false
    	);
    }

    public function add_javascript($hook) {

    	if ( get_current_screen()->id == $this->page_id . '-network' ) {
    		wp_enqueue_script( 'nbt-settings-js', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-settings.js', array( 'jquery' ) );
    		wp_enqueue_style( 'nbt-settings-css', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/settings.css' );
    	}
    }

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_submenu_page( 'blog_templates_main', __( 'Settings', $this->localization_domain ), __( 'Settings', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
    }

    /**
     * Adds the options subpanel
     *
     * @since 1.0
     */
    function pre_3_1_network_admin_page() {
        if ( get_bloginfo('version') >= 3 )
            add_submenu_page( 'blog_templates_main', __( 'Blog Templates', $this->localization_domain ), __( 'Blog Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
        else
            add_submenu_page( 'blog_templates_main', __( 'Blog Templates', $this->localization_domain ), __( 'Blog Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'filter_plugin_actions' ) );
    }

    /**
     * Adds settings/options page
     *
     * @since 1.0
     */
    function admin_options_page() {

	    $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

		?>

			<div class="wrap">
			    <form method="post" id="options">
		        	<?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); ?> 
		            
		            <h2><?php _e('Options', $this->localization_domain); ?></h2>
					
					<h3><?php _e( 'Template selection', $this->localization_domain ); ?></h3>
		            <table class="form-table">
		            	<?php ob_start(); ?>
			                <label for="show-registration-templates">
			                    <input type="checkbox" <?php checked( !empty($this->options['show-registration-templates']) ); ?> name="show-registration-templates" id="show-registration-templates" value="1"/> 
			                    <?php _e('Selecting this option will allow your new users to choose between templates when they sign up for a site.', $this->localization_domain); ?>
			                </label><br/>
			                <?php $this->render_row( __('Show templates selection on registration:', $this->localization_domain), ob_get_clean() ); ?>
			            
			            <?php ob_start(); ?>

			            <?php ob_start(); ?>
			                <label for="show-categories-selection">
			                    <input type="checkbox" <?php checked( !empty($this->options['show-categories-selection']) ); ?> name="show-categories-selection" id="show-categories-selection" value="1"/> 
			                    <?php _e( 'A new toolbar will appear on to on the selection screen. Users will be able to filter by templates categories <strong>(Just applicable when theme screenshot or previewer is selected)</strong>.', $this->localization_domain); ?>
			                </label><br/>
			                <?php $this->render_row( __('Show categories menu', $this->localization_domain), ob_get_clean() ); ?>
			            
			            <?php ob_start(); ?>

			            <?php $appearance_template = $this->_get_config_option('registration-templates-appearance'); ?>
		                <label for="registration-templates-appearance-select">
		                    <input type="radio" <?php checked( empty( $appearance_template ) ); ?> name="registration-templates-appearance" id="registration-templates-appearance-select" value=""/>
		                    <?php _e('As simple selection box', $this->localization_domain); ?>
		                </label><br/>
		                <label for="registration-templates-appearance-description">
		                    <input type="radio" <?php checked( $appearance_template, 'description' ); ?> name="registration-templates-appearance" id="registration-templates-appearance-description" value="description"/>
		                    <?php _e('As radio-box selection with descriptions', $this->localization_domain); ?>
		                </label><br/>
		                <label for="registration-templates-appearance-screenshot">
		                    <input type="radio" <?php checked( $appearance_template, 'screenshot' ); ?> name="registration-templates-appearance" id="registration-templates-appearance-screenshot" value="screenshot" />
		                    <?php _e('As theme screenshot selection', $this->localization_domain); ?>
		                </label><br/>
		                <label for="registration-templates-appearance-screenshot_plus">
		                    <input type="radio" <?php checked( $appearance_template, 'screenshot_plus' ); ?> name="registration-templates-appearance" id="registration-templates-appearance-screenshot_plus" value="screenshot_plus" />
		                    <?php _e('As theme screenshot selection with titles and description', $this->localization_domain); ?>
		                </label><br/>
		                <label for="registration-templates-appearance-previewer">
		                    <input type="radio" <?php checked( 'previewer' == $appearance_template ); ?> name="registration-templates-appearance" id="registration-templates-appearance-previewer" value="previewer" />
		                    <?php _e('As a theme previewer', $this->localization_domain); ?>
		                </label><br/>
		                <div id="previewer-button-text">
			                <label style="margin-left:20px;margin-top:10px" for="registration-templates-appearance-previewer-button-text">
			                	<?php _e( '"Select this Theme" button text', $this->localization_domain); ?>
			                    <input type="text" name="registration-templates-button-text" id="registration-templates-appearance-previewer-button-text" value="<?php echo $this->options['previewer_button_text']; ?>" />
			                </label>
			            </div>
			            <?php $this->render_row( __('Type of selection', $this->localization_domain), ob_get_clean() ); ?>
			        </table>
		            <p><div class="submit"><input type="submit" name="save_options" class="button-primary" value="<?php esc_attr_e(__('Save Settings', $this->localization_domain));?>" /></div></p>
			    </form>
			   </div>
			<?php
	    }

        /**
        * Adds the Settings link to the plugin activate/deactivate page
        *
        * @param array $links The ID of the blog to copy
        *
        * @since 1.0
        */
        function filter_plugin_actions( $links ) {
            global $wp_version;

            if ( version_compare( $wp_version , '3.0.9', '>' ) )
                $settings_link = '<a href="' . network_admin_url( 'settings.php?page=' . basename(__FILE__) ) . '">' . __( 'Settings', $this->localization_domain ) . '</a>';
            elseif ( version_compare( $wp_version , '3.0', '<' ) )
                $settings_link = '<a href="wpmu-admin.php?page=' . basename(__FILE__) . '">' . __( 'Settings', $this->localization_domain ) . '</a>';
            else
                $settings_link = '<a href="ms-admin.php?page=' . basename(__FILE__) . '">' . __( 'Settings', $this->localization_domain ) . '</a>';
            array_unshift( $links, $settings_link ); // add before other links

            return $links;
        }

        private function render_row( $title, $markup ) {
            ?>
                <tr valign="top">
                    <th scope="row"><label for="site_name"><?php echo $title; ?></label></th>
                    <td>
                        <?php echo $markup; ?>          
                    </td>
                </tr>
            <?php
        }

        private function _get_config_option ($key, $default=false) {
            if (empty($this->options)) return $default;
            if (empty($this->options[$key])) return $default;
            return $this->options[$key];
        }
        
        /**
        * Saves the admin options to the database.
        *
        * @since 1.0
        **/
        function save_admin_options() {
            return update_site_option( $this->options_name, $this->options );
        }

        /**
         * Separated into its own function so we could include it in the init hook
         *
         * @since 1.0
         */
        function admin_options_page_posted() {
            if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->menu_slug )
                return;


            $model = blog_templates_model::get_instance();


            if (isset($_POST['save_options'])) {

                if (! wp_verify_nonce($_POST['_nbtnonce'], 'blog_templates-update-options') )
                    wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );
                $this->options['show-registration-templates'] = isset($_POST['show-registration-templates']) ? (int)$_POST['show-registration-templates'] : 0;
                $this->options['registration-templates-appearance'] = isset($_POST['registration-templates-appearance']) ? $_POST['registration-templates-appearance'] : '';
                $this->options['show-categories-selection'] = isset($_POST['show-categories-selection']) ? $_POST['show-categories-selection'] : 0;

                if ( ! empty( $_POST['registration-templates-button-text'] ) )
					$this->options['previewer_button_text'] = sanitize_text_field( $_POST['registration-templates-button-text'] );

                $this->save_admin_options(); 

                $this->updated_message =  __( 'Options saved.', $this->localization_domain );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );
                return;
            }

        }

        public function show_admin_notice() {
        	?>
				<div class="updated">
					<p><?php echo $this->updated_message; ?></p>
				</div>
        	<?php
        }

}