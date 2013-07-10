<?php

function nbt_get_sites_search() {
	global $wpdb, $current_site;

	if ( ! empty( $_POST['term'] ) ) 
		$term = $_REQUEST['term'];
	else
		echo json_encode( array() );

	$s = isset( $_REQUEST['term'] ) ? stripslashes( trim( $_REQUEST[ 'term' ] ) ) : '';
	$wild = '%';
	if ( false !== strpos($s, '*') ) {
		$wild = '%';
		$s = trim($s, '*');
	}

	$like_s = esc_sql( like_escape( $s ) );
	$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

	if ( $like_s != trim('/', $current_site->path) )
		$blog_s = $current_site->path . $like_s . $wild . '/';
	else
		$blog_s = $like_s;

	$query .= " AND  ( {$wpdb->blogs}.path LIKE '$blog_s' ) LIMIT 10";
	$results = $wpdb->get_results( $query );

	$returning = array();
	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			$details = get_blog_details( $row->blog_id );
			$returning[] = array( 
				'blog_name' => $details->blogname,
				'path' => $row->path, 
				'blog_id' => $row->blog_id 
			);
		}
	}

	echo json_encode( $returning );

	die();
}
add_action( 'wp_ajax_nbt_get_sites_search', 'nbt_get_sites_search' );

class blog_templates_main_menu {

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

