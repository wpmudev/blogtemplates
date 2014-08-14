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

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

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
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}


}
