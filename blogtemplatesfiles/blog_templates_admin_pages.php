<?php

class blog_templates_admin_pages {

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
        add_action( 'network_admin_notices', array($this, 'admin_options_page_posted' ) );
        add_action( 'admin_notices', array($this, 'admin_options_page_posted' ) );

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
    		'default' => ''
    	);
    }

    public function add_javascript($hook) {
    	if ( get_current_screen()->id == $this->page_id . '-network' ) {
    		wp_enqueue_script( 'nbt-settings-js', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/settings.js', array( 'jquery' ) );
    		wp_enqueue_style( 'nbt-settings-css', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/settings.css' );
    	}
    }

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_submenu_page( 'settings.php', __( 'Blog Templates', $this->localization_domain ), __( 'Blog Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
    }

    /**
     * Adds the options subpanel
     *
     * @since 1.0
     */
    function pre_3_1_network_admin_page() {
        if ( get_bloginfo('version') >= 3 )
            add_submenu_page( 'ms-admin.php', __( 'Blog Templates', $this->localization_domain ), __( 'Blog Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
        else
            add_submenu_page( 'wpmu-admin.php', __( 'Blog Templates', $this->localization_domain ), __( 'Blog Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'filter_plugin_actions' ) );
    }

    /**
     * Adds settings/options page
     *
     * @since 1.0
     */
    function admin_options_page() {

	    $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

	    global $pagenow;
	    $url = $pagenow . '?page=' . $this->menu_slug;

			?>

			<div class="wrap">
			    <form method="post" id="options">
			        <?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); 
			        if ( ! is_numeric( $t ) ) { ?>
			            <h2>Blog Templates</h2>
			            <?php 
			                $templates_table = new NBT_Templates_Table( $this->options ); 
			                $templates_table->prepare_items();
			                $templates_table->display();
			            ?>
			            
			            <h2><?php _e('Create New Blog Template',$this->localization_domain); ?></h2>
			            <p><?php _e('Create a blog template based on the blog of your choice! This allows you (and other admins) to copy all of the selected blog\'s settings and allow you to create other blogs that are almost exact copies of that blog. (Blog name, URL, etc will change, so it\'s not a 100% copy)',$this->localization_domain); ?></p>
			            <p><?php _e('Simply fill out the form below and click "Create Blog Template!" to generate the template for later use!',$this->localization_domain); ?></p>
			            <table class="form-table">
			                <?php ob_start(); ?>
			                    <input name="template_name" type="text" id="template_name" class="regular-text"/>
			                <?php $this->render_row( __( 'Template Name:', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <input name="copy_blog_id" type="text" id="copy_blog_id" class="small-text"/>
			                <?php $this->render_row( __( 'Blog ID:', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <textarea class="large-text" name="template_description" type="text" id="template_description" cols="45" rows="5"></textarea>
			                <?php $this->render_row( __( 'Template Description:', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php 
			                    ob_start(); 
			                    $options_to_copy = array(
			                        'settings' => __( 'Wordpress Settings, Current Theme, and Active Plugins', $this->localization_domain ),
			                        'posts'    => __( 'Posts <strong>(Once the template is created you will be able to select between categories)</strong>', $this->localization_domain ),
			                        'pages'    => __( 'Pages', $this->localization_domain ),
			                        'terms'    => __( 'Categories, Tags, and Links', $this->localization_domain ),
			                        'users'    => __( 'Users', $this->localization_domain ),
			                        'menus'    => __( 'Menus', $this->localization_domain ),
			                        'files'    => __( 'Files', $this->localization_domain )
			                        
			                    );
			                    foreach ( $options_to_copy as $key => $value ) {
			                        echo "<input type='checkbox' name='to_copy[]' id='nbt-{$key}' value='$key'>&nbsp;<label for='nbt-{$key}'>$value</label><br/>";
			                    }
			                ?>
			                <?php $this->render_row( __( 'What To Copy To New Blog?', $this->localization_domain ), ob_get_clean() ); ?>

			                
			                <?php if ( is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ): ?>
			                    <?php ob_start(); ?>
			                        <input type='checkbox' name='copy_status' id='nbt-copy-status'>
			                        <label for='nbt-copy-status'><?php _e( 'Check if you want also to copy the blog status (Public or not)', $this->localization_domain ); ?></label>
			                    <?php $this->render_row( __( 'Copy Status?', $this->localization_domain ), ob_get_clean() ); ?>
			                <?php endif; ?>

		                    <?php ob_start(); ?>
		                        <input type='checkbox' name='block_posts_pages' id='nbt-block-posts-pages'>
		                        <label for='nbt-block-posts-pages'><?php _e( 'Check if you want to block for edition (even for the blog administrator) the posts/pages created by the template by default', $this->localization_domain ); ?></label>
		                    <?php $this->render_row( __( 'Block Posts/Pages', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <p><?php _e('After you add this template, an advanced options area will show up on the edit screen (Click on the template name when it appears in the list above). In that advanced area, you can choose to add full tables to the template, in case you\'re using a plugin that creates its own database tables. Note that this is not required for new Blog Templates to work', $this->localization_domain); ?></p>
			                <?php $this->render_row( __( 'Advanced', $this->localization_domain ), ob_get_clean() ); ?>
			                
			            </table>
			            <p><?php _e('Please note that this will turn the blog you selected into a template blog. Any changes you make to this blog will change the template, as well! We recommend creating specific "Template Blogs" for this purpose, so you don\'t accidentally add new settings, content, or users that you don\'t want in your template.',$this->localization_domain); ?></p>
			            <p><?php printf( __( 'This means that if you would like to create a dedicated template blog for this template, please <a href="%1$s">create a new blog</a> and then visit this page to create the template.',$this->localization_domain ), '<a href="' . ( get_bloginfo('version') >= 3 ) ? network_admin_url('site-new.php') : admin_url('wpmu-blogs.php') . '">'); ?></p>

			            <p><div class="submit"><input type="submit" name="save_new_template" class="button-primary" value="Create Blog Template!" /></div></p>
			            
			            <h2><?php _e('Options', $this->localization_domain); ?></h2>
			            <p>
			                <label for="show-registration-templates">
			                    <?php _e('Show templates selection on registration:', $this->localization_domain); ?>
			                    <input type="checkbox"
			                        <?php echo (
			                            !empty($this->options['show-registration-templates']) ? 'checked="checked"' : ''
			                        ); ?>
			                        name="show-registration-templates" id="show-registration-templates" value="1" 
			                    />
			                </label>
			            </p>
			            <p>
			                <?php _e('Selecting this option will allow your new users to choose between templates when they sign up for a site.', $this->localization_domain); ?>
			            </p>
			            <?php $appearance_template = $this->_get_config_option('registration-templates-appearance'); ?>
			            <p>
			                <label for="registration-templates-appearance-select">
			                    <input type="radio"
			                        <?php echo (
			                            empty($appearance_template) ? 'checked="checked"' : ''
			                        ); ?>
			                        name="registration-templates-appearance" id="registration-templates-appearance-select" value="" 
			                    />
			                    <?php _e('As simple selection box', $this->localization_domain); ?>
			                </label>
			            </p>
			            <p>
			                <label for="registration-templates-appearance-description">
			                    <input type="radio"
			                        <?php echo (
			                            'description' == $appearance_template ? 'checked="checked"' : ''
			                        ); ?>
			                        name="registration-templates-appearance" id="registration-templates-appearance-description" value="description" 
			                    />
			                    <?php _e('As radio-box selection with descriptions', $this->localization_domain); ?>
			                </label>
			            </p>
			            <p>
			                <label for="registration-templates-appearance-screenshot">
			                    <input type="radio"
			                        <?php echo (
			                            'screenshot' == $appearance_template ? 'checked="checked"' : ''
			                        ); ?>
			                        name="registration-templates-appearance" id="registration-templates-appearance-screenshot" value="screenshot" 
			                    />
			                    <?php _e('As theme screenshot selection', $this->localization_domain); ?>
			                </label>
			            </p>
			            <p>
			                <label for="registration-templates-appearance-screenshot_plus">
			                    <input type="radio"
			                        <?php echo (
			                            'screenshot_plus' == $appearance_template ? 'checked="checked"' : ''
			                        ); ?>
			                        name="registration-templates-appearance" id="registration-templates-appearance-screenshot_plus" value="screenshot_plus" 
			                    />
			                    <?php _e('As theme screenshot selection with titles and description', $this->localization_domain); ?>
			                </label>
			            </p>
			            <?php
			            /* Will be on next releases
			            <p>
			                <label for="registration-templates-appearance-previewer">
			                    <input type="radio" <?php checked( 'previewer' == $appearance_template ); ?> name="registration-templates-appearance" id="registration-templates-appearance-previewer" value="previewer" />
			                    <?php _e('As a theme previewer', $this->localization_domain); ?>
			                </label>
			            </p>*/
			            ?>
			            <p><div class="submit"><input type="submit" name="save_options" class="button-primary" value="<?php esc_attr_e(__('Save Options', $this->localization_domain));?>" /></div></p>
			            
			        <?php
			            } else {
			                $template = $this->options['templates'][$t];
			        ?>
			            <p><a href="<?php echo $url; ?>">&laquo; <?php _e('Back to Blog Templates', $this->localization_domain); ?></a></p>
			            <h2><?php _e('Edit Blog Template', $this->localization_domain); ?></h2>
			             <table class="form-table">
			                <?php ob_start(); ?>
			                    <input name="template_name" type="text" id="template_name" class="regular-text" value="<?php esc_attr_e( $template['name'] );?>"/>
			                <?php $this->render_row( __( 'Template Name:', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <textarea class="widefat" name="template_description" id="template_description" cols="45" rows="5"><?php echo esc_textarea($template['description']);?></textarea>
			                <?php $this->render_row( __( 'Template Description', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php 
			                    ob_start(); 
			                    $options_to_copy = array(
			                        'settings' => __( 'Wordpress Settings, Current Theme, and Active Plugins', $this->localization_domain ),
			                        'posts'    => __( 'Posts', $this->localization_domain ),
			                        'pages'    => __( 'Pages', $this->localization_domain ),
			                        'terms'    => __( 'Categories, Tags, and Links', $this->localization_domain ),
			                        'users'    => __( 'Users', $this->localization_domain ),
			                        'menus'    => __( 'Menus', $this->localization_domain ),
			                        'files'    => __( 'Files', $this->localization_domain )
			                        
			                    );

			                    foreach ( $options_to_copy as $key => $value ) {
			                        ?>
			                            <input type="checkbox" name="to_copy[]" id="nbt-<?php echo $key; ?>" value="<?php echo $key; ?>" <?php checked( in_array( $key, $template['to_copy'] ) ); ?>> <label for='nbt-<?php echo $key; ?>'><?php echo $value; ?></label><br/>
			                            <?php 

			                            if ( 'posts' === $key ) {
			                            		switch_to_blog( $template['blog_id'] );
			                            		$args = array(
			                            			'hide_empty' => 0,
			                            			'hierarchical' => true
			                            		);
			                            		$categories = get_categories( $args );

			                            		restore_current_blog();

			                            		if ( $categories ):
				                            		?>
					                            		<p id="categories_list_title"><strong><?php _e( 'Select categories to be copied', $this->localization_domain ); ?></strong></p>
					                            		<div id="categories_list">
					                            			<label for="category-all"><input id="category-all" type="checkbox" name="posts_categories[]" <?php checked( in_array( 'all-categories', $template['posts_categories'] ) ); ?> value="all-categories"> <?php _e( 'All categories', $this->localization_domain ); ?></label><br/>
						                            		<?php
							                            		foreach ( $categories as $category ) {
							                            			?>
																		<label for="category-<?php echo $category->term_id; ?>"><input id="category-<?php echo $category->term_id; ?>" type="checkbox" name="posts_categories[]" value="<?php echo $category->term_id; ?>" <?php checked( in_array( $category->term_id, $template['posts_categories'] ) ); ?>> <?php echo $category->name; ?></label><br/>
							                            			<?php
							                            		}
					                            	?></div><?php
					                            endif;
			                            	?><br/><?php
			                        	}
			                    }
			                ?>
			                <?php $this->render_row( __( 'What To Copy To New Blog?', $this->localization_domain ), ob_get_clean() ); ?>

			                
			                <?php if ( is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ): ?>
			                    <?php ob_start(); ?>
			                        <input type='checkbox' name='copy_status' id='nbt-copy-status' <?php checked( ! empty( $template['copy_status'] ) ); ?>>
			                        <label for='nbt-copy-status'><?php _e( 'Check if you want also to copy the blog status (Public or not)', $this->localization_domain ); ?></label>
			                    <?php $this->render_row( __( 'Copy Status?', $this->localization_domain ), ob_get_clean() ); ?>
			                <?php endif; ?>

			                <?php ob_start(); ?>
		                        <input type='checkbox' name='block_posts_pages' id='nbt-block-posts-pages' <?php checked( $template['block_posts_pages'] ); ?>>
		                        <label for='nbt-block-posts-pages'><?php _e( 'Check if you want to block for edition (even for the blog administrator) the posts/pages created by the template by default', $this->localization_domain ); ?></label>
		                    <?php $this->render_row( __( 'Block Posts/Pages', $this->localization_domain ), ob_get_clean() ); ?>
			                
			            </table>
			            
			            <p><div class="submit"><input type="submit" name="save_updated_template" value="<?php _e('Save', $this->localization_domain); ?> &raquo;" class="button-primary" /></div></p>

			            
			            <?php
			                global $wpdb;

			                switch_to_blog($template['blog_id']);
			            ?>
			            <h2><?php _e('Advanced Options',$this->localization_domain); ?></h2>
			                        
			            <p><?php printf(__('The tables listed here were likely created by plugins you currently have or have had running on this blog. If you want the data from these tables copied over to your new blogs, add a checkmark next to the table. Note that the only tables displayed here begin with %s, which is the standard table prefix for this specific blog. Plugins not following this convention will not have their tables listed here.',$this->localization_domain),$wpdb->prefix); ?></p>
			            <table class="form-table">
			                <?php ob_start();

			                //Grab all non-core tables and display them as options
			                // Changed
			                $pfx = class_exists("m_wpdb") ? $wpdb->prefix : str_replace('_','\_',$wpdb->prefix);
			                

			                //$results = $wpdb->get_results("SHOW TABLES LIKE '" . str_replace('_','\_',$wpdb->prefix) . "%'", ARRAY_N);
			                $results = $wpdb->get_results("SHOW TABLES LIKE '{$pfx}%'", ARRAY_N);

			                if (!empty($results)) {

			                    foreach($results as $result) {
			                        if (!in_array(str_replace($wpdb->prefix,'',$result['0']),$wpdb->tables)) {

			                            if (class_exists("m_wpdb")) {
			                                $db = $wpdb->analyze_query("SHOW TABLES LIKE '{$pfx}%'");
			                                $dataset = $db['dataset'];
			                                $current_db = $wpdb->dbh_connections[$dataset];
			                                $val = $current_db['name'] . '.' . $result[0];
			                            } else {
			                                $val =  $result[0];
			                            }
			                            if ( stripslashes_deep( $pfx ) == $wpdb->base_prefix ) {
			                                // If we are on the main blog, we'll have to avoid those tables from other blogs
			                                $pattern = '/^' . stripslashes_deep( $pfx ) . '[0-9]/';
			                                if ( preg_match( $pattern, $result[0] ) )
			                                    continue;
			                            }
			                            //echo "<input type='checkbox' name='additional_template_tables[]' value='$result[0]'";
			                            echo "<input type='checkbox' name='additional_template_tables[]' value='{$val}'";
			                            if ( isset( $template['additional_tables'] ) && is_array( $template['additional_tables'] ) )
			                                //if ( in_array( $result[0], $template['additional_tables'] ) )
			                                if ( in_array( $val, $template['additional_tables'] ) )
			                                    echo ' checked="CHECKED"';
			                            echo " id='nbt-{$val}'>&nbsp;<label for='nbt-{$val}'>{$result[0]}</label><br/>";
			                        }
			                    }
			                } else {
			                    _e('There are no additional tables to display for this blog',$this->localization_domain);
			                }
			                // End changed
			                
			                
			                $this->render_row( __( 'Additional Tables', $this->localization_domain ), ob_get_clean() ); ?>

			            </table>
			            <p><div class="submit"><input type="submit" name="save_updated_template" value="<?php _e('Save', $this->localization_domain); ?> &raquo;" class="button-primary" /></div></p>
			            <?php restore_current_blog(); ?>
			        <?php } ?>
			    </form>
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
            if ( !isset( $_GET['page'] ) || $_GET['page'] !== $this->menu_slug )
                return;

            unset( $this->options['templates'][''] ); //Delete the [] item, this will fix corrupted data

            $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

            if( !empty( $_POST['save_updated_template'] ) ) {
                if (! wp_verify_nonce($_POST['_nbtnonce'], 'blog_templates-update-options') )
                    die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                $this->options['templates'][$t]['name'] = stripslashes($_POST['template_name']);
                $this->options['templates'][$t]['description'] = stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) );
                $this->options['templates'][$t]['to_copy'] = isset( $_POST['to_copy'] ) ? (array)$_POST['to_copy'] : array();
                $this->options['templates'][$t]['additional_tables'] = isset( $_POST['additional_template_tables'] ) ? $_POST['additional_template_tables'] : array();
                $this->options['templates'][$t]['copy_status'] = isset( $_POST['copy_status'] ) ? true : false;
                $this->options['templates'][$t]['block_posts_pages'] = isset( $_POST['block_posts_pages'] ) ? true : false;
                if ( ! isset( $_POST['posts_categories'] ) ) {
                	$this->options['templates'][$t]['posts_categories'] = array( 'all-categories' );
                }
                else {

                	$categories = $_POST['posts_categories'];

                	if ( in_array( 'all-categories', $categories ) ) {
                		$this->options['templates'][$t]['posts_categories'] = array( 'all-categories' );
                	}
                	else {
                		$this->options['templates'][$t]['posts_categories'] = array();
                		foreach( $categories as $category ) {
                			if ( ! is_numeric( $category ) )
                				continue;

                			$this->options['templates'][$t]['posts_categories'][] = absint( $category );
                		}
                	}
                }

                $this->save_admin_options();

                echo '<div class="updated fade"><p>Success! Your changes were sucessfully saved!</p></div>';
            } elseif( !empty( $_POST['save_new_template'] ) ) {
                if ( ! wp_verify_nonce( $_POST['_nbtnonce'], 'blog_templates-update-options' ) )
                    die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                if ( ! get_blog_details( (int) $_POST['copy_blog_id'] ) )
                    die( __( 'Whoops! The blog ID you posted is incorrect. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                if ( is_main_site( (int) $_POST['copy_blog_id'] ) )
                    die( __( 'Whoops! The blog ID you posted is incorrect. You cannot template the main site. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                $this->options['templates'][] = array(
                    'name' => (!empty($_POST['template_name']) ? stripslashes( $_POST['template_name'] ) : ''),
                    'description' => (!empty($_POST['template_description']) ? stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) ) : ''),
                    'blog_id' => (int)$_POST['copy_blog_id'],
                    'to_copy' => (!empty($_POST['to_copy']) ? (array)$_POST['to_copy'] : array()),
                    'copy_status' => isset( $_POST['copy_status'] ) ? true : false,
                    'block_posts_pages' => isset( $_POST['block_posts_pages'] ) ? true : false
                );

                $this->save_admin_options();

                echo '<div class="updated fade"><p>' . __( 'Success! Your changes were sucessfully saved!', $this->localization_domain ) . '</p></div>';
            } elseif( isset( $_GET['remove_default'] ) ) {
                if ( ! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-remove_default') )
                    die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );
                unset($this->options['default']);

                $this->save_admin_options();

                if ( ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) || ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) ) {
                    //These querystring vars must have been left over from an earlier link click, remove them
                    $to_url = remove_query_arg(array('default','d'),$this->currenturl_with_querystring);
                } else {
                    $to_url = $this->currenturl_with_querystring;
                }
                echo '<div class="updated fade"><p>' . __( 'Success! The default option was successfully turned off.', $this->localization_domain ) . '</p></div>';
            } elseif ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {
                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-make_default') )
                    die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                $this->options['default'] = (int)$_GET['default'];

                $this->save_admin_options();

                echo '<div class="updated fade"><p>' . __( 'Success! The default template was sucessfully updated.', $this->localization_domain ) . '</p></div>';
            } elseif ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) {
                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-delete_template') )
                    die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );
                unset($this->options['templates'][$_GET['d']]);

                $this->save_admin_options();

                echo '<div class="updated fade"><p>' . __( 'Success! The template was sucessfully deleted.', $this->localization_domain ) . '</p></div>';
            } else if (isset($_POST['save_options'])) {
                if (! wp_verify_nonce($_POST['_nbtnonce'], 'blog_templates-update-options') )
                    die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );
                $this->options['show-registration-templates'] = isset($_POST['show-registration-templates']) ? (int)$_POST['show-registration-templates'] : 0;
                $this->options['registration-templates-appearance'] = isset($_POST['registration-templates-appearance']) ? $_POST['registration-templates-appearance'] : '';
                $this->save_admin_options(); 

            }
        }

}