    var $menu_slug = 'blog_templates_main';

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
    		'categories_selection' => false
    	);
    }

    public function add_javascript($hook) {

    	if ( get_current_screen()->id == $this->page_id . '-network' ) {
    		wp_enqueue_script( 'nbt-settings-js', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-settings.js', array( 'jquery' ) );
    		wp_enqueue_script( 'nbt-autocomplete-js', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/js/nbt-autocomplete.js', array( 'jquery' ) );
    		wp_enqueue_style( 'nbt-settings-css', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/settings.css' );

    		wp_enqueue_script( 'jquery-ui-autocomplete' );

    		wp_enqueue_style( 'nbt-jquery-ui-styles', NBT_PLUGIN_URL . 'blogtemplatesfiles/assets/css/jquery-ui.css' );

			$params = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			);
			wp_localize_script( 'nbt-settings-js', 'export_to_text_js', $params );
    	}
    }

	/**
     * Adds the options subpanel
     *
     * @since 1.2.1
     */
    function network_admin_page() {
        $this->page_id = add_menu_page( __( 'Blog Templates', $this->localization_domain ), __( 'Blog Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
    }

    /**
     * Adds the options subpanel
     *
     * @since 1.0
     */
    function pre_3_1_network_admin_page() {
        if ( get_bloginfo('version') >= 3 )
            add_menu_page( __( 'Templates', $this->localization_domain ), __( 'Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
        else
            add_menu_page( __( 'Templates', $this->localization_domain ), __( 'Templates', $this->localization_domain ), 'manage_network', $this->menu_slug, array($this,'admin_options_page'));
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
			    <form method="post" id="options" enctype="multipart/form-data">
			        <?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); 
			        
			        if ( ! is_numeric( $t ) ) { ?>
			            <h2>Blog Templates</h2>
			            <?php 
			                $templates_table = new NBT_Templates_Table(); 
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
			                    <input name="copy_blog_id" type="text" id="copy_blog_id" class="small-text" placeholder="<?php _e( 'Blog ID', $this->localization_domain ); ?>"/>
			                    <div class="ui-widget">
				                    <label for="search_for_blog"> <?php _e( 'Or search by blog path', $this->localization_domain ); ?> 
										<input type="text" id="search_for_blog" class="medium-text">
										<span class="description"><?php _e( 'For example, if the blog you are searching has an URL like http://ablog.mydomain.com, you can type "ablog"', $this->localization_domain ); ?></span>
				                    </label>
				                </div>
			                <?php $this->render_row( __( 'Blog ID:', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php ob_start(); ?>
			                    <textarea class="large-text" name="template_description" type="text" id="template_description" cols="45" rows="5"></textarea>
			                <?php $this->render_row( __( 'Template Description:', $this->localization_domain ), ob_get_clean() ); ?>

			                <?php 
			                	ob_start();
			                    echo '<strong>' . __( 'After you add this template, a set of options will show up on the edit screen.', $this->localization_domain ) . '</strong>';
			                ?>
			                <?php $this->render_row( __( 'More options', $this->localization_domain ), ob_get_clean() ); ?>
			                
			            </table>
			            <p><?php _e('Please note that this will turn the blog you selected into a template blog. Any changes you make to this blog will change the template, as well! We recommend creating specific "Template Blogs" for this purpose, so you don\'t accidentally add new settings, content, or users that you don\'t want in your template.',$this->localization_domain); ?></p>
			            <p><?php printf( __( 'This means that if you would like to create a dedicated template blog for this template, please <a href="%1$s">create a new blog</a> and then visit this page to create the template.',$this->localization_domain ), '<a href="' . ( get_bloginfo('version') >= 3 ) ? network_admin_url('site-new.php') : admin_url('wpmu-blogs.php') . '">'); ?></p>

			            <p><div class="submit"><input type="submit" name="save_new_template" class="button-primary" value="Create Blog Template!" /></div></p>
			            
			            
			        <?php
			            } else {
			            	$model = blog_templates_model::get_instance();
			                $template = $model->get_template( $t );
			        ?>
			            <p><a href="<?php echo $url; ?>">&laquo; <?php _e('Back to Blog Templates', $this->localization_domain); ?></a></p>
			            <h2><?php _e('Edit Blog Template', $this->localization_domain); ?></h2>
			            <input type="hidden" name="template_id" value="<?php echo $t; ?>" />
			            <div id="nbtpoststuff">
			            	<div id="post-body" class="metabox-holder columns-2">
			            		<div id="post-body-content">
					            	<table class="form-table">
						               	 <?php ob_start(); ?>
						                    <input name="template_name" type="text" id="template_name" class="regular-text" value="<?php esc_attr_e( $template['name'] );?>"/>
						                <?php $this->render_row( __( 'Template Name:', $this->localization_domain ), ob_get_clean() ); ?>

						                <?php ob_start(); ?>
						                    <textarea class="widefat" name="template_description" id="template_description" cols="45" rows="5"><?php echo esc_textarea( $template['description'] );?></textarea>
						                <?php $this->render_row( __( 'Template Description', $this->localization_domain ), ob_get_clean() ); ?>

						                <?php 
						                    ob_start(); 
						                    $options_to_copy = array(
						                        'settings' => __( 'Wordpress Settings, Current Theme, and Active Plugins', $this->localization_domain ),
						                        'posts'    => __( 'Posts', $this->localization_domain ) . ' <a href="#" id="select-category-link" class="button-secondary">' . __( 'Select categories', $this->localization_domain ) . ' &#x25BC;</a>',
						                        'pages'    => __( 'Pages', $this->localization_domain ),
						                        'terms'    => __( 'Categories, Tags, and Links', $this->localization_domain ),
						                        'users'    => __( 'Users', $this->localization_domain ),
						                        'menus'    => __( 'Menus', $this->localization_domain ),
						                        'files'    => __( 'Files', $this->localization_domain )
						                        
						                    );

						                    foreach ( $options_to_copy as $key => $value ) : ?>
						                            <input type="checkbox" name="to_copy[]" id="nbt-<?php echo $key; ?>" value="<?php echo $key; ?>" <?php checked( in_array( $key, $template['to_copy'] ) ); ?>> <label for='nbt-<?php echo $key; ?>' id="nbt-label-<?php echo $key; ?>"><?php echo $value; ?></label><br/>
						                            <?php if ( 'posts' === $key ) :   ?>
						                            		<div id="poststuff" style="width:280px;margin-left:25px">
							                            		<div id="categorydiv" class="postbox ">
																	<h3 class="hndle"><span>Categories</span></h3>
																	<div class="inside">
																		<div id="taxonomy-category" class="categorydiv">

																			<div id="category-all" class="tabs-panel">
																				<ul id="categorychecklist" data-wp-lists="list:category" class="categorychecklist form-no-clear">
																					<li id="all-categories"><label class="selectit"><input value="all-categories" type="checkbox" <?php checked( in_array( 'all-categories', $template['post_category'] ) ); ?> name="post_category[]" id="in-all-categories"> <strong><?php _e( 'All categories', $this->localization_domain ); ?></strong></label></li>
																					<?php switch_to_blog( $template['blog_id'] ); ?>
																					<?php wp_terms_checklist( 0, array( 'selected_cats' => $template['post_category'] ) ); ?>
																					<?php restore_current_blog(); ?>
																				</ul>
																			</div>
																					
																		</div>
																	</div>
																</div>
															</div>
						                        	<?php endif; ?>
						                  	<?php endforeach; ?>
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

					                    <?php 
					                    	ob_start();

					                    	if ( empty( $template['screenshot'] ) )
					                    		$img = nbt_get_default_screenshot_url($template['blog_id']);
					                    	else
					                    		$img = $template['screenshot'];

										?>
											<img src="<?php echo $img; ?>" style="max-width:100%;"/><br/>
											<p>
												<label for="screenshot">
													<?php _e( 'Upload new screenshot', $this->localization_domain ); ?> 
													<input type="file" name="screenshot">
												</label>
												<?php submit_button( __( 'Reset screenshot', $this->localization_domain ), 'secondary', 'reset-screenshot', true ); ?>
											</p>
					                    <?php $this->render_row( __( 'Screenshot', $this->localization_domain ), ob_get_clean() ); ?>
									</table>
				                    <?php
						                global $wpdb;

						                switch_to_blog($template['blog_id']);
						            ?>
						            <br/><br/>
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
						                                //if ( in_array( $result[0], $template['additional_tables']'] ) )
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
							            <?php restore_current_blog(); ?>
					                	
					            	</table>
					            	
					            </div>

					            <?php 
					            	$model = blog_templates_model::get_instance();
					            	$categories = $model->get_templates_categories(); 
					            	$template_categories_tmp = $model->get_template_categories( $t );

					            	$template_categories = array();
					            	foreach ( $template_categories_tmp as $row ) {
					            		$template_categories[] = absint( $row['ID'] );
					            	}
					            ?>
					            <div id="postbox-container-1" class="postbox-container">
									<div id="side-sortables" class="meta-box-sortables ui-sortable">
										<div id="categorydiv" class="postbox ">
											<div class="handlediv" title=""><br></div><h3 class="hndle"><span><?php _e( 'Template categories' ); ?></span></h3>
												<div class="inside">
													<div id="taxonomy-category" class="categorydiv">
														<div id="category-all" class="tabs-panel">
															<ul id="templatecategorychecklist" class="categorychecklist form-no-clear">
																<?php foreach ( $categories as $category ): ?>
																	<li id="template-cat-<?php echo $category['ID']; ?>"><label class="selectit"><input value="<?php echo $category['ID']; ?>" <?php checked( in_array( $category['ID'], $template_categories ) ); ?> type="checkbox" name="template_category[]"> <?php echo $category['name']; ?></label></li>
																<?php endforeach; ?>
															</ul>
														</div>
													</div>
												</div>
											</div>
										</div>			
									</div>
								</div>
					        </div>
			            </div>
			            <div class="clear"></div>
			            <?php submit_button( __( 'Save template', $this->localization_domain ), 'primary', 'save_updated_template' ); ?>		            
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
            if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->menu_slug )
                return;

            $model = blog_templates_model::get_instance();

            $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

            $save_template = ( ! empty( $_POST['reset-screenshot'] ) || ! empty( $_POST['save_updated_template'] ) );
            if( $save_template ) {

                if (! wp_verify_nonce($_POST['_nbtnonce'], 'blog_templates-update-options') )
                    die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );
                

                $args = array( 
	                'name' => stripslashes($_POST['template_name'] ),
	                'description' => stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) ),
	                'to_copy' => isset( $_POST['to_copy'] ) ? (array)$_POST['to_copy'] : array(),
	                'additional_tables' => isset( $_POST['additional_template_tables'] ) ? $_POST['additional_template_tables'] : array(),
	                'copy_status' => isset( $_POST['copy_status'] ) ? true : false,
	                'block_posts_pages' => isset( $_POST['block_posts_pages'] ) ? true : false,
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

                if ( ! isset( $_POST['post_category'] ) ) {
                	$post_category = array( 'all-categories' );
                }
                else {
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

                if ( ! isset( $_POST['template_category'] ) ) {
                	$template_category = array( $model->get_default_category_id() );
                }
                else {
                	$categories = $_POST['template_category'];

                	$template_category = array();
            		foreach( $categories as $category ) {
            			if ( ! is_numeric( $category ) )
            				continue;

            			$template_category[] = absint( $category );
            		}
                }

                $model->update_template_categories( $t, $template_category );

                $model->update_template( $t, $args );

                $this->updated_message =  __( 'Your changes were sucessfully saved!', $this->localization_domain );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif( !empty( $_POST['save_new_template'] ) ) {
                if ( ! wp_verify_nonce( $_POST['_nbtnonce'], 'blog_templates-update-options' ) )
                    wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                if ( ! get_blog_details( (int) $_POST['copy_blog_id'] ) )
                    wp_die( __( 'Whoops! The blog ID you posted is incorrect. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                if ( is_main_site( (int) $_POST['copy_blog_id'] ) )
                    wp_die( __( 'Whoops! The blog ID you posted is incorrect. You cannot template the main site. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                $name = ( ! empty( $_POST['template_name'] ) ? stripslashes( $_POST['template_name'] ) : __( 'A template', $this->localization_domain ) );
                $description = ( ! empty( $_POST['template_description'] ) ? stripslashes( preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $_POST['template_description'] ) ) : '' );
                $blog_id = (int)$_POST['copy_blog_id'];

                $settings = array(
                    'to_copy' => array(),
                    'post_category' => array( 'all-categories' ),
                    'copy_status' => false,
                    'block_posts_pages' => false
                );

                $template_id = $model->add_template( $blog_id, $name, $description, $settings );

                $to_url = add_query_arg(
                	array(
                		'page' => $this->menu_slug,
                		't' => $template_id
                	),
                	network_admin_url( 'admin.php' )
                );
                wp_redirect( $to_url );

            } elseif( isset( $_GET['remove_default'] ) ) {

                if ( ! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-remove_default') )
                    wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );
               	
               	$model->remove_default_template();

               	$this->options['default'] = '';
               	$this->save_admin_options();

                $this->updated_message = __( 'The default template was successfully turned off.', $this->localization_domain );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {

                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-make_default') )
                    wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

				$default_updated = $model->set_default_template( absint( $_GET['default'] ) );

				if ( $default_updated ) {
					$this->options['default'] = $_GET['default'];
	               	$this->save_admin_options();
	            }

                $this->updated_message =  __( 'The default template was sucessfully updated.', $this->localization_domain );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );

            } elseif ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) {

                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-delete_template') )
                    wp_die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)', $this->localization_domain ) );

                $model->delete_template( absint( $_GET['d'] ) );

                $this->updated_message =  __( 'Success! The template was sucessfully deleted.', $this->localization_domain );
                add_action( 'network_admin_notices', array( &$this, 'show_admin_notice' ) );
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