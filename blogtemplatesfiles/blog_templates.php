<?php

//require_once('includes/array_replace_recursive.php');

if (!class_exists('blog_templates')) {
    class blog_templates {
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /**
        * @var string The options string name for this plugin
        */
        var $optionsName = 'blog_templates_options';
        
        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "blog_templates";
        
        /**
        * @var string $pluginurl The path to this plugin
        */ 
        var $thispluginurl = '';
        /**
        * @var string $pluginurlpath The path to this plugin
        */
        var $thispluginpath = '';
            
        /**
        * @var array $options Stores the options for this plugin
        */
        var $options = array();
        
        //Class Functions
        /**
        * PHP 4 Compatible Constructor
        */
        function blog_templates(){$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct(){
            //Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup
            if (defined('WPMU_PLUGIN_DIR') && strpos(__FILE__,WPMU_PLUGIN_DIR) === false) { //We're not in the WPMU Plugin Directory
                $this->thispluginpath = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
                $this->thispluginurl = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            } else { //We are in the WPMU Plugin Directory
                $this->thispluginurl = WPMU_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
                $this->thispluginurl = WPMU_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            }
            
            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();

            /*echo '<pre>';
            print_r($this->options['templates']);
            echo '</pre>';*/
            
            //Actions        
            add_action("admin_menu", array(&$this,"admin_menu_link"));
            add_action('wpmu_new_blog', array(&$this, 'set_blog_defaults'), 999, 2); // Set to 999 so this runs after every other action
            
            add_action('admin_footer', array(&$this,'add_template_dd'));
            
            wp_enqueue_script('jquery');
            
            if (isset($_GET['t'])) {
                //Add the JS to initiate the tabs
                //add_action("admin_head", array(&$this,"admin_head"));
                
                //wp_enqueue_script('jquery-ui-tabs');
                //The jQuery UI CSS is required for the tabs
                //wp_enqueue_style( 'jquery-custom-ui-tabs', $this->thispluginurl.'css/jquery.ui.tabs.css');
            }
            
            //Filters
            /*
            add_filter('the_content', array(&$this, 'filter_content'), 0);
            */
        }
        
        /**
        * @desc Adds the necessary JS and CSS to the admin header for the easy admin area
        */
        function admin_head() {
            /*?>
            <script type="text/javascript">
                jQuery(document).ready(function(){
                    var anchor = jQuery(document).attr('location').hash; // the anchor in the URL
                    var index = jQuery('#blog_template_tabs li a').index(jQuery(anchor)); // in tab index of the anchor in the URL
                    if (index < 0) { index = 0; }
                    jQuery('#blog_template_tabs').tabs({ 'selected': index }); // select the tab
                    jQuery( 'html, body' ).animate( { scrollTop: 0 }, 0 ); //Scroll to the top if necessary

                    jQuery('#blog_template_tabs').bind('tabsshow', function(event, ui) { // change the url anchor when we click on a tab
                        var scrollto = window.pageYOffset;
                        document.location.hash = jQuery('#blog_template_tabs li a[href="#' + ui.panel.id + '"]').attr('id');
                        jQuery( 'html, body' ).animate( { scrollTop: scrollto }, 0 );
                        jQuery('form').attr('action',document.location); //Update the form's location so we return to this tab when we save the form
                    });
                });
            </script>
            <?php*/
        }
        
        function get_template_dropdown($tag_name, $include_none) {
            $templates = array();
            foreach ($this->options['templates'] as $key=>$template) {
                $templates[$key] = $template['name'];
            }
            
            if ($templates != array()) {
                echo "<select name=\"$tag_name\">";
                echo "<option value=\"\">None</option>";
                foreach ($templates as $key=>$value) {
                    echo "<option value=\"$key\">$value</option>";
                }
                echo '</select>';
            }
        }
        
        /**
        * Adds the Template dropdown to the WPMU New Blog form
        */
        function add_template_dd() {
            global $pagenow;
            if ($pagenow != 'wpmu-blogs.php') return;
            
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('.form-table:last tr:last').before('\
                    <tr class="form-field form-required">\
                        <th style="text-align:center;" scope="row"><?php _e('Template', $this->localizationDomain) ?></th>\
                        <td><?php $this->get_template_dropdown('blog_template',true); ?></td>\
                    </tr>');
                });
            </script>
            <?php
        }

        function set_blog_defaults($blog_id, $user_id) {
            //Check if the user chose a template, and if that template exists
            if (isset($_POST['blog_template']) && $this->options['templates'][$_POST['blog_template']]) {
                global $wpdb;
                
                $template = $this->options['templates'][$_POST['blog_template']];
                
                /*echo 'Template: ' . $_POST['blog_template'] . '<pre>';
                print_r($template);
                echo '</pre>';*/
                
                //Begin the transaction
                $wpdb->query("BEGIN;");
                
                switch_to_blog($blog_id); //Switch to the blog that was just created
                
                foreach($template['to_copy'] as $value) {
                    //echo "Case: $value<br/>";
                    switch ($value) {
                        case 'settings':
                            //We can't use the helper functions here, because we need to save some of the settings
                            
                            //Delete the current options, except blog-specific options
                            $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt'");
                            
                            if (!$wpdb->last_error) { //No error. Good! Now copy over the old settings
                                //Switch to the template blog, then grab the settings/plugins/templates values from the template blog
                                switch_to_blog($template['blog_id']);
                                $templated = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt'");
                                restore_current_blog(); //Switch back to the newly created blog
                                
                                //Now, insert the templated settings into the newly created blog
                                foreach ($templated as $row) {
                                    //Make sure none of the options are using wp_X_ convention, and if they are, replace the value with the new blog ID
                                    $row->option_name = preg_replace('/wp_[0-9]*_/i', "wp_{$blog_id}_",$row->option_name);
                                    $row->option_value = preg_replace('/wp_[0-9]*_/i', "wp_{$blog_id}_",$row->option_value);

                                    //To prevent duplicate entry errors, since we're not deleting ALL of the options, there could be an ID collision
                                    unset($row->option_id);
                                    
                                    //Insert the row
                                    $wpdb->insert($wpdb->options,(array) $row);
                                    //Check for errors
                                    if (!empty($wpdb->last_error)) {
                                        $error = '<div id="message" class="error"><p>Insertion Error: ' . $wpdb->last_error . ' - The template was not applied</p></div>';
                                        $wpdb->query("ROLLBACK;");
                                        
                                        //We've rolled it back and thrown an error, we're done here
                                        restore_current_blog();
                                        wp_die($error);
                                    }
                                }
                            } else {
                                $error = '<div id="message" class="error"><p>Deletion Error: ' . $wpdb->last_error . ' - The template was not applied</p></div>';
                                $wpdb->query("ROLLBACK;");
                                restore_current_blog(); //Switch back to our current blog
                                wp_die($error);
                            }       
                        break;
                        case 'posts':
                            $this->clear_table($wpdb->posts);
                            $this->copy_table($template['blog_id'],"posts");
                            $this->clear_table($wpdb->postmeta);
                            $this->copy_table($template['blog_id'],"postmeta");
                        break;
                        case 'terms':
                            $this->clear_table($wpdb->links);
                            $this->copy_table($template['blog_id'],"links");
                            
                            $this->clear_table($wpdb->terms);
                            $this->copy_table($template['blog_id'],"terms");
                            
                            $this->clear_table($wpdb->term_relationships);
                            $this->copy_table($template['blog_id'],"term_relationships");
                            
                            $this->clear_table($wpdb->term_taxonomy);
                            $this->copy_table($template['blog_id'],"term_taxonomy");
                        break;
                        case 'users':
                            //Copy over the users to this blog
                            $users = $wpdb->get_results("SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE 'wp\_{$template['blog_id']}\_%'");
                            if (empty($users)) continue; //If there are no users to copy, just leave. We don't want to leave this blog without any users
                            
                            //Delete the auto user from the blog, to prevent duplicates or erroneous users
                            $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'wp\_$blog_id\_%'");
                            if (!empty($wpdb->last_error)) {
                                $error = '<div id="message" class="error"><p>Deletion Error: ' . $wpdb->last_error . ' - The template was not applied</p></div>';
                                $wpdb->query("ROLLBACK;");
                                
                                //We've rolled it back and thrown an error, we're done here
                                restore_current_blog();
                                wp_die($error);
                            }                           
                            
                            //Now, insert the templated settings into the newly created blog
                            foreach ($users as $user) {
                                //###Could add logic here to check if the user email entered via the New Blog form has been added, and if not, add them after the foreach loop...
                                //echo 'Adding User';
                                $user->meta_key = preg_replace('/wp_[0-9]*_/i', "wp_{$blog_id}_",$user->meta_key);
                                unset($user->umeta_id); //Remove the umeta_id field, let it autoincrement
                                //Insert the user
                                $wpdb->insert($wpdb->usermeta,(array) $user);
                                //Check for errors
                                if (!empty($wpdb->last_error)) {
                                    $error = '<div id="message" class="error"><p>Insertion Error: ' . $wpdb->last_error . ' - The template was not applied</p></div>';
                                    $wpdb->query("ROLLBACK;");
                                    
                                    //We've rolled it back and thrown an error, we're done here
                                    restore_current_blog();
                                    wp_die($error);
                                }
                            }
                        break;
                    }
                }
                

                $wpdb->query("COMMIT;"); //If we get here, everything's fine. Commit the transaction                
                
                restore_current_blog(); //Switch back to our current blog            
            }
        }
        
        /**
        * Added to automate comparing the two tables, and making sure no old fields that have been removed get copied to the new table
        * 
        * @param mixed $new_table_name
        * @param mixed $old_table_row
        */
        function get_fields_to_remove($new_table_name, $old_table_row) {
            //make sure we have something to compare it to
            if (empty($old_table_row)) return false;
            
            //We need the old table row to be in array format, so we can use in_array()
            $old_table_row = (array)$old_table_row;
            
            global $wpdb;
            
            //Get the new table structure
            $new_table = (array)$wpdb->get_results("SHOW COLUMNS FROM {$wpdb->$new_table_name}");
            
            $new_fields = array();
            foreach($new_table as $row) {
                $new_fields[] = $row->Field;
            }
            
            /*echo "SHOW COLUMNS FROM {$wpdb->$new_table_name}" . '<pre>';
            print_r($new_fields);
            echo '</pre>';*/
            
            $results = array();
            
            //Now, go through the columns in the old table, and check if there are any that don't show up in the new table
            foreach ($old_table_row as $key=>$value) {
                //echo "Key: $key -- Value: $value";
                //echo "<br/>";
                if (!in_array($key,$new_fields)) { //If the new table doesn't have this field
                    //echo "In array<br/>";
                    //There's a column that isn't in the new one, make note of that
                    $results[] = $key;
                }
            }
            
            /*echo '<pre>';
            print_r($results);
            echo '</pre>';*/
            
            //Return the results array, which should contain all of the fields that don't appear in the new table
            return $results;
        }
        
        function copy_table($templated_blog_id, $table) {
            global $wpdb;
            //echo "copying {$wpdb->$table} from $templated_blog_id<br/>SQL: SELECT * FROM $table<br/>";
            
            
            //Switch to the template blog, then grab the values
            switch_to_blog($templated_blog_id);
            $templated = $wpdb->get_results("SELECT * FROM {$wpdb->$table}");
            restore_current_blog(); //Switch back to the newly created blog
            
            /*echo '<pre>';
            print_r($templated);
            echo '</pre>';*/
            
            if (count($templated))
                $to_remove = $this->get_fields_to_remove($table, $templated[0]);
            
            /*echo '<pre>To Remove: ';
            print_r($to_remove);
            echo '</pre>';*/
            
            
            
            //Now, insert the templated settings into the newly created blog
            foreach ($templated as $row) {
                $row = (array)$row;
                /*echo '<pre>Row: ';
                print_r($row);
                echo '</pre>';*/
                foreach ($row as $key=>$value) {
                    if (in_array($key,$to_remove))
                        unset($row[$key]);
                }
                //echo 'Copying a Row<br/>';
                $wpdb->insert($wpdb->$table,$row);
                if (!empty($wpdb->last_error)) {
                    $error = '<div id="message" class="error"><p>Insertion Error: ' . $wpdb->last_error . ' - The template was not applied</p></div>';
                    $wpdb->query("ROLLBACK;");
                    
                    //We've rolled it back and thrown an error, we're done here
                    restore_current_blog();
                    wp_die($error);
                }
            }
        }
        
        /**
        * Deletes everything from a table
        * 
        * @param string $table The name of the table to clear
        */
        function clear_table($table) {
            global $wpdb;
            //Delete the current categories
            $wpdb->query("DELETE FROM $table");
            
            if ($wpdb->last_error) { //No error. Good! Now copy over the terms from the templated blog
                $error = '<div id="message" class="error"><p>Deletion Error: ' . $wpdb->last_error . ' - The template was not applied</p></div>';
                $wpdb->query("ROLLBACK;");
                restore_current_blog(); //Switch back to our current blog
                wp_die($error);
            }
        }
                
        /**
        * @desc Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_site_option($this->optionsName)) {
                $theOptions = array('templates'=>array());
                update_site_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        
        /**
        * @desc Saves the admin options to the database.
        */
        function saveAdminOptions(){
            return update_site_option($this->optionsName, $this->options);
        }
        
        /**
        * @desc Adds the options subpanel
        */
        function admin_menu_link() {
            //If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
            //reflect the page filename (ie - options-general.php) of the page your plugin is under!
            if (is_site_admin()) {
                //add_submenu_page( 'wpmu-admin.php', 'Generate Blog Template', 'Generate Blog Template', 'administrator', basename(__FILE__) . '_create', array(&$this,'admin_options_page_create_template'));
                add_submenu_page( 'wpmu-admin.php', 'Blog Templates', 'Blog Templates', 'administrator', basename(__FILE__), array(&$this,'admin_options_page'));
                add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
            }
        }
        
        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) {
           //If your plugin is under a different top-level menu than Settiongs (IE - you changed the function above to something other than add_options_page)
           //Then you're going to want to change options-general.php below to the name of your top-level page
           $settings_link = '<a href="wpmu-admin.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
           array_unshift( $links, $settings_link ); // add before other links

           return $links;
        }
        
        /**
        * Returns the WP Options table for this blog as a data array so we can use/save it easily
        */
        function get_wp_options_as_array() {
            global $wpdb;
            $wp_options = array();
            $results = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` NOT LIKE '_transient%' AND `option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt'");
            foreach ($results as $row) {
                $wp_options[$row->option_name] = array('blog_id'=>$row->blog_id,'option_value'=>$row->option_value,'autoload'=>$row->autoload);
            }
            return $wp_options;
        }
        
        /**
        * Adds settings/options page
        */
        function admin_options_page() {
            $t = $_GET['t'];

            if($_POST['save']){
                if (! wp_verify_nonce($_POST['_wpnonce'], 'blog_templates-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
                $this->options['templates'][$t]['name'] = $_POST['template_name'];
                $this->options['templates'][$t]['to_copy'] = $_POST['to_copy'];
                
                $this->saveAdminOptions();
                
                echo '<div class="updated fade"><p>Success! Your changes were sucessfully saved!</p></div>';
            } elseif ($_POST['save_new_template']) {
                global $wpdb;
                if (! wp_verify_nonce($_POST['_wpnonce'], 'blog_templates-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
                $this->options['templates'][] = array('name'=>$_POST['template_name'],'blog_id'=>$wpdb->blogid,'to_copy' => (array)$_POST['to_copy']);
                
                //$this->options['templates'][count($this->options['templates'])-1]['wp_options'] = $this->get_wp_options_as_array();

                $this->saveAdminOptions();
                
                echo '<div class="updated fade"><p>Success! Your changes were sucessfully saved!</p></div>';
            } elseif (isset($_GET['d'])) {
                $d = $_GET['d'];
                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-delete_template') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
                unset($this->options['templates'][$d]);
                
                $this->saveAdminOptions();
                
                echo '<div class="updated fade"><p>Success! The template was sucessfully deleted.</p></div>';
            }
            
            global $pagenow; 
            $url = $pagenow . '?page=blog_templates.php';            
?>                                   
<div class="wrap">
    <form method="post" id="options">
    <?php wp_nonce_field('blog_templates-update-options'); ?>
        <?php if (!is_numeric($t)) { ?>
            <h2>Blog Templates</h2>
            <table width="100%" cellspacing="2" cellpadding="5" class="widefat fixed">
                <thead>
                <tr><th>Template Name</th><th>Blog</th><th>Actions</th></tr>
                </thead>
                <tfoot>
                <tr><th>Template Name</th><th>Blog</th><th>Actions</th></tr>
                </tfoot>
                <?php foreach ($this->options['templates'] as $key => $template) { ?>
                <tr valign="top"> 
                    <td><a href="<?php echo $url . '&t=' . $key ?>"><?php echo $template['name'] ?></a></td>
                    <td><b><?php echo get_blog_option($template['blog_id'],'blogname'); ?></b> <a href="<?php echo get_blog_option($template['blog_id'],'siteurl'); ?>/wp-admin">[Dashboard]</a></td>
                    <td><a href="<?php echo wp_nonce_url($url . '&d=' . $key,"blog_templates-delete_template")?>">[Delete]</a></td>
                </tr>
                <?php } ?>
            </table>
            <h2>Create New Blog Template</h2>
            <p>Create a blog template based on the current blog! This allows you (and other admins) to copy all of the current blog's settings and allow you to create other blogs
            that are almost exact copies of this blog. (Blog name, URL, etc will change, so it's not a 100% copy)</p>
            <p>Simply fill out the form below and click "Create Blog Template!" to generate the template for later use!</p>
            <table width="100%" cellspacing="2" cellpadding="5" class="widefat fixed"> 
                <tr valign="top"> 
                    <th width="33%" scope="row"><?php _e('Template Name:', $this->localizationDomain); ?></th> 
                    <td><input name="template_name" type="text" id="template_name" size="45"/>
                </td> 
                </tr>
                <tr>
                    <th>
                        Using this blog:
                    </th>
                    <th>
                        <?php bloginfo('wpurl'); ?>
                    </th>
                </tr>
                <tr valign="top"> 
                    <th width="33%"><?php _e('What To Copy To New Blog?', $this->localizationDomain); ?></th>
                    <td>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="settings" value="settings"><label for="settings"><?php _e('Wordpress Settings, Current Theme, and Active Plugins', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="posts" value="posts"><label for="posts"><?php _e('Posts and Pages', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="terms" value="terms"><label for="categories"><?php _e('Categories, Tags, and Links', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="users" value="users"><label for="users"><?php _e('Users', $this->localizationDomain); ?></label></span><br/>
                    </td>
                </tr>
                <tr>
                    <td colspan=2><p>Please note that this will turn the blog you are currently logged in
                        to - <?php bloginfo('wpurl'); ?> - into a template blog. Any changes you make to this blog will change the template, as well! We recommend creating specific "Template Blogs" for this purpose, so you don't accidentally add new settings, content, or users that you don't want in your template.</p>
                        <p>This means that if you would like to create a dedicated template blog for this template, please
                        <a href="http://test.besttrainingcourse.com/wp-admin/wpmu-blogs.php">create a new blog</a> and then visit this page when you are
                        logged in to the backend of that blog to create the template.</p>
                    </td>
                </tr>
            </table>
            <p><div class="submit"><input type="submit" name="save_new_template" class="button-primary" value="Create Blog Template!" /></div></p>
        <?php
            } else { 
                $template = $this->options['templates'][$t];
        ?>
            <p><a href="<?php echo $url; ?>">&laquo; Back to Blog Templates</a></p>
            <h2>Edit Blog Template</h2>
            <table width="100%" cellspacing="2" cellpadding="5" class="widefat fixed"> 
                <tr valign="top"> 
                    <th width="33%"><?php _e('Template Name:', $this->localizationDomain); ?></th> 
                    <td><input name="template_name" type="text" id="template_name" size="45" value="<?php echo $template['name'];?>"/>
                </td> 
                </tr>
                <tr valign="top"> 
                    <th width="33%"><?php _e('What To Copy To New Blog?', $this->localizationDomain); ?></th>
                    <td>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="settings" value="settings" <?php echo (in_array('settings',$template['to_copy']))?'checked="checked"':'';?>><label for="settings"><?php _e('Wordpress Settings, Current Theme, and Active Plugins', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="posts" value="posts" <?php echo (in_array('posts',$template['to_copy']))?'checked="checked"':''?>><label for="posts"><?php _e('Posts and Pages', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="terms" value="terms" <?php echo (in_array('terms',$template['to_copy']))?'checked="checked"':''?>><label for="categories"><?php _e('Categories, Tags, and Links', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="users" value="users" <?php echo (in_array('users',$template['to_copy']))?'checked="checked"':''?>><label for="users"><?php _e('Users', $this->localizationDomain); ?></label></span><br/>
                    </td>
                </tr>
                <tr>
                    <th colspan=2><input type="submit" name="save" value="Save &raquo;" /></th>
                </tr>
            </table
        <?php } ?>
        </table>
    </form>
<?php
        }
    } //End Class
    //instantiate the class
    $blog_templates_var = new blog_templates();
} //End if blog_templates class exists statement
?>