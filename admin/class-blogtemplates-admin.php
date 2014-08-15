<?php

/**
 * Plugin class. Manage the administrative side on WordPress
 *
 *
 * @package Blog_Templates_Admin
 */
class Blog_Templates_Admin {

	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 */
	private function __construct() {

		$plugin = Blog_Templates::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'network_admin_plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Menus
		include_once( 'class-main-menu.php' );
		include_once( 'class-categories-menu.php' );
		include_once( 'class-settings-menu.php' );
		new Blog_Templates_Main_Menu();

		if ( apply_filters( 'nbt_activate_categories_feature', true ) )
			new Blog_Templates_Categories_Menu();
		new Blog_Templates_Settings_Menu();

		add_action('admin_footer', array($this,'add_template_dd'));

		/**
         * From 1.7.1 version we are not allowing to template the main site
         * This will alert the user to remove that template
         */
        add_action( 'all_admin_notices', array( &$this, 'alert_main_site_templated' ) );

        // Special features for Multi-Domains
        add_action( 'add_multi_domain_form_field', array($this, 'multi_domain_form_field' ) ); // add field to domain addition form
        add_action( 'edit_multi_domain_form_field', array($this, 'multi_domain_form_field' ) ); // add field to domain edition form
        add_filter( 'md_update_domain', array($this, 'multi_domain_update_domain' ), 10, 2 ); // saves blog template value on domain update
        add_filter( 'manage_multi_domains_columns', array($this, 'manage_multi_domains_columns' ) ); // add column to multi domain table
        add_action( 'manage_multi_domains_custom_column', array($this, 'manage_multi_domains_custom_column' ), 10, 2 ); // populate blog template column in multi domain table

	}

	/**
	 * Return an instance of this class.
	 *
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function enqueue_admin_styles() {
		wp_enqueue_style( 'nbt-icons', plugins_url( 'assets/css/icons.css', __FILE__ ) );
	}



	/**
	 * Add settings action link to the plugins page.
	 *
	 */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . network_admin_url( 'settings.php?page=blog_templates_main' ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
     * Adds the Template dropdown to the WPMU New Blog form
     *
     */
    public function add_template_dd() {
        global $pagenow;
        if( ! in_array( $pagenow, array( 'ms-sites.php', 'site-new.php' ) ) || isset( $_GET['action'] ) && 'editblog' == $_GET['action'] )
            return;

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('.form-table:last tr:last').before('\
                <tr class="form-field form-required">\
                    <th scope="row"><?php _e('Template', 'blog_templates') ?></th>\
                    <td><?php $this->get_template_dropdown('blog_template_admin',true); ?></td>\
                </tr>');
            });
        </script>
        <?php
    }

    /**
     * Returns a dropdown of all blog templates
     *
     */
    function get_template_dropdown( $tag_name, $include_none, $echo = true, $esc_js = true ) {

        $settings = nbt_get_settings();
        $templates = array();
        foreach ( $settings['templates'] as $key => $template ) {
            if ( ! is_main_site( absint( $template['blog_id'] ) ) )
                $templates[$key] = $template['name'];
        }

        $selector = '';
        if ( is_array( $templates ) ) {
            $selector .= '<select name="' . esc_attr( $tag_name ) . '">';
            if ( $include_none )
                $selector .= '<option value="none">' . __( 'None', 'blog_templates' ) . '</option>';
            
            foreach ( $templates as $key => $value ) {
                $label = ( $esc_js ) ? esc_js( $value ) : stripslashes_deep( $value );
                $selector .= '<option value="' . esc_attr( $key ) . '" ' . esc_attr( selected( $key == $settings['default'], true, false ) ) . '>' . $label . '</option>';
            }
            $selector .= '</select>';    

        }

        if ( $echo )
            echo $selector;
        else
            return $selector;
    }

    function alert_main_site_templated() {
	    $settings = nbt_get_settings();
	    if ( ! empty( $settings['templates'] ) ) {
	        $main_site_templated = false;
	        foreach ( $settings['templates'] as $template ) {
	            if ( is_main_site( absint( $template['blog_id'] ) ) )
	                $main_site_templated = true;
	        }

	        if ( $main_site_templated && is_super_admin() ) {
	            $settings_url = add_query_arg( 'page', 'blog_templates_main', network_admin_url( 'settings.php' ) );
	            ?>
	                <div class="error">
	                    <p><?php printf( __( '<strong>New Blog Templates alert:</strong> The main site cannot be templated from 1.7.1 version, please <a href="%s">go to settings page</a> and remove that template (will not be shown as a choice from now on)', 'blog_templates' ), $settings_url ); ?></p>
	                </div>
	            <?php
	        }
	    }
	}

	/**
    * Save Blog Template value in the current domain array
    *
    * @since 1.2
    */
    function multi_domain_update_domain( $current_domain, $domain ) {
        $current_domain['blog_template'] = isset( $domain['blog_template'] ) ? $domain['blog_template'] : '';

        return $current_domain;
    }

    /**
    * Adds Blog Template column to Multi-Domains table
    *
    * @since 1.2
    */
    function manage_multi_domains_columns( $columns ) {
        $columns['blog_template'] = __( 'Blog Template', 'blog_templates' );
        return $columns;
    }

    /**
    * Display content of the Blog Template column in the Multi-Domains table
    *
    * @since 1.2
    */
    function manage_multi_domains_custom_column( $column_name, $domain ) {
        if( 'blog_template' == $column_name ) {
            $settings = nbt_get_settings();
            if( !isset( $domain['blog_template'] ) ) {
                echo 'Default';
            } elseif( !is_numeric( $domain['blog_template'] ) ) {
                echo 'Default';
            } else {
                $key = $domain['blog_template'];
                echo $settings['templates'][$key]['name'];
            }
        }
    }

    /**
    * Adds field for Multi Domain addition and edition forms
    *
    * @since 1.2
    */
    function multi_domain_form_field( $domain = '' ) {

        $settings = nbt_get_settings();
        if( count( $settings['templates'] ) <= 1 ) // don't display field if there is only one template or none
            return false;
        ?>
        <tr>
            <th scope="row"><label for="blog_template"><?php _e( 'Default Blog Template', 'blog_templates' ) ?>:</label></th>
            <td>
                <select id="blog_template" name="blog_template">
                    <option value=""><?php _e( 'Default', 'blog_templates' ); ?></option>
                    <?php
                    foreach( $settings['templates'] as $key => $blog_template ) {
                        $selected = isset( $domain['blog_template'] ) ? selected( $key, $domain['blog_template'], false ) : '';
                        echo "<option value='$key'$selected>$blog_template[name]</option>";
                    }
                    ?>
                </select><br />
                <span class="description"><?php _e( 'Default Blog Template used for this domain.', 'blog_templates' ) ?></span>
            </td>
        </tr>
        <?php
    }


}
