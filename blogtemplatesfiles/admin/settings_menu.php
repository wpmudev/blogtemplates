<?php


class blog_templates_settings_menu {

    var $menu_slug = 'blog_templates_settings';

    var $page_id;

    var $updated_message = '';


	function __construct() {
		global $wp_version;
       

        add_action( 'network_admin_menu', array( $this, 'network_admin_page' ) );

        // Admin notices and data processing
        add_action( 'admin_init', array($this, 'admin_options_page_posted' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'add_javascript' ) );

       
	}


    public function add_javascript($hook) {
    	if ( get_current_screen()->id == $this->page_id . '-network' ) {
            wp_enqueue_style( 'wp-color-picker' );
    		wp_enqueue_script( 'nbt-settings-js', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-settings.js', array( 'jquery', 'wp-color-picker' ) );
    		wp_enqueue_style( 'nbt-settings-css', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/settings.css' );
    	}
    }

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_submenu_page( 'blog_templates_main', __( 'Settings', 'blog_templates' ), __( 'Settings', 'blog_templates' ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
    }


    /**
     * Adds settings/options page
     *
     * @since 1.0
     */
    function admin_options_page() {
        
	    $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';
        $settings = nbt_get_settings();

		?>

			<div class="wrap">
			    <form method="post" id="options">
		        	<?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); ?> 
		            
                    <?php screen_icon( 'blogtemplates' ); ?>
		            <h2><?php _e('Options', 'blog_templates'); ?></h2>
					
					<h3><?php _e( 'Template selection', 'blog_templates' ); ?></h3>
		            <table class="form-table">
		            	<?php ob_start(); ?>
			                <label for="show-registration-templates">
			                    <input type="checkbox" <?php checked( !empty($settings['show-registration-templates']) ); ?> name="show-registration-templates" id="show-registration-templates" value="1"/> 
			                    <?php _e('Selecting this option will allow your new users to choose between templates when they sign up for a site.', 'blog_templates'); ?>
			                </label><br/>
			                <?php $this->render_row( __('Show templates selection on registration:', 'blog_templates'), ob_get_clean() ); ?>
			            
			            <?php ob_start(); ?>

                        <?php $appearance_template = $settings['registration-templates-appearance']; ?>
                        <label for="registration-templates-appearance-select">
                            <input type="radio" <?php checked( empty( $appearance_template ) ); ?> name="registration-templates-appearance" id="registration-templates-appearance-select" value=""/>
                            <?php _e('As simple selection box', 'blog_templates'); ?>
                        </label><br/>
                        <label for="registration-templates-appearance-description">
                            <input type="radio" <?php checked( $appearance_template, 'description' ); ?> name="registration-templates-appearance" id="registration-templates-appearance-description" value="description"/>
                            <?php _e('As radio-box selection with descriptions', 'blog_templates'); ?>
                        </label><br/>
                        <label for="registration-templates-appearance-screenshot">
                            <input type="radio" <?php checked( $appearance_template, 'screenshot' ); ?> name="registration-templates-appearance" id="registration-templates-appearance-screenshot" value="screenshot" />
                            <?php _e('As theme screenshot selection', 'blog_templates'); ?>
                        </label><br/>
                        <label for="registration-templates-appearance-screenshot_plus">
                            <input type="radio" <?php checked( $appearance_template, 'screenshot_plus' ); ?> name="registration-templates-appearance" id="registration-templates-appearance-screenshot_plus" value="screenshot_plus" />
                            <?php _e('As theme screenshot selection with titles and description', 'blog_templates'); ?>
                        </label><br/>
                        <label for="registration-templates-appearance-previewer">
                            <input type="radio" <?php checked( 'previewer' == $appearance_template ); ?> name="registration-templates-appearance" id="registration-templates-appearance-previewer" value="previewer" />
                            <?php _e('As a theme previewer', 'blog_templates'); ?>
                        </label><br/>
                        <div id="previewer-button-text">
                            <label style="margin-left:20px;margin-top:20px;display:block;" for="registration-templates-appearance-previewer-button-text">
                                <?php _e( '"Select this Theme" button text', 'blog_templates'); ?>
                                <input type="text" name="registration-templates-button-text" id="registration-templates-appearance-previewer-button-text" value="<?php echo $settings['previewer_button_text']; ?>" />
                            </label>
                        </div>

                        <div id="screenshot-selection-styles">
                            <label style="margin-left:20px;margin-top:20px;display:block;">
                                <?php _e( 'Unselected background color screenshot', 'blog_templates'); ?><br/>
                                <input type="text" class="color-field" id="selected-background-color" name="unselected-background-color" value="<?php echo $settings['unselected-background-color']; ?>" />
                            </label>
                            <label style="margin-left:20px;margin-top:20px;display:block;">
                                <?php _e( 'Selected background color screenshot', 'blog_templates'); ?><br/>
                                <input type="text" class="color-field" id="selected-background-color" name="selected-background-color" value="<?php echo $settings['selected-background-color']; ?>" />
                            </label>
                        </div>
                        <?php $this->render_row( __('Type of selection', 'blog_templates'), ob_get_clean() ); ?>
			        </table>
                    
                    <h3><?php _e( 'Categories Toolbar', 'blog_templates' ); ?></h3>
                    <table class="form-table">
                        <?php ob_start(); ?>
                            <label for="show-categories-selection">
                                <input type="checkbox" <?php checked( !empty($settings['show-categories-selection']) ); ?> name="show-categories-selection" id="show-categories-selection" value="1"/> 
                                <?php _e( 'A new toolbar will appear on to on the selection screen. Users will be able to filter by templates categories <strong>(Just applicable when theme screenshot or previewer is selected)</strong>.', 'blog_templates'); ?>
                            </label><br/>
                            <?php $this->render_row( __('Show categories menu', 'blog_templates'), ob_get_clean() ); ?>
                        
                        <?php ob_start(); ?>

                        <?php ob_start(); ?>
                            <label for="toolbar-color">
                                <input type="text" class="color-field" name="toolbar-color" id="toolbar-color" value="<?php echo $settings['toolbar-color']; ?>"/> 
                            </label>
                            <?php $this->render_row( __( 'Toolbar background color', 'blog_templates' ), ob_get_clean() ); ?>
                        
                        <?php ob_start(); ?>

                        <?php ob_start(); ?>
                            <label for="toolbar-text-color">
                                <input type="text" class="color-field" name="toolbar-text-color" id="toolbar-text-color" value="<?php echo $settings['toolbar-text-color']; ?>"/> 
                            </label>
                            <?php $this->render_row( __( 'Toolbar text color', 'blog_templates' ), ob_get_clean() ); ?>
                        
                        <?php ob_start(); ?>

                        <?php ob_start(); ?>
                            <label for="toolbar-border-color">
                                <input type="text" class="color-field" name="toolbar-border-color" id="toolbar-border-color" value="<?php echo $settings['toolbar-border-color']; ?>"/> 
                            </label>
                            <?php $this->render_row( __( 'Toolbar border color', 'blog_templates' ), ob_get_clean() ); ?>
                        
                    </table>
		            <p><div class="submit"><input type="submit" name="save_options" class="button-primary" value="<?php esc_attr_e(__('Save Settings', 'blog_templates'));?>" /></div></p>
                </form>
                <form method="post">
                    <table class="form-table">
                        <h3><?php _e( 'Repair Database', 'blog_templates' ); ?></h3>
                        <p><?php _e( 'Check the option and click on the button if some of the plugin tables have not been created', 'blog_templates' ); ?></p>
                        <?php ob_start(); ?>
                            <label for="repair-tables">
                                <input type="checkbox" name="repair-tables" id="repair-tables" /> 
                                <?php _e( 'No data will be deleted from the existent tables.', 'blog_templates'); ?>
                            </label><br/>
                            <?php $this->render_row( __( 'Repair tables?', 'blog_templates'), ob_get_clean() ); ?>
                    </table>
                    <input type="hidden" name="action" value="repair_database">
                    <?php wp_nonce_field( 'repair-database' ); ?>
                    <p><div class="submit"><input type="submit" name="submit_repair_database" class="button-primary" value="<?php esc_attr_e(__('Repair now!', 'blog_templates'));?>" /></div></p>
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
                $settings_link = '<a href="' . network_admin_url( 'settings.php?page=' . basename(__FILE__) ) . '">' . __( 'Settings', 'blog_templates' ) . '</a>';
            elseif ( version_compare( $wp_version , '3.0', '<' ) )
                $settings_link = '<a href="wpmu-admin.php?page=' . basename(__FILE__) . '">' . __( 'Settings', 'blog_templates' ) . '</a>';
            else
                $settings_link = '<a href="ms-admin.php?page=' . basename(__FILE__) . '">' . __( 'Settings', 'blog_templates' ) . '</a>';
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


        /**
         * Separated into its own function so we could include it in the init hook
         *
         * @since 1.0
         */
        function admin_options_page_posted() {
            if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->menu_slug )
                return;


            $model = nbt_get_model();
            $settings = nbt_get_settings();


            if (isset($_POST['save_options'])) {

                if (! wp_verify_nonce($_POST['_nbtnonce'], 'blog_templates-update-options') )
                    wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', 'blog_templates' ) );

                $defaults = nbt_get_default_settings();
                $settings['show-registration-templates'] = isset($_POST['show-registration-templates']) ? (int)$_POST['show-registration-templates'] : 0;
                $settings['registration-templates-appearance'] = isset($_POST['registration-templates-appearance']) ? $_POST['registration-templates-appearance'] : '';
                $settings['show-categories-selection'] = isset($_POST['show-categories-selection']) ? $_POST['show-categories-selection'] : 0;
                $settings['toolbar-color'] = isset($_POST['toolbar-color']) ? $_POST['toolbar-color'] : $defaults['toolbar-color'];
                $settings['toolbar-text-color'] = isset($_POST['toolbar-text-color']) ? $_POST['toolbar-text-color'] : $defaults['toolbar-text-color'];
                $settings['toolbar-border-color'] = isset($_POST['toolbar-border-color']) ? $_POST['toolbar-border-color'] : $defaults['toolbar-border-color'];
                $settings['selected-background-color'] = isset($_POST['selected-background-color']) ? $_POST['selected-background-color'] : $defaults['selected-background-color'];
                $settings['unselected-background-color'] = isset($_POST['unselected-background-color']) ? $_POST['unselected-background-color'] : $defaults['unselected-background-color'];

                if ( ! empty( $_POST['registration-templates-button-text'] ) )
					$settings['previewer_button_text'] = sanitize_text_field( $_POST['registration-templates-button-text'] );

                nbt_update_settings( $settings );

                $this->updated_message =  __( 'Options saved.', 'blog_templates' );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );
                return;
            }

            if ( isset( $_POST['submit_repair_database'] ) && isset( $_POST['action'] ) && 'repair_database' == $_POST['action' ] ) {

                if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'repair-database' ) )
                    return false;

                if ( ! ( isset( $_POST['repair-tables'] ) ) )
                    return false;
                
                $model = nbt_get_model();
                $model->create_tables();

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