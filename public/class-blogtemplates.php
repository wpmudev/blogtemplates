<?php

class Blog_Templates {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '3.0';

	/**
	 * @TODO - Rename "plugin-name" to the name of your plugin
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'blog_templates';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$this->includes();

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'maybe_upgrade' ) );
		add_action( 'init', array( $this, 'init_plugin' ) );

		add_action( 'delete_blog', array( &$this, 'maybe_delete_template' ), 10, 1 );

		$action_order = defined('NBT_APPLY_TEMPLATE_ACTION_ORDER') && NBT_APPLY_TEMPLATE_ACTION_ORDER ? NBT_APPLY_TEMPLATE_ACTION_ORDER : 9999;
        add_action('wpmu_new_blog', array($this, 'set_blog_defaults'), apply_filters('blog_templates-actions-action_order', $action_order), 6); // Set to *very high* so this runs after every other action; also, accepts 6 params so we can get to meta

		do_action( 'nbt_object_create', $this );

	}

	private function includes() {
		include_once( 'includes/model.php' );
		include_once( 'includes/class-settings-handler.php' );
		include_once( 'includes/helpers.php' );
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		include_once( 'includes/model.php' );
		$model = nbt_get_model();
		$model->create_tables();
		update_site_option( 'nbt_plugin_version', self::VERSION );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		delete_site_option( 'nbt_plugin_version' );
	}


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	function maybe_upgrade() {

        // Split posts option into posts and pages options
        $saved_version = get_site_option( 'nbt_plugin_version', false );

        if ( $saved_version === false ) {
            self::activate();
            return;
        }

        if ( ! $saved_version )
            $saved_version = '1.7.2';

        if ( $saved_version == self::VERSION )
            return;

        require_once( 'includes/upgrade.php' );

        if ( version_compare( $saved_version, '1.7.2', '<=' ) ) {
            $options = get_site_option( 'blog_templates_options', array( 'templates' => array() ) );
            $new_options = $options;
            foreach ( $options['templates'] as $key => $template ) {
                $to_copy = $template['to_copy'];
                if ( in_array( 'posts', $to_copy ) )
                    $new_options['templates'][ $key ]['to_copy'][] = 'pages';
            }

            update_site_option( 'blog_templates_options', $new_options );
            
        }
        

        if ( version_compare( $saved_version, '1.7.6', '<' ) ) {
            $options = get_site_option( 'blog_templates_options', array( 'templates' => array() ) );
            $new_options = $options;

            foreach ( $options['templates'] as $key => $template ) {
                $new_options['templates'][ $key ]['block_posts_pages'] = false;
                $new_options['templates'][ $key ]['post_category'] = array( 'all-categories' );
            }
            
            update_site_option( 'blog_templates_options', $new_options );
        }


        if ( version_compare( $saved_version, '1.9', '<' ) ) {
            $model = nbt_get_model();
            $model->create_tables();
            blog_templates_upgrade_19();
        }

        if ( version_compare( $saved_version, '1.9.1', '<' ) ) {
            blog_templates_upgrade_191();
        }

        if ( version_compare( $saved_version, '2.0', '<' ) ) {
            $model = nbt_get_model();
            $model->create_tables();

            // Due to a server issue in WPMUDEV we need to upgrade again in the same way
            blog_templates_upgrade_191();

            blog_templates_upgrade_20();
        }

        if ( version_compare( $saved_version, '2.2', '<' ) ) {
            blog_templates_upgrade_22();
        }

        if ( version_compare( $saved_version, '2.6.2', '<' ) ) {
            blog_templates_upgrade_262();
        }

        update_site_option( 'nbt_plugin_version', self::VERSION );

    }

    public function init_plugin() {

    }

    /**
     * Delete templates attached to blogs that no longer exist
     * 
     * @param Integer $blog_id 
     */
    function maybe_delete_template( $blog_id ) {

        $delete_template_ids = array();
        $settings = nbt_get_settings();

        // Searching those templates attached to that blog
        foreach ( $settings['templates'] as $key => $template ) {
            if ( $template['blog_id'] == $blog_id ) {
                $delete_template_ids[] = $key;
            }
        }

        // Deleting and saving new options
        if ( ! empty( $delete_template_ids ) ) {
            $model = nbt_get_model();
            foreach ( $delete_template_ids as $template_id ) {
                unset( $settings['templates'][ $template_id ] );

                if ( $settings['default'] == $template_id )
                    $settings['default'] = false;

                $model->delete_template( $template_id );

                do_action( 'blog_templates_delete_template', $template_id );

                nbt_update_settings( $settings );
            }
        }
    }

    /**
     * Checks for a template to use, and if it exists, copies the templated settings to the new blog
     *
     * @param mixed $blog_id
     * @param mixed $user_id
     *
     */
    function set_blog_defaults( $blog_id, $user_id, $_passed_domain=false, $_passed_path=false, $_passed_site_id=false, $_passed_meta=false ) {
        

    }



}
