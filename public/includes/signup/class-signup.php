<?php 

class Blog_Templates_Signup {
	
    static $toolbar;

	public function __construct() {
		// Signup: WordPress            
        add_action( 'signup_hidden_fields', array( &$this, 'maybe_add_template_hidden_field' ) );
        add_action( 'signup_blogform', array( $this, 'registration_template_selection' ) );
        add_filter( 'add_signup_meta', array( $this, 'registration_template_selection_add_meta' ) );

        // Signup: BuddyPress
        add_action( 'bp_blog_details_fields', array( &$this, 'maybe_add_template_hidden_field' ) );
        add_action('bp_after_blog_details_fields', array($this, 'registration_template_selection'));
        add_filter('bp_signup_usermeta', array($this, 'registration_template_selection_add_meta'));
        add_action( 'bp_before_blog_details_fields', 'nbt_bp_add_register_scripts' );

        // Init Toolbar
        add_action( 'nbt_before_templates_signup', array( 'Blog_Templates_Signup', 'init_toolbar' ), 10 );

        include_once( NBT_PLUGIN_DIR . 'public/includes/class-templates-query.php' );
        $this->templates_query = new Blog_Templates_Templates_Query();
	}

	function maybe_add_template_hidden_field() {
        $settings = nbt_get_settings();
        if ( 'page_showcase' == $settings['registration-templates-appearance'] ) {
            if ( 'just_user' == $_REQUEST['blog_template'] ) {
                ?>
                    <input type="text" name="blog_template" value="just_user">
                    <script>
                        jQuery(document).ready(function($) {
                            $('#signupuser').attr('checked', true);
                            $('#signupblog').hide();
                            $('label[for="signupblog"]').hide();
                            $('#blog-details-section').hide();
                        });
                    </script>
                <?php
            }
            else {
                $value = isset( $_REQUEST['blog_template'] ) ? $_REQUEST['blog_template'] : '';
                ?>
                    <input type="hidden" name="blog_template" value="<?php echo absint( $_REQUEST['blog_template'] ); ?>">
                <?php
            }
            return;
        }
    }

    /**
     * Shows template selection on registration.
     */
    public static function registration_template_selection () {

        $settings = nbt_get_settings();

        if ( ! $settings['show-registration-templates'] ) 
            return false;

        // Setup vars
        $templates = $settings['templates'];

        $templates_to_remove = array();
        foreach ( $templates as $key => $template ) {

            if ( is_main_site( $template['blog_id'] ) )
                $templates_to_remove[] = $key;
        }

        if ( ! empty( $templates_to_remove ) ) {
            foreach ( $templates_to_remove as $key )
                unset( $templates[ $key ] );
        }


        $tpl_file_suffix = $settings['registration-templates-appearance'] ? '-' . $settings['registration-templates-appearance'] : '';
        $tpl_file = "blog_templates-registration{$tpl_file_suffix}.php";


        // Setup theme file
        $theme_file = locate_template( array( 'blogtemplates/' . $tpl_file ) );
        $theme_file = $theme_file ? $theme_file : NBT_PLUGIN_DIR . 'public/views/templates/' . $tpl_file;

        if ( ! file_exists( $theme_file ) ) 
            return false;

        // Showcase special case
        if ( 'page-showcase' === $tpl_file_suffix )
            add_action( 'nbt_before_templates_signup', array( 'Blog_Templates_Signup', 'set_signup_url_field' ), 15 );

        nbt_render_theme_selection_scripts( $settings );
        
        @include $theme_file;

    }

    public static function init_toolbar() {
        $settings = nbt_get_settings();

        if ( $settings['show-categories-selection'] ) {
            require_once( 'class-signup-toolbar.php' );
            $tpl_file_suffix = $settings['registration-templates-appearance'] ? '-' . $settings['registration-templates-appearance'] : '';
            self::$toolbar = new Blog_Templates_Signup_Toolbar( $tpl_file_suffix );
            self::$toolbar->display();
        }
    }

    /**
     * Store selected template in blog meta on signup.
     */
    public static function registration_template_selection_add_meta($meta) {
        $meta = $meta ? $meta : array();
        $settings = nbt_get_settings();
        $meta['blog_template'] = isset( $_POST['blog_template'] ) && is_numeric( $_POST['blog_template'] ) ? $_POST['blog_template'] : $settings['default'];
        return $meta;
    }

    public static function set_signup_url_field() {
        if ( class_exists( 'BuddyPress' ) ) {
            $sign_up_url = bp_get_signup_page();
        }
        else {
            $sign_up_url = network_site_url( 'wp-signup.php' );
            $sign_up_url = apply_filters( 'wp_signup_location', $sign_up_url );
        }

        $sign_up_url = add_query_arg( 'blog_template', 'just_user', $sign_up_url );
        
        ?>
            <p><a href="<?php echo esc_url( $sign_up_url ); ?>"><?php _e('Just a username, please.') ?></a></p>
        <?php
    }

}