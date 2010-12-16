<?php

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

        var $currenturl_with_querystring;

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


            //"Constants" setup
            if (defined('WPMU_PLUGIN_DIR') && strpos(__FILE__,WPMU_PLUGIN_DIR) === false) { //We're not in the WPMU Plugin Directory
                $this->thispluginpath = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
                $this->thispluginurl = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
                //Language Setup
                load_plugin_textdomain($this->localizationDomain, false, dirname(plugin_basename(__FILE__))."/languages/");
            } else { //We are in the WPMU Plugin Directory
                $this->thispluginpath = WPMU_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
                $this->thispluginurl = WPMU_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
                //Language Setup
                load_muplugin_textdomain($this->localizationDomain, "/blogtemplatesfiles/languages/");
            }

            $this->currenturl_with_querystring = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();

            /*echo '<pre>';
            print_r($this->options['templates']);
            echo '</pre>';*/

            //Actions
            add_action("admin_menu", array(&$this,"admin_menu_link"));
            add_action('wpmu_new_blog', array(&$this, 'wpmu_new_blog'), 999, 2); // Set to 999 so this runs after every other action
            add_action('admin_notices', array(&$this,'admin_options_page_posted')); //Catch the admin options page postback

            add_action('admin_footer', array(&$this,'add_template_dd'));

            wp_enqueue_script('jquery');

            //Filters
            /*
            add_filter('the_content', array(&$this, 'filter_content'), 0);
            */
        }

        function get_template_dropdown($tag_name, $include_none) {
            $templates = array();
            foreach ($this->options['templates'] as $key=>$template) {
                $templates[$key] = $template['name'];
            }

            if ($templates != array()) {
                echo "<select name=\"$tag_name\">";
                echo "<option value=\"none\">None</option>";
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

			if( 'wpmu-blogs.php' !== $pagenow || 'ms-sites.php' !== $pagenow || isset( $_GET['action'] ) && $_GET['action'] !== 'list' )
				return;

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

        /**
        * Catch the wpmu_new_blog action
        *
        * @param mixed $blog_id
        * @param mixed $user_id
        */
        function wpmu_new_blog($blog_id,$user_id) {
            $this->set_blog_defaults($blog_id,$user_id);
        }

        /**
        * Checks for a template to use, and if it exists, copies the templated settings to the new blog
        *
        * @param mixed $blog_id
        * @param mixed $user_id
        */
        function set_blog_defaults($blog_id, $user_id) {
            global $wpdb;

            $template = '';
            if ( isset( $_POST['blog_template'] ) && is_numeric( $_POST['blog_template'] ) ) { //If they've chosen a template, use that. For some reason, when PHP gets 0 as a posted var, it doesn't recognize it as is_numeric, so test for that specifically
                $template = $this->options['templates'][$_POST['blog_template']];
            } elseif ( isset( $_POST['blog_template'] ) && $_POST['blog_template'] == 'none' ) {
                return; //The user doesn't want to use a template
            } elseif ( isset( $this->options['default'] ) && is_numeric( $this->options['default'] ) ) { //If they haven't chosen a template, use the default if it exists
                $template = $this->options['templates'][$this->options['default']];
            }

            if (empty($template)) return; //No template, lets leave

            //Begin the transaction
            $wpdb->query("BEGIN;");


            switch_to_blog($blog_id); //Switch to the blog that was just created

            //Get the prefixes, so we don't have to worry about regex, or changes to WP's naming conventions
            $new_prefix = $wpdb->prefix;

            //Don't forget to get the template blog's prefix
            switch_to_blog($template['blog_id']);
            $template_prefix = $wpdb->prefix;

            //Now, go back to the new blog that was just created
            restore_current_blog();

            foreach($template['to_copy'] as $value) {
                switch ($value) {
                    case 'settings':
                        //We can't use the helper functions here, because we need to save some of the settings

                        $exclude_settings = apply_filters( 'blog_template_exclude_settings', "`option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'new_admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt'" );

                        //Delete the current options, except blog-specific options
                        $wpdb->query("DELETE FROM $wpdb->options WHERE $exclude_settings");

                        if (!$wpdb->last_error) { //No error. Good! Now copy over the old settings
                            //Switch to the template blog, then grab the settings/plugins/templates values from the template blog
                            switch_to_blog($template['blog_id']);

                            $templated = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE $exclude_settings");
                            restore_current_blog(); //Switch back to the newly created blog

                            //Now, insert the templated settings into the newly created blog
                            foreach ($templated as $row) {
                                //Make sure none of the options are using wp_X_ convention, and if they are, replace the value with the new blog ID
                                $row->option_name = str_replace($template_prefix, $new_prefix,$row->option_name);
                                $row->option_value = str_replace($template_prefix, $new_prefix,$row->option_value);

                                //To prevent duplicate entry errors, since we're not deleting ALL of the options, there could be an ID collision
                                unset($row->option_id);

                                //Insert the row
                                $wpdb->insert($wpdb->options,(array) $row);
                                //Check for errors
                                if (!empty($wpdb->last_error)) {
                                    $error = '<div id="message" class="error"><p>Insertion Error: ' . $wpdb->last_error . ' - The template was not applied. (New Blog Templates - While inserting templated settings)</p></div>';
                                    $wpdb->query("ROLLBACK;");

                                    //We've rolled it back and thrown an error, we're done here
                                    restore_current_blog();
                                    wp_die($error);
                                }
                            }
                        } else {
                            $error = '<div id="message" class="error"><p>Deletion Error: ' . $wpdb->last_error . ' - The template was not applied. (New Blog Templates - While removing auto-generated settings)</p></div>';
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
                        $users = $wpdb->get_results("SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE '" . mysql_escape_string($template_prefix) . "%'");
                        if (empty($users)) continue; //If there are no users to copy, just leave. We don't want to leave this blog without any users

                        //Delete the auto user from the blog, to prevent duplicates or erroneous users
                        $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '" . mysql_escape_string($new_prefix) . "%'");
                        if (!empty($wpdb->last_error)) {
                            $error = '<div id="message" class="error"><p>Deletion Error: ' . $wpdb->last_error . ' - The template was not applied. (New Blog Templates - While removing auto-generated users)</p></div>';
                            $wpdb->query("ROLLBACK;");

                            //We've rolled it back and thrown an error, we're done here
                            restore_current_blog();
                            wp_die($error);
                        }

                        //Now, insert the templated settings into the newly created blog
                        foreach ($users as $user) {
                            //###Could add logic here to check if the user email entered via the New Blog form has been added, and if not, add them after the foreach loop...
                            //echo 'Adding User';
                            $user->meta_key = str_replace($template_prefix, $new_prefix,$user->meta_key);
                            unset($user->umeta_id); //Remove the umeta_id field, let it autoincrement
                            //Insert the user
                            $wpdb->insert($wpdb->usermeta,(array) $user);
                            //Check for errors
                            if (!empty($wpdb->last_error)) {
                                $error = '<div id="message" class="error"><p>Insertion Error: ' . $wpdb->last_error . ' - The template was not applied. (New Blog Templates - While adding templated users)</p></div>';
                                $wpdb->query("ROLLBACK;");

                                //We've rolled it back and thrown an error, we're done here
                                restore_current_blog();
                                wp_die($error);
                            }
                        }
                    break;
                }
            }

            //Are there any additional tables we need to copy?
            /*error_log('Begin Additional Tables code');
            echo 'Before additional tables code<br/>';*/
            if (is_array($template['additional_tables'])) {
                //echo 'is array<br/>';
                foreach ($template['additional_tables'] as $add) {
                    $add = mysql_escape_string($add); //Just in case

                    $result = $wpdb->get_results("SHOW TABLES LIKE '" . str_replace($template_prefix,$new_prefix,$add) . "'", ARRAY_N);
                    if (!empty($result)) { //Is the table present? Clear it, then copy
                        //echo ("table exists: $add<br/>");
                        $this->clear_table($add);
                        //Copy the DB
                        $this->copy_table($template['blog_id'],str_replace($template_prefix,'',$add));
                    } else { //The table's not present, add it and copy the data from the old one
                        //echo ('table doesn\'t exist<br/>');
                        $wpdb->query("CREATE TABLE " . str_replace($template_prefix,$new_prefix,$add) . " LIKE $add");
                        $wpdb->query("INSERT " . str_replace($template_prefix,$new_prefix,$add) . " SELECT * FROM $add");
                        if (!empty($wpdb->last_error)) {
                            $error = '<div id="message" class="error"><p>Insertion Error: ' . $wpdb->last_error . ' - The template was not applied. (New Blog Templates - With CREATE TABLE query for Additional Tables)</p></div>';
                            $wpdb->query("ROLLBACK;");

                            //We've rolled it back and thrown an error, we're done here
                            restore_current_blog();
                            wp_die($error);
                        }
                    }
                }
            }

            //error_log('Finished Successfully');

            $wpdb->query("COMMIT;"); //If we get here, everything's fine. Commit the transaction

            restore_current_blog(); //Switch back to our current blog
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
                /*
                echo '<pre>Row: ';
                print_r($row);
                echo '</pre>';//*/
                foreach ($row as $key=>$value) {
                    if (in_array($key,$to_remove))
                        unset($row[$key]);
                }
                      /*
                echo '<pre>Row: ';
                print_r($row);
                echo '</pre>';//*/
                //echo 'Copying a Row<br/>';
                $wpdb->insert($wpdb->$table,$row);
                if (!empty($wpdb->last_error)) {
                    $error = '<div id="message" class="error"><p>Insertion Error: ' . $wpdb->last_error . ' - The template was not applied. (New Blog Templates - While copying ' . $table . ')</p></div>';
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
                $error = '<div id="message" class="error"><p>Deletion Error: ' . $wpdb->last_error . ' - The template was not applied. (New Blog Templates - While clearing ' . $table . ')</p></div>';
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
            if (get_bloginfo('version') >= 3)
                add_submenu_page( 'ms-admin.php', 'Blog Templates', 'Blog Templates', 'administrator', basename(__FILE__), array(&$this,'admin_options_page'));
            else
                add_submenu_page( 'wpmu-admin.php', 'Blog Templates', 'Blog Templates', 'administrator', basename(__FILE__), array(&$this,'admin_options_page'));
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
        }

        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) {
            //If your plugin is under a different top-level menu than Settiongs (IE - you changed the function above to something other than add_options_page)
            //Then you're going to want to change options-general.php below to the name of your top-level page
            if (get_bloginfo('version') >= 3)
                $settings_link = '<a href="ms-admin.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
            else
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

            $exclude_settings = apply_filters( 'blog_template_exclude_settings', "`option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'new_admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt'" );

            $results = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` NOT LIKE '_transient%' AND $exclude_settings");
            foreach ($results as $row) {
                $wp_options[$row->option_name] = array('blog_id'=>$row->blog_id,'option_value'=>$row->option_value,'autoload'=>$row->autoload);
            }
            return $wp_options;
        }

        /**
        * Separated into its own function so we could include it in the init hook
        */
        function admin_options_page_posted() {
            if ( !isset( $_GET['page'] ) || $_GET['page'] !== 'blog_templates.php' )
				return; //If this isn't the options page, we don't even need to check

            unset($this->options['templates']['']); //Delete the [] item, this will fix corrupted data

            $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

            if( !empty( $_POST['save_updated_template'] ) ) {
                if (! wp_verify_nonce($_POST['_wpnonce'], 'blog_templates-update-options') )
					die('Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)');
                $this->options['templates'][$t]['name'] = stripslashes($_POST['template_name']);
                $this->options['templates'][$t]['to_copy'] = (array)$_POST['to_copy'];
                $this->options['templates'][$t]['additional_tables'] = $_POST['additional_template_tables'];

                $this->saveAdminOptions();

                echo '<div class="updated fade"><p>Success! Your changes were sucessfully saved!</p></div>';
            } elseif( !empty( $_POST['save_new_template'] ) ) {
                global $wpdb;
                if (! wp_verify_nonce($_POST['_wpnonce'], 'blog_templates-update-options') )
					die('Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)');
                $this->options['templates'][] = array('name'=>stripslashes($_POST['template_name']),'blog_id'=>$wpdb->blogid,'to_copy' => (array)$_POST['to_copy']);

                $this->saveAdminOptions();

                echo '<div class="updated fade"><p>Success! Your changes were sucessfully saved!</p></div>';
            } elseif( isset( $_POST['remove_default'] ) ) {
                if ( ! wp_verify_nonce($_POST['_wpnonce'], 'blog_templates-update-options') )
					die('Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)');
                unset($this->options['default']);

                $this->saveAdminOptions();

                if ( ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) || ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) ) {
                    //These querystring vars must have been left over from an earlier link click, remove them
                    $to_url = remove_query_arg(array('default','d'),$this->currenturl_with_querystring);
                } else {
                    $to_url = $this->currenturl_with_querystring;
                }
                echo '<div class="updated fade"><p>Success! The default option was successfully turned off.</p></div>';
            } elseif ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {
                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-make_default') ) die('Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)');

                $this->options['default'] = $_GET['default'];

                $this->saveAdminOptions();

                echo '<div class="updated fade"><p>Success! The default template was sucessfully updated.</p></div>';
            } elseif ( isset( $_GET['d'] ) && is_numeric( $_GET['d'] ) ) {
                if (! wp_verify_nonce($_GET['_wpnonce'], 'blog_templates-delete_template') ) die('Whoops! There was a problem with the data you posted. Please go back and try again. (Generated by New Blog Templates)');
                unset($this->options['templates'][$_GET['d']]);

                $this->saveAdminOptions();

                echo '<div class="updated fade"><p>Success! The template was successfully deleted.</p></div>';
            }
        }

        /**
        * Adds settings/options page
        */
        function admin_options_page() {
            $t = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

            global $pagenow;
            $url = $pagenow . '?page=blog_templates.php';
?>

<div class="wrap">
    <form method="post" id="options">
    <?php wp_nonce_field('blog_templates-update-options');
        if (!is_numeric($t)) { ?>
            <h2>Blog Templates</h2>
            <table width="100%" cellspacing="2" cellpadding="5" class="widefat fixed">
                <thead>
                <tr><th><?php _e('Template Name',$this->localizationDomain); ?></th><th><?php _e('Blog',$this->localizationDomain); ?></th><th><?php _e('Actions',$this->localizationDomain); ?></th></tr>
                </thead>
                <tfoot>
                <tr><th><?php _e('Template Name',$this->localizationDomain); ?></th><th><?php _e('Blog',$this->localizationDomain); ?></th><th><?php _e('Actions',$this->localizationDomain); ?></th></tr>
                </tfoot>
                <?php foreach ($this->options['templates'] as $key => $template) { ?>
                <tr valign="top">
                    <td><a href="<?php echo $url . '&t=' . $key ?>"><?php echo $template['name'] ?></a></td>
                    <td><b><?php echo get_blog_option($template['blog_id'],'blogname'); ?></b> <a href="<?php echo get_blog_option($template['blog_id'],'siteurl'); ?>/wp-admin">[Dashboard]</a></td>
                    <td><?php echo ( isset( $this->options['default'] ) && is_numeric( $this->options['default'] ) && $this->options['default'] == $key ) ?'<b>(Default)</b>' : '<a href="' . wp_nonce_url($url . '&default=' . $key,"blog_templates-make_default") . '">[Make Default]</a>'; ?>
                    <a href="<?php echo wp_nonce_url($url . '&d=' . $key,"blog_templates-delete_template")?>">[Delete]</a></td>
                </tr>
                <?php } ?>
            </table>
            <h2><?php _e('Create New Blog Template',$this->localizationDomain); ?></h2>
            <p><?php _e('Create a blog template based on the current blog! This allows you (and other admins) to copy all of the current blog\'s settings and allow you to create other blogs
            that are almost exact copies of this blog. (Blog name, URL, etc will change, so it\'s not a 100% copy)',$this->localizationDomain); ?></p>
            <p><?php _e('Simply fill out the form below and click "Create Blog Template!" to generate the template for later use!',$this->localizationDomain); ?></p>
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
                        <?php echo get_option('siteurl'); ?>
                    </th>
                </tr>
                <tr valign="top">
                    <th width="33%"><?php _e('What To Copy To New Blog?', $this->localizationDomain); ?></th>
                    <td>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="settings" value="settings"><label for="settings"><?php _e('Wordpress Settings, Current Theme, and Active Plugins', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="posts" value="posts"><label for="posts"><?php _e('Posts and Pages', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="terms" value="terms"><label for="terms"><?php _e('Categories, Tags, and Links', $this->localizationDomain); ?></label></span><br/>
                        <span style="padding-right: 10px;"><input type="checkbox" name="to_copy[]" id="users" value="users"><label for="users"><?php _e('Users', $this->localizationDomain); ?></label></span><br/>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Advanced'); ?></th>
                    <td><?php _e('After you add this template, an advanced options area will show up on the edit screen (Click on the template name when it appears in the list above). In that advanced area,
                    you can choose to add full tables to the template, in case you\'re using a plugin that creates its own database tables. Note that this is not required for new Blog Templates to work'); ?></td>
                </tr>
                </table>
                <p>
                    <?php _e('Please note that this will turn the blog you are currently logged in
                    to',$this->localizationDomain); ?> - <?php bloginfo('wpurl'); ?> - <?php _e('into a template blog. Any changes you make to this blog will change the
                    template, as well! We recommend creating specific "Template Blogs" for this purpose, so you don\'t accidentally add new settings, content, or users
                    that you don\'t want in your template.',$this->localizationDomain); ?></p>
                    <p><?php _e('This means that if you would like to create a dedicated template blog for this template, please',$this->localizationDomain); ?>
                    <a href="<?php
                    if (get_bloginfo('version') >= 3)
                        echo admin_url('ms-sites.php');
                    else
                        echo admin_url('wpmu-blogs.php');
                    ?>"><?php _e('create a new blog',$this->localizationDomain); ?></a> <?php _e('and then visit this page when you are
                    logged in to the backend of that blog to create the template.',$this->localizationDomain); ?>
                </p>

            <p><div class="submit"><input type="submit" name="save_new_template" class="button-primary" value="Create Blog Template!" /></div></p>
            <h2><?php _e('Default Template',$this->localizationDomain); ?></h2>
            <p><?php _e('You can set one of your templates to be the default template. The default template gets automatically applied to any new blog creation, whether it\'s through
            Wordpress, BuddyPress, or another plugin, as long as they use the internal Wordpress blog creation function. That means if you choose to use a default template,
            any new blog created will automatically use that template - including blogs that users set up themselves!',$this->localizationDomain); ?></p>
            <p><?php _e('If you\'ve set a blog as a default blog (if you did, one of the blogs above will say <b>(Default)</b> under the Actions column) and you no longer want to use
            a default template, simply click the button below to remove the default option. You can always make another template default later by clicking one of the [Make Default]
            links above. Note: Clicking this button will not delete any templates, it simply turns off the default feature until you choose another default template.',$this->localizationDomain); ?></p>
            <p><div class="submit"><input type="submit" name="remove_default" class="button-primary" value="<?php _e('Remove Default Option',$this->localizationDomain); ?>" /></div></p>
        <?php
            } else {
                $template = $this->options['templates'][$t];
        ?>
            <p><a href="<?php echo $url; ?>">&laquo; <?php _e('Back to Blog Templates', $this->localizationDomain); ?></a></p>
            <h2><?php _e('Edit Blog Template', $this->localizationDomain); ?></h2>
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
            </table>
            <p><div class="submit"><input type="submit" name="save_updated_template" value="<?php _e('Save', $this->localizationDomain); ?> &raquo;" class="button-primary" /></div></p>
            <h2><?php _e('Advanced Options',$this->localizationDomain); ?></h2>
            <table width="100%" cellspacing="2" cellpadding="5" class="widefat fixed">
                <tr>
                    <td width="33%"><b><?php _e('Additional Tables',$this->localizationDomain); ?></b><br/>
                    <?php
                        global $wpdb;

                        switch_to_blog($template['blog_id']);

                        printf(__('The tables listed here were likely created by plugins you currently have or have had running on this blog. If you want
                        the data from these tables copied over to your new blogs, add a checkmark next to the table. Note that the only tables displayed here
                        begin with %s, which is the standard table prefix for this specific blog. Plugins not following this convention will not have
                        their tables listed here.',$this->localizationDomain),$wpdb->prefix); ?>
                    </td>
                    <td>
                        <?php

                            //Grab all non-core tables and display them as options
                            $results = $wpdb->get_results("SHOW TABLES LIKE '" . str_replace('_','\_',$wpdb->prefix) . "%'", ARRAY_N);
                            if (!empty($results)) {
                                foreach($results as $result) {
                                    if (!in_array(str_replace($wpdb->prefix,'',$result['0']),$wpdb->tables)) {
                                        echo "<input type='checkbox' name='additional_template_tables[]' value='$result[0]'";
                                        if (is_array($template['additional_tables']))
                                            if (in_array($result[0],$template['additional_tables']))
                                                echo ' checked="CHECKED"';
                                        echo ">$result[0]</option><br/>";
                                    }
                                }
                            } else {
                                _e('There are no additional tables to display for this blog',$this->localizationDomain);
                            }

                            restore_current_blog();
                        ?>
                    </td>
                </tr>
            </table>
            <p><div class="submit"><input type="submit" name="save_updated_template" value="<?php _e('Save', $this->localizationDomain); ?> &raquo;" class="button-primary" /></div></p>
        <?php } ?>
    </form>
<?php
        }
    } //End Class
    //instantiate the class
    $blog_templates_var = new blog_templates();
} //End if blog_templates class exists statement
?>